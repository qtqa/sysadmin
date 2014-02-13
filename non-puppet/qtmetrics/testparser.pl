#!/usr/bin/env perl
#############################################################################
##
## Copyright (C) 2013 Digia Plc and/or its subsidiary(-ies).
## Contact: http://www.qt-project.org/legal
##
## This file is part of the Qt Metrics web portal.
##
## $QT_BEGIN_LICENSE:LGPL$
## Commercial License Usage
## Licensees holding valid commercial Qt licenses may use this file in
## accordance with the commercial license agreement provided with the
## Software or, alternatively, in accordance with the terms contained in
## a written agreement between you and Digia.  For licensing terms and
## conditions see http://qt.digia.com/licensing.  For further information
## use the contact form at http://qt.digia.com/contact-us.
##
## GNU Lesser General Public License Usage
## Alternatively, this file may be used under the terms of the GNU Lesser
## General Public License version 2.1 as published by the Free Software
## Foundation and appearing in the file LICENSE.LGPL included in the
## packaging of this file.  Please review the following information to
## ensure the GNU Lesser General Public License version 2.1 requirements
## will be met: http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
##
## In addition, as a special exception, Digia gives you certain additional
## rights.  These rights are described in the Digia Qt LGPL Exception
## version 1.1, included in the file LGPL_EXCEPTION.txt in this package.
##
## GNU General Public License Usage
## Alternatively, this file may be used under the terms of the GNU
## General Public License version 3.0 as published by the Free Software
## Foundation and appearing in the file LICENSE.GPL included in the
## packaging of this file.  Please review the following information to
## ensure the GNU General Public License version 3.0 requirements will be
## met: http://www.gnu.org/copyleft/gpl.html.
##
##
## $QT_END_LICENSE$
##
#############################################################################
=head1 NAME

testparser.pl - Qt CI system, Gather metrics from build logs to SQL database

=head1 SYNOPSIS

$ ./testparser.pl -method <single|catchup|full> workdir [-delete ] [-verbose] [-sqloutput <file>] [-reload] [-limit <DATE>]

Scan through logs for one or several builds in one go.

=head2 OPTIONS

=over

=item -method <method>

Method used in scanning the directories
See L<METHOD> for more information.

=item -delete

Deletes the old database before inserting new data.

=item workdir

Directory from which the scan will initiate. This can vary from the top level
directory to the build specific directory, depending on the method of scan.
See L<METHOD> for more information.

=item -verbose

Prints a lot more information of what the script does.

=item -sqloutput <file>

Define a file into which table injection commands are written to.
This requires -verbose to be used as well or nothing will be printed
to the file.

=item -reload

Possible to use when using 'single' as L<METHOD>. Reloads information
for given build into database by removing the old data matching
the project and project number currently read.

=item -limit <DATE>

Possible to use when using 'full' as L<METHOD>. Skips folders that
have time stamps older than given date. Enter the date in format: "YYYYMMDD".

=back

=head1 METHOD

The method parameter tells the script how you wish to scan the logs.
The script provides three different ways of working:

=over

=item B<SINGLE> (default)

With SINGLE the script scans through one given build directory and
adds the results to the SQL database. Therefore the path given as the
workdir must be pointed directly to the build folder itself.
E.g. C</var/results/Qt5_stable_Integration/build_00404>

=item B<CATCHUP>

With CATCHUP the script brings the database up to date with the
latest builds results. If looks for new build folders for existing
projects and also looks if there are new projects in the given
workdir. Thus the workdir must be pointed to the top level of the
hierarchy.
E.g. C</var/results>

=item B<FULL>

With FULL the script goes through all the build folders and
creates a new database. As with CATCHUP, the workdir should
point to the top level of the hierarchy.
E.g. C</var/results>

=back

=head1 EXAMPLES OF USAGE

C<testparser.pl -m catchup c:\work\results>

C<testparser.pl -method full -d /var/ci-results/logs>

C<testparser.pl -m single /var/ci-results/logs/Qt5_stable_Integration/Build_01234>

=cut

use strict;
use warnings;
use File::Spec::Functions;
use File::Slurp qw(read_dir);
use JSON;
use CGI;
use IO::Uncompress::Gunzip qw(gunzip $GunzipError) ;
use Date::Parse;
use DateTime;
use Time::Piece;
use Getopt::Long qw( GetOptionsFromArray );
use DBI();
use Pod::Usage;

my $BUILDSTATEFILE = "state.json.gz";
my $BUILDLOGFILE = "log.txt.gz";
my $VERBOSE = 0;

