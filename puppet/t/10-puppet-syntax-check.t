#!/usr/bin/env perl
#############################################################################
##
## Copyright (C) 2012 Nokia Corporation and/or its subsidiary(-ies).
## Contact: http://www.qt-project.org/
##
## $QT_BEGIN_LICENSE:LGPL$
## GNU Lesser General Public License Usage
## This file may be used under the terms of the GNU Lesser General Public
## License version 2.1 as published by the Free Software Foundation and
## appearing in the file LICENSE.LGPL included in the packaging of this
## file. Please review the following information to ensure the GNU Lesser
## General Public License version 2.1 requirements will be met:
## http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
##
## In addition, as a special exception, Nokia gives you certain additional
## rights. These rights are described in the Nokia Qt LGPL Exception
## version 1.1, included in the file LGPL_EXCEPTION.txt in this package.
##
## GNU General Public License Usage
## Alternatively, this file may be used under the terms of the GNU General
## Public License version 3.0 as published by the Free Software Foundation
## and appearing in the file LICENSE.GPL included in the packaging of this
## file. Please review the following information to ensure the GNU General
## Public License version 3.0 requirements will be met:
## http://www.gnu.org/copyleft/gpl.html.
##
## Other Usage
## Alternatively, this file may be used in accordance with the terms and
## conditions contained in a signed written agreement between you and Nokia.
##
##
##
##
##
##
## $QT_END_LICENSE$
##
#############################################################################
use strict;
use warnings;

use Capture::Tiny qw( capture_merged );
use English qw( -no_match_vars );
use File::Find::Rule;
use File::Spec::Functions;
use File::chdir;
use FindBin;
use List::MoreUtils qw( natatime );
use Test::More;

BEGIN {
    do( catfile( $FindBin::Bin, '..', 'sync_and_run.pl' ) )
        || die "load sync_and_run.pl: $! $@";
    QtQA::Puppet::SyncAndRun->import();

    # $DIR should point to sync_and_run.pl's directory, not this directory
    $QtQA::Puppet::SyncAndRun::DIR = catfile( $FindBin::Bin, '..' );
}

# puppet command used to parse (and not execute) a puppet manifest.
# Consists of the first part of the command; additional options may be appended
# by the caller.
my @PUPPET_PARSE_CMD = puppet_parse_cmd();

sub puppet_parse_cmd
{
    my $puppet = find_puppet();

    my $version = qx("$puppet" --version);
    if ($? != 0) {
        die "'$puppet --version' exited with status $?";
    }

    chomp $version;

    if ($version !~ m{\A(\d+)\.(\d+)\.}) {
        die "Can't parse output from '$puppet --version': $version";
    }

    my ($maj, $min) = ($1, $2);

    # puppet 2.7 and later use 'puppet parser validate';
    # earlier use 'puppet apply --parseonly'
    if ($maj > 2 || ($maj == 2 && $min >= 7)) {
        return ($puppet, 'parser', 'validate');
    }

    return ($puppet, 'apply', '--parseonly');
}

# Returns a list of all .pp files in the current git repository.
sub find_all_pp_files
{
    local $CWD = $QtQA::Puppet::SyncAndRun::DIR;
    my @files = qx(git ls-files -- *.pp);
    if ($? != 0) {
        die "'git ls-files' exited with status $?";
    }

    chomp @files;
    if (@files < 10) {
        local $LIST_SEPARATOR = "\n";
        die "found too few files, something must be wrong.\nfiles: @files";
    }
    return sort @files;
}

# Validate syntax of one or more puppet files.
sub validate_some_pp_files
{
    my (@filenames) = @_;
    return unless @filenames;

    my @cmd_base = (
        @PUPPET_PARSE_CMD,
        '--color',
        'false',
        '--confdir',
        '.',
        ($OSNAME =~ m{win32}i ? ('--config', 'puppet-win32.conf') : ()),
        # we do _not_ want to use the modulepath from the config file (including private/)
        # because the test of this repository should not depend on the content of some other
        # site-specific repository
        '--modulepath',
        'modules',
        'validate',
    );

    # Try to validate all files in a single command ...
    my $status;
    my $output = capture_merged {
        $status = system( @cmd_base, @filenames );
    };

    if (@filenames == 1) {
        is( $status, 0, "$filenames[0] parse OK [single]" ) || diag( $output );
        return;
    }

    # if we passed, great! All the files are OK.
    if ($status == 0) {
        foreach my $filename (@filenames) {
            pass( "$filename parse OK [multi]" );
        }
        return;
    }

    # If we failed, split the files into two chunks and test each chunk separately.
    # The intent is to minimize the amount of times we run puppet to isolate the
    # failing file(s).
    my $count = scalar(@filenames);
    my $half = $count/2;
    validate_some_pp_files( @filenames[0..($half-1)] );
    validate_some_pp_files( @filenames[$half..($count-1)] );

    return;
}

sub validate_all_pp_files
{
    chdir( $QtQA::Puppet::SyncAndRun::DIR ) || die "chdir $QtQA::Puppet::SyncAndRun::DIR: $!";

    my @pp = find_all_pp_files();
    plan tests => scalar(@pp);

    # process max of 100 files at a time to avoid hitting command line limits
    my $it = natatime( 100, @pp );
    while (my @chunk = $it->()) {
        validate_some_pp_files( @chunk );
    }

    return;
}

validate_all_pp_files();
done_testing();