sub process_arguments
{
    my (@args) = @_;
    my %options;
    $options{method} = 'single';
    GetOptionsFromArray( \@args,
        'method=s'    => \$options{method},
        'delete'      => \$options{delete},
        'sqloutput=s' => \$options{sqloutput},
        'verbose'     => \$options{verbose},
        'reload'      => \$options{reload},
        'limit=s'     => \$options{datelimit},
        'h|help|?'    => sub { pod2usage(1) },
    ) || die;
    if ($#args < 0) {
        print "Workpath not defined.\n";
        exit 1;
    }

    $options{method} = lc($options{method});
    if ($options{method} !~ m/^(full|catchup|single)$/) {
        print "Unknown method.\n";
        exit 1;
    }

    $options{workpath} = pop(@args);
    if (! -d $options{workpath}) {
        print "Workpath \"$options{workpath}\" not found.\n";
        exit 1;
    }

    if (defined $options{delete}) {
        print "Warning: Deleting of current database before new indexing selected.\n";
        print "You have 5 seconds to abort.\n";
        sleep(5);
    }

    if (defined $options{reload}) {
        if ($options{method} !~ m/^single$/) {
            print "Can only use -reload with 'single' method.\n";
            exit 1;
        }
    }

    if (defined $options{datelimit}) {
        my $format = '%Y%m%d';
        my $tp = Time::Piece->strptime($options{datelimit}, $format);
        my $dt = DateTime->new(
            year => $tp->year(),
            month => $tp->mon(),
            day => $tp->mday(),
        );
        $options{datelimit} = $dt;
        print "Date limit set to: $dt\n";
    }

    $VERBOSE = 1 if defined $options{verbose};

    return %options;
}

sub uncompress_to_scalar
{
    my $input = shift;
    return "" if !$input;
    my $gzoutput;

    if (check_exists_and_openable($input)) {
      use Archive::Extract;
      my $ae = Archive::Extract->new( archive => $input ) or warn ("Can't create archive object.");
      if ($ae->is_gz) {
          #print "Gzip compressed\n";
          local $/;
          gunzip $input => \$gzoutput or warn "gunzip filed: $GunzipError\n";
      } else{
          print "warning: Inputfile $input is not a .gz file\n";
      }
      return $gzoutput;
    }
    else {
        print "Scalar being return is null\n";
        return;
    }
}

sub read_json
{
    my $raw_input = shift;
    my $ret;
    eval {$ret = decode_json($raw_input);};
    return $ret;
}

sub if_defined
{
    my $value = shift;
    return $value ? $value : "";
}

#convert epoch time with milliseconds to ISO time
sub epoch_ms_to_iso
{
    my $epochtime = shift;
    my $time = "";
    if (defined $epochtime) {
        $epochtime /= 1000;
        $time = DateTime->from_epoch( epoch => $epochtime);
    }
    return $time;
}

sub epoch_s_to_iso
{
    my $epochtime = shift;
    my $time = "";
    if (defined $epochtime) {
        $time = DateTime->from_epoch( epoch => $epochtime);
    }
    return $time;
}

sub ms_to_hms
{
    my $in_seconds = shift;
    if (defined $in_seconds) {
        $in_seconds /= 1000;
        my ($days,$hours,$minutes,$seconds) = (gmtime $in_seconds)[7,2,1,0];
        $hours += $days * 24;
        return "$hours:$minutes:$seconds";
    }
    return "";
}

sub read_build_data
{
    my $statehash = shift;
    my $inputfolder = shift;
    my %data;
    my $backup_time;

    print "Getting data of $inputfolder.\n";

    # the 'result' data might get redefined later, if one of the configurations has failed
    $data{RESULT} = if_defined($statehash->{build}->{result});
    $data{FULLDISPLAYNAME} = if_defined($statehash->{build}->{fullDisplayName});
    $data{FULLDISPLAYNAME} =~ s/\s#\d+$//;
    $data{BUILD_NUMBER} = if_defined($statehash->{build}->{number});
    $data{ABORTEDBYINTEGRATOR} = if_defined($statehash->{build}->{aborted_by_integrator});
    $data{TIMESTAMP} = epoch_ms_to_iso($statehash->{build}->{timestamp});
    $data{DURATION} = ms_to_hms($statehash->{build}->{duration});
    $data{URL} = if_defined($statehash->{build}->{url});

    #loop through all the runs (an array)
    foreach my $runhash (@{$statehash->{build}->{runs}}) {

        my $result = if_defined($runhash->{result});
        my $number = if_defined($runhash->{number});

        my $cfg = if_defined($runhash->{url});
        $cfg =~ s{^.*?cfg=(.*)/\d+/}{$1};
        print "cfg = $cfg\n";

        if (defined $cfg and defined $data{NUMBER}) {
            if ($number ne $data{NUMBER}) {
                #TODO: $data{url} and $data{BUILD_NUMBER} might be undef
                print "ALARM! In $data{URL} $cfg\'s build number $number does not match main number $data{BUILD_NUMBER}\n";
                print "       Marking this configuration as \"CANCELLED\"\n";
                $result = "CANCELLED";
                last;
            }
        }

        if (defined $result and $result =~ m/SUCCESS/) {
            $data{cfg}{$cfg}{builddata}{RESULT} = "SUCCESS";
        } elsif (defined $result and $result =~ m/FAILURE/) {
            $data{cfg}{$cfg}{builddata}{RESULT} = "FAILURE";
            # If one of the configurations has failed, the ABORTED is true, but it's due to something failing.
            # Thus we change the overall status to FAILURE to represent the status more clearly.
            $data{RESULT} = "FAILURE";
        } elsif (defined $result and $result =~ m/ABORTED/) {
            $data{cfg}{$cfg}{builddata}{RESULT} = "ABORTED";
        } else {
            $data{cfg}{$cfg}{builddata}{RESULT} = "undef";
        }

        if (check_exists_and_openable(catfile($inputfolder,$cfg,$BUILDLOGFILE))) {
            my @content_in_array = split("\n",uncompress_to_scalar(catfile($inputfolder,$cfg,$BUILDLOGFILE)));
            $data{cfg}{$cfg}{logdata} = get_log_data(@content_in_array);
            $data{cfg}{$cfg}{testresults} = get_test_results(@content_in_array);
            $data{cfg}{$cfg}{phases} = get_phase_times(@content_in_array);
            $backup_time = epoch_s_to_iso(get_modify_time(catfile($inputfolder,$cfg,$BUILDLOGFILE)));
        }

        my $cfg_timestamp = epoch_ms_to_iso($runhash->{timestamp});
        $data{cfg}{$cfg}{builddata}{TIMESTAMP} = $cfg_timestamp ? $cfg_timestamp : $backup_time;
        $data{cfg}{$cfg}{builddata}{DURATION} = ms_to_hms($runhash->{duration});

    }
    return \%data;
}

sub get_modify_time
{
    my $file = shift;
    my $date = (stat $file )[9];
    return $date;
}

sub getdata
{
    my @logarray = @{(shift)};
    my $regexp = shift;

    foreach my $line (@logarray) {
        $line =~ s/[\n|\r]$//g;
        return $1 if ($line =~ m/$regexp/);
    }
    return;
}

sub exists_in_array
{
    my @arr = @{(shift)};
    my $regexp = shift;
    foreach my $line (@arr) {
        $line =~ s/[\n|\r]$//g;
        return 1 if ($line =~ m/$regexp/);
    }
    return 0;
}

sub get_log_data
{
    print "Getting general data from log files.\n";
    my @filecontent = @_;
    my %logdata;
    $logdata{project} = getdata(\@filecontent, qr/^Started by upstream project "(.*)" build number \d+$/);
    $logdata{build_number} = getdata(\@filecontent, qr/^Started by upstream project ".*" build number (\d+)$/);
    $logdata{build_node} = getdata(\@filecontent, qr/^Building remotely on (.*) in workspace .*$/);
    $logdata{node_labels} = getdata(\@filecontent, qr/^NODE_LABELS=(.*)$/);
    $logdata{jenkins_url} = getdata(\@filecontent, qr/^JENKINS_URL=(.*)$/);
    $logdata{build_id} = getdata(\@filecontent, qr/^BUILD_ID=(.*)$/);
    $logdata{build_url} = getdata(\@filecontent, qr/^BUILD_URL=(.*)$/);
    $logdata{build_tag} = getdata(\@filecontent, qr/^BUILD_TAG=(.*)$/);
    $logdata{job_name} = getdata(\@filecontent, qr/^JOB_NAME=(.*)$/);
    $logdata{job_url} = getdata(\@filecontent, qr/^JOB_URL=(.*)$/);
    $logdata{cfg} = getdata(\@filecontent, qr/^cfg='(.*)'$/);
    $logdata{qtqa_qt_configure_args} = getdata(\@filecontent, qr/^set QTQA_QT_CONFIGURE_ARGS=(.*)$/);
    $logdata{qtqa_qt_configure_extra_args} = getdata(\@filecontent, qr/^set QTQA_QT_CONFIGURE_EXTRA_ARGS=(.*)$/);
    $logdata{FORCESUCCESS} = exists_in_array(\@filecontent, qr/^Normally I would now fail.  However, `forcesuccess' was set in/);
    $logdata{FORCESUCCESS} |= exists_in_array(\@filecontent, qr/^Note: forcesuccess is set, but the test script succeeded./);
    $logdata{INSIGNIFICANT} = exists_in_array(\@filecontent, qr/^This is a warning, not an error, because the `qt.tests.insignificant' option was used./);
    return (\%logdata);
}

sub get_test_results
{
    print "Getting test results.\n";
    my @filecontent = @_;

    my $RESULTPARTSTR = qr/=== Timing: =================== TEST RUN COMPLETED! ============================/;
    my $RESULTPARTSTR2 = qr/=== Failures: ==================================================================/;
    my $RESULTPARTSTR3 = qr/=== Totals: .*=/;

    my $phase = 0;
    my $autotest = 0;
    my %testresults;
    my $total_autotests = 0;
    foreach my $line (@filecontent) {
        $line =~ s/[\n|\r]$//g;
        $phase = 1 if ($line =~ m/^$RESULTPARTSTR$/);
        $phase = 2 if ($line =~ m/^$RESULTPARTSTR2$/);
        $autotest = 1 if ($line =~ m/#=#.*?#=#\s\>(.*)$/);
        $autotest = 0 if ($line =~ m/#=#.*?#=#\s\<(.*)\s#=# Elapsed (\d+) second\(s\).$/);

        if (1 == $autotest) {
            $total_autotests++ if ($line =~ m/^Testing\s(.*)$/);
        }

        if (2 == $phase) {
            if ($line =~ m/^\s{2}(.*?)\s*(\[insignificant\])*$/) {
                print "Found test case '$1'\n" if $VERBOSE;
                if (!defined $2) {
                    push (@{$testresults{failed_tests}}, $1);
                } elsif ("[insignificant]" eq $2) {
                    push (@{$testresults{insignificant_failed_tests}}, $1);
                } else {
                    push (@{$testresults{unspecified_tests}}, $1);
                }
            }
            else { last if ($line =~ m/^$RESULTPARTSTR3$/); }
        }
    }
    $testresults{TOTAL_AUTOTESTS} = $total_autotests;
    return (\%testresults);
}

sub get_phase_times
{
    print "Getting times for different phases.\n";
    my @filecontent = @_;

    my $TIMESTR = qr/\w{3}\s\w{3}\s+\d+\s\d{2}:\d{2}:\d{2}\s\d{4}/;

    my %phasedata;
    my @phases;
    foreach my $line (@filecontent) {
        next if ($line !~ m/#=#/);
        $line =~ s/[\n|\r]$//g;
        my $parent = "";
        my ($timestr) = $line =~ m/#=# ($TIMESTR)/;
        my $time = DateTime->from_epoch( epoch => str2time($timestr), time_zone => 'local');

        if ($line =~ m/#=#.*?#=#\s\>(.*)$/) {
            push (@phases, $1);
            print "Entering phase '$phases[-1]' in time $time.\n" if $VERBOSE;
            $parent = $phases[-2] || "";

            $phasedata{$phases[-1]}{start} = $time;
            $phasedata{$phases[-1]}{parent} = $parent;
        }
        if ($line =~ m/#=#.*?#=#\s\<(.*)\s#=# Elapsed (\d+) second\(s\).$/) {
            my $returningphase = $1;
            my $duration = $2;
            my $stackphase = pop (@phases);
            if ($returningphase ne $stackphase) {
                print "Odd order in phases. Returning '$returningphase' doesn't match phase in stack '$stackphase'.\n";
            }
            print "Exiting phase '$returningphase' in time $time. Duration: $duration.\n" if $VERBOSE;

            $phasedata{$returningphase}{end} = $time;
        }
    }
    return (\%phasedata);
}

sub sql_connect
{
    my $dbh;
    print "Connecting to MySQL...\n";
    $ENV{HOME} = $ENV{HOMEPATH} if ($^O =~ m/mswin32/i);

    # Connect to the database.
    die "Can't access SQL configuration" if (!check_exists_and_openable ("$ENV{HOME}/.my.cnf"));
    my $dsn = "DBI:mysql:;mysql_read_default_file=$ENV{HOME}/.my.cnf";
    eval {
        $dbh = DBI->connect($dsn, undef, undef, {'RaiseError' => 1});
    };
    if ($@) {
        die("Connection to SQL failed because $@");
    }
    return $dbh;
}

sub sql_disconnect
{
    print "Disconnecting from MySQL...\n";
    my $dbh = shift;
    eval {
        $dbh->disconnect();
    };
    if ($@) {
        die("Disconnection from SQL failed because $@");
    }
}

sub sql_drop_table
{
    my $dbh = shift;
    my $table = shift;
    eval {
        $dbh->do ("DROP TABLE IF EXISTS $table");
    };
    if ($@) {
        die("Removal of table '$table' failed because $@");
    }

}

sub sql_drop_tables
{
    my $dbh = shift;

    print "Dropping old tables.\n";
    sql_drop_table($dbh, "generic");
    sql_drop_table($dbh, "ci");
    sql_drop_table($dbh, "cfg");
    sql_drop_table($dbh, "test");
    sql_drop_table($dbh, "phases");

}

sub sql_create_tables
{
    my $dbh = shift;
    print "Creating new tables.\n";

    $dbh->{AutoCommit} = 0;  # enable transactions, if possible
    $dbh->{RaiseError} = 1;

    eval {
        $dbh->do ("CREATE TABLE IF NOT EXISTS generic (name VARCHAR(10),
                                                       rebuild BOOLEAN,
                                                       date TIMESTAMP NULL,
                                                       current INTEGER,
                                                       total INTEGER)
                  ");

        # gives the table initial values, since only one row is used in this table
        if ("0E0" eq $dbh->do ("SELECT * FROM generic")) {
            $dbh->do ("INSERT IGNORE INTO generic (name, rebuild, date, current, total) values ('qt', 0, NULL, 0, 0);");
        }

        $dbh->do ("CREATE TABLE IF NOT EXISTS ci (project VARCHAR(50),
                                                  build_number INTEGER,
                                                  result VARCHAR(10),
                                                  timestamp TIMESTAMP NULL,
                                                  duration TIME NULL,
                                                  PRIMARY KEY (`project`, `build_number`)
                                                 )
                  ");
        $dbh->do ("CREATE TABLE IF NOT EXISTS cfg (cfg VARCHAR(100),
                                                   project VARCHAR(50),
                                                   build_number INTEGER,
                                                   result VARCHAR(10),
                                                   forcesuccess BOOLEAN,
                                                   insignificant BOOLEAN,
                                                   total_autotests INTEGER,
                                                   timestamp TIMESTAMP NULL,
                                                   duration TIME NULL,
                                                   PRIMARY KEY (`cfg`, `project`, `build_number`)
                                                  )
                  ");
        $dbh->do ("CREATE TABLE IF NOT EXISTS test (name VARCHAR(50),
                                                    project VARCHAR(50),
                                                    build_number INTEGER,
                                                    cfg VARCHAR(100),
                                                    insignificant BOOLEAN,
                                                    timestamp TIMESTAMP NULL,
                                                    PRIMARY KEY (`name`, `project`, `build_number`, `cfg`)
                                                   )
                  ");
        $dbh->do ("CREATE TABLE IF NOT EXISTS phases (project VARCHAR(50),
                                                      build_number INTEGER,
                                                      cfg VARCHAR(100),
                                                      phase VARCHAR(100),
                                                      parent VARCHAR(100),
                                                      start TIMESTAMP NULL,
                                                      end TIMESTAMP NULL,
                                                      PRIMARY KEY (`project`, `build_number`, `cfg`, `phase`)
                                                     )
                  ");
        $dbh->commit;   # commit the changes if we get this far
    };
    if ($@) {
        print "Transaction aborted because $@";
        print "This will leave current data out from the database. Look for this in logs and figure out the problem.\n";
        eval { $dbh->rollback };
    } else {
        print "Tables created.\n";
    }
    $dbh->{AutoCommit} = 1;  # disable transactions, if possible
}

sub sql
{
    my $dbh = shift;
    my %options = %{(shift)};
    my %datahash = %{(shift)};
    my $output = $options{sqloutput};

    open(OUTPUT, ($output ? ">>$output" : ">&STDOUT"));

    $dbh->{AutoCommit} = 0;  # enable transactions, if possible
    $dbh->{RaiseError} = 1;
    if (defined $options{reload}) {
        eval {
            # if 'reload' is defined in options, remove possible data from sql database before storing new data
            print "Deleting old data from database.\n";
            print "DELETE from ci where project=\"$datahash{FULLDISPLAYNAME}\" and build_number=$datahash{BUILD_NUMBER}\n" if $VERBOSE or $output;
            $dbh->do ("DELETE from ci where project=\"$datahash{FULLDISPLAYNAME}\" and build_number=$datahash{BUILD_NUMBER}") or print "removal of old data in ci failed: $!\n" if !$output;
            $dbh->do ("DELETE from cfg where project=\"$datahash{FULLDISPLAYNAME}\" and build_number=$datahash{BUILD_NUMBER}") or print "removal of old data in cfg failed: $!\n" if !$output;
            $dbh->do ("DELETE from test where project=\"$datahash{FULLDISPLAYNAME}\" and build_number=$datahash{BUILD_NUMBER}") or print "removal of old data in test failed: $!\n" if !$output;
            $dbh->do ("DELETE from phases where project=\"$datahash{FULLDISPLAYNAME}\" and build_number=$datahash{BUILD_NUMBER}") or print "removal of old data in phases failed: $!\n" if !$output;
            $dbh->commit;   # commit the changes if we get this far
        };
        if ($@) {
            warn "Transaction aborted because $@";
            eval { $dbh->rollback };
        } else {
            print "Data deleted.\n";
        }
    }

    print "Storing data to sql.\n";

    eval {
        # Have to store quotes in scalar, as printing can't have those if we want to have the possibility to print NULL.
        my $timestamp = $datahash{TIMESTAMP} ? "\"$datahash{TIMESTAMP}\"" : "NULL";
        my $duration = $datahash{DURATION} ? "\"$datahash{DURATION}\"" : "NULL";
        print OUTPUT "INSERT INTO ci VALUES (\"$datahash{FULLDISPLAYNAME}\",
                                               $datahash{BUILD_NUMBER},
                                               \"$datahash{RESULT}\",
                                               $timestamp,
                                               $duration)\n" if $VERBOSE or $output;
        $dbh->do ("INSERT INTO ci VALUES (\"$datahash{FULLDISPLAYNAME}\",
                                          $datahash{BUILD_NUMBER},
                                          \"$datahash{RESULT}\",
                                          $timestamp,
                                          $duration)
                  ") or print "insert of keys info ci failed: $!\n" if !$output;

        #insert data for configuration
        foreach my $cfg (keys %{$datahash{cfg}}) {
            my $forcesuccess_cfg = $datahash{cfg}{$cfg}{logdata}{FORCESUCCESS} ? $datahash{cfg}{$cfg}{logdata}{FORCESUCCESS} : 0;
            my $insignificant_cfg = $datahash{cfg}{$cfg}{logdata}{INSIGNIFICANT} ? $datahash{cfg}{$cfg}{logdata}{INSIGNIFICANT} : 0;
            my $total_autotests = $datahash{cfg}{$cfg}{testresults}{TOTAL_AUTOTESTS} ? $datahash{cfg}{$cfg}{testresults}{TOTAL_AUTOTESTS} : 0;
            my $timestamp_cfg = $datahash{cfg}{$cfg}{builddata}{TIMESTAMP} ? "\"$datahash{cfg}{$cfg}{builddata}{TIMESTAMP}\"" : "NULL";
            my $duration_cfg = $datahash{cfg}{$cfg}{builddata}{DURATION} ? "\"$datahash{cfg}{$cfg}{builddata}{DURATION}\"" : "NULL";
            print OUTPUT "INSERT INTO cfg VALUES (\"$cfg\",
                                                     \"$datahash{FULLDISPLAYNAME}\",
                                                     $datahash{BUILD_NUMBER},
                                                     \"$datahash{cfg}{$cfg}{builddata}{RESULT}\",
                                                     $forcesuccess_cfg,
                                                     $insignificant_cfg,
                                                     $total_autotests,
                                                     $timestamp_cfg,
                                                     $duration_cfg)\n" if $VERBOSE or $output;
            $dbh->do ("INSERT INTO cfg VALUES (\"$cfg\",
                                               \"$datahash{FULLDISPLAYNAME}\",
                                               $datahash{BUILD_NUMBER},
                                               \"$datahash{cfg}{$cfg}{builddata}{RESULT}\",
                                               $forcesuccess_cfg,
                                               $insignificant_cfg,
                                               $total_autotests,
                                               $timestamp_cfg,
                                               $duration_cfg)
                      ") or print "insert of keys into cfg failed: $!\n" if !$output;


            if (defined $datahash{cfg}{$cfg}{testresults}{insignificant_failed_tests}) {
                my $timestamp_cfg = $datahash{cfg}{$cfg}{builddata}{TIMESTAMP} ? "\"$datahash{cfg}{$cfg}{builddata}{TIMESTAMP}\"" : "NULL";
                foreach my $test (@{$datahash{cfg}{$cfg}{testresults}{insignificant_failed_tests}}) {
                    print "$cfg - $test (insignificant)\n" if $VERBOSE;
                    print OUTPUT "INSERT INTO test VALUES (\"$test\",
                                                           \"$datahash{FULLDISPLAYNAME}\",
                                                           $datahash{BUILD_NUMBER},
                                                           \"$cfg\",
                                                           1,
                                                           $timestamp_cfg)\n" if $VERBOSE or $output;
                    $dbh->do ("INSERT INTO test VALUES (\"$test\",
                                                        \"$datahash{FULLDISPLAYNAME}\",
                                                        $datahash{BUILD_NUMBER},
                                                        \"$cfg\",
                                                        1,
                                                        $timestamp_cfg)
                              ") or print "insert of keys into test failed: $!\n" if !$output;
                }
            }
            if (defined $datahash{cfg}{$cfg}{testresults}{failed_tests}) {
                my $timestamp_cfg = $datahash{cfg}{$cfg}{builddata}{TIMESTAMP} ? "\"$datahash{cfg}{$cfg}{builddata}{TIMESTAMP}\"" : "NULL";
                foreach my $test (@{$datahash{cfg}{$cfg}{testresults}{failed_tests}}) {
                    print "$cfg - $test\n" if $VERBOSE;
                    print OUTPUT "INSERT INTO test VALUES (\"$test\",
                                                           \"$datahash{FULLDISPLAYNAME}\",
                                                           $datahash{BUILD_NUMBER},
                                                           \"$cfg\",
                                                           0,
                                                           $timestamp_cfg)\n" if $VERBOSE or $output;
                    $dbh->do ("INSERT INTO test VALUES (\"$test\",
                                                        \"$datahash{FULLDISPLAYNAME}\",
                                                        $datahash{BUILD_NUMBER},
                                                        \"$cfg\",
                                                        0,
                                                        $timestamp_cfg)
                              ") or print "insert of keys into test failed: $!\n" if !$output;
                }
            }
            if (defined $datahash{cfg}{$cfg}{phases}) {
                foreach my $phase (keys(%{$datahash{cfg}{$cfg}{phases}})) {
                    print "$cfg - $phase\n" if $VERBOSE;
                    my $parent = $datahash{cfg}{$cfg}{phases}{$phase}{parent} ? $datahash{cfg}{$cfg}{phases}{$phase}{parent} : "";
                    my $start = $datahash{cfg}{$cfg}{phases}{$phase}{start} ? $datahash{cfg}{$cfg}{phases}{$phase}{start} : "";
                    my $end = $datahash{cfg}{$cfg}{phases}{$phase}{end} ? $datahash{cfg}{$cfg}{phases}{$phase}{end} : "";
                    print OUTPUT "INSERT INTO phases VALUES (\"$datahash{FULLDISPLAYNAME}\",
                                                             $datahash{BUILD_NUMBER},
                                                             \"$cfg\",
                                                             \"$phase\",
                                                             \"$parent\",
                                                             \"$start\",
                                                             \"$end\")\n" if $VERBOSE or $output;
                    $dbh->do ("INSERT INTO phases VALUES (\"$datahash{FULLDISPLAYNAME}\",
                                                          $datahash{BUILD_NUMBER},
                                                          \"$cfg\",
                                                          \"$phase\",
                                                          \"$parent\",
                                                          \"$start\",
                                                          \"$end\")
                              ") or print "insert of keys into phases failed: $!\n" if !$output;
                }
            }

        }
        $dbh->commit;   # commit the changes if we get this far
    };
    if ($@) {
        warn "Transaction aborted because $@";
        # now rollback to undo the incomplete changes
        # but do it in an eval{} as it may also fail
        eval { $dbh->rollback };
        # add other application on-error-clean-up code here
    } else {
        print "Data committed to database.\n";
    }
    $dbh->{AutoCommit} = 1;  # disable transactions, if possible

    close OUTPUT;
}

sub check_exists_and_openable
{
    my $file = shift;
    open my $fh, "<", $file or do {
        print "$0: open $file: $!\n";
        return 0;
    };
    close $fh or print "$0: close $file: $!\n";
    return 1;
}

sub get_all_folders
{
    my $workdir = shift;
    my $timelimit = shift;
    my @folders;

    for my $dir (grep { -d catdir($workdir,$_) } read_dir($workdir)) {
        for my $dir2 (grep { -d catdir($workdir,$dir,$_) } read_dir(catdir($workdir,$dir))) {
            my $finaldir = catdir($workdir,$dir,$dir2);
            print "$finaldir\n";
            if ($finaldir !~ m/(latest-success|latest)/) {
                next if (folder_too_old($finaldir, $timelimit));
                push(@folders, $finaldir);
            }
        }
    }
    return \@folders;
}

sub sql_get_distinct
{
    my $dbh = shift;
    my $query = shift;
    my $table = shift;
    my @distincts;

    # Now retrieve data from the table.
    my $sth = $dbh->prepare("SELECT DISTINCT $query FROM $table");
    $sth->execute();
    while (my $ref = $sth->fetchrow_hashref()) {
        print "Found a row: project = $ref->{$query}\n" if $VERBOSE;
        push (@distincts, $ref->{$query});
    }
    $sth->finish();

    return @distincts;
}

sub sql_get_max
{
    my $dbh = shift;
    my $max = shift;
    my $where = shift;
    my $table = shift;
    my $highest;

    # Now retrieve data from the table.
    # select max(build_number) as article from ci where project='QtBase_stable_Integration';

    my $sth = $dbh->prepare("SELECT MAX($max) as $max from $table where $where");
    $sth->execute();
    while (my $ref = $sth->fetchrow_hashref()) {
        print "Found a row: $where: $ref->{$max}\n";
        $highest = $ref->{$max};
    }
    $sth->finish();

    return $highest;
}

sub get_catchup_folders
{
    my $workdir = shift;
    my $timelimit = shift;
    my @folders;
    my @distincts;
    my %distmaxno;

    my $dbh = sql_connect();
    @distincts = sql_get_distinct($dbh, "project", "ci");
    foreach (@distincts) {
        $distmaxno{$_} = sql_get_max($dbh, "build_number", "project='$_'", "ci");
    }
    sql_disconnect($dbh);

    # go through all projects in database and look for matching folder and compare build numbers
    print "Comparing projects in database against projects in log folder.\n";
    foreach my $proj (keys (%distmaxno)) {
        my $projdir = catdir($workdir,$proj);
        if (-d $projdir) {
            for my $dir (grep { -d catdir($projdir,$_) } read_dir($projdir)) {
                my $builddir = catdir($projdir,$dir);
                next if ($builddir !~ m/build_(\d+)/);
                next if (folder_too_old($builddir, $timelimit));
                my $builddir_nmbr = $1;
                if ($builddir_nmbr > $distmaxno{$proj}) {
                    print "Build dir '$builddir' has greater build number than $distmaxno{$proj} found for $proj\n";
                    push (@folders, $builddir);
                }
            }
        } else {
            print "Can't find $projdir\n";
        }
    }
    # go through all folders and compare project names to the ones in the database and see if database is missing a project entirely

    print "Comparing projects in log folder against projects in database.\n";
    for my $dir (grep { -d catdir($workdir,$_) } read_dir($workdir)) {
        # skip directory if it's already
        next if (exists $distmaxno{$dir});
        print "Found project '$dir' that's not in the database.\n";
        for my $dir2 (grep { -d catdir($workdir,$dir,$_) } read_dir(catdir($workdir,$dir))) {
            my $finaldir = catdir($workdir,$dir,$dir2);
            next if ($finaldir =~ m/(latest-success|latest)/);
            next if (folder_too_old($finaldir, $timelimit));
            push(@folders, $finaldir);
        }
    }
    return \@folders;
}

sub folder_too_old
{
    my $folder = shift;
    my $timelimit = shift;

    if (defined $timelimit) {
        my $modify_time = epoch_s_to_iso(get_modify_time($folder));
        if ($modify_time < $timelimit) {
            print "$folder older than specified time limit ($timelimit)\n" if $VERBOSE;
            return 1;
        }
    }
    return 0;
}

sub check_single_folder
{
     my $workdir = shift;
     my @folders = ();

     push (@folders, $workdir) if (-d $workdir);
     return \@folders;
}

sub sql_set_rebuild
{
    my $dbh = shift;
    my $rebuild = shift;
    my $timestamp = shift;

}

sub sql_update_progress
{
    my $dbh = shift;
    my %table = %{(shift)};

    $dbh->do ("UPDATE generic SET name=\"qt\", rebuild=$table{rebuild}, date=\"$table{date}\", current=$table{current}, total=$table{total};");
}

sub run
{
    my %options = process_arguments(@ARGV);

    my @inputfolders;

    if ($options{method} =~ m/^full$/) {
        @inputfolders = @{(get_all_folders($options{workpath}, $options{datelimit}))};
    }
    elsif ($options{method} =~ m/^catchup$/) {
        @inputfolders = @{(get_catchup_folders($options{workpath}, $options{datelimit}))};
    }
    elsif ($options{method} =~ m/^single$/) {
        @inputfolders = @{(check_single_folder($options{workpath}))};
    }

    my $dbh = sql_connect();

    sql_drop_tables($dbh) if (defined $options{delete});
    sql_create_tables($dbh);

    my %generic_table = (
        rebuild => 1,
        date    => DateTime->now(),
        current => 0,
        total   => 0,
       );

    #loop through each build folder one by one
    for my $index (0 .. $#inputfolders) {
        my $inputfolder = $inputfolders[$index];
        $generic_table{current} = $index+1;
        $generic_table{total} = $#inputfolders+1;

        print "Processing $inputfolder...\n";
        sql_update_progress($dbh, \%generic_table);
        my $statefile = catfile($inputfolder, $BUILDSTATEFILE);
        my $mainlogfile = catfile($inputfolder, $BUILDLOGFILE);

        next if (!check_exists_and_openable ($statefile));
        next if (!check_exists_and_openable ($mainlogfile));

        print "Needed main log files exists.\n";

        my $modify_time = epoch_s_to_iso(get_modify_time($statefile));
        my $statehash = read_json(uncompress_to_scalar($statefile));
        my $logcontent = uncompress_to_scalar($mainlogfile);

        my %datahash = %{read_build_data($statehash, $inputfolder)};
        $datahash{TIMESTAMP} = $modify_time if ($datahash{TIMESTAMP} eq "");

        print "Build Summary:\n";
        print "Name: $datahash{FULLDISPLAYNAME}\n";
        print "Build number: $datahash{BUILD_NUMBER}\n";
        print "Result: $datahash{RESULT}\n";
        print "Build date: $datahash{TIMESTAMP}\n";
        sql($dbh, \%options, \%datahash);
        print "$inputfolder processed.\n\n";
    }
    $generic_table{rebuild} = 0;
    sql_update_progress($dbh, \%generic_table);
    sql_disconnect($dbh);
    return;
}
run( @ARGV ) unless caller;
