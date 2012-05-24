#!/usr/bin/env perl
use strict;
use warnings;

# This script syncs this repo to latest and does a single puppet run.
# Intended to be used from a cron job.
#
# Important: do not use non-core perl modules here!
# Most things listed in "perldoc perlmodlib" should be safe.

use Carp;
use Cwd qw( abs_path );
use FindBin;
use English qw( -no_match_vars );

our $DIR = abs_path( $FindBin::Bin );
our $WINDOWS = ($OSNAME =~ m{win32}i);

# Avoid any usage of the git_mirror.pl from within puppet
local $ENV{ HARDGIT_SKIP } = 1;

sub chdir_or_die
{
    my ($dir) = @_;

    chdir( $dir ) || die "chdir $dir: $!";

    return;
}

sub system_or_carp
{
    my (@cmd) = @_;

    system( @cmd );
    if ($? != 0) {
        carp "system @cmd: $?";
    }

    return;
}

sub update_git_dir
{
    my ($gitdir) = @_;

    return unless (-d $gitdir);

    chdir_or_die( $gitdir );

    # If our git repo has somehow become out of sync, these commands will warn about it.
    # Note that we warn, instead of dying, because puppet should still run if at all possible
    # (e.g. puppet should still run if the git server is down).
    system_or_carp( qw(git pull -q) );
    system_or_carp( qw(git --no-pager diff) );

    chdir_or_die( $DIR );

    return;
}

sub find_puppet
{
    return 'puppet' unless $WINDOWS;

    foreach my $key ('ProgramFiles', 'ProgramFiles(x86)') {
        my $path = $ENV{ $key };
        next unless $path;

        my $candidate = "$path\\Puppet Labs\\Puppet\\bin\\puppet.bat";
        if (-f $candidate) {
            return $candidate;
        }
    }

    return 'puppet';
}

sub run_and_exit
{
    my (@cmd) = @_;

    if (!$WINDOWS) {
        exec @cmd;
        die "exec @cmd: $!";
    }

    # On Windows, we avoid exec because it effectively "detaches" (gets a new
    # PID and, if run from a console, returns control to the console).
    system( @cmd );
    exit $?;
}

sub run_puppet
{
    my @puppet_command = (
        find_puppet( ),
    );

    if ($WINDOWS) {
        push @puppet_command, (
            # 'apply' is the command we want; we just don't use it on platforms
            # other than Windows because we still have some very old puppet
            # installations which don't support it.
            'apply',

            # avoid color because puppet on Windows doesn't automatically turn
            # it off when not at a console
            '--color=false',

            # On Windows, we need to use ';' instead of ':' in the module path,
            # which means the entry in $confdir/puppet.conf doesn't
            # work (or we'd need a different puppet.conf for Windows vs elsewhere)
            '--modulepath', "$DIR/private/modules;$DIR/modules",
        );
    } else {
        push @puppet_command, (
            '--logdest', 'syslog',
        );
    }

    push @puppet_command, (
        '--confdir', $DIR,
        "$DIR/manifests/site.pp",
    );

    run_and_exit( @puppet_command );

    return;
}

sub run
{
    chdir_or_die( $DIR );

    if (-f 'disable_puppet') {
        print "not doing anything because $DIR/disable_puppet exists\n";
        return;
    }

    for my $gitdir ($DIR, "$DIR/private") {
        update_git_dir( $gitdir );
    }

    run_puppet( );

    return;
}

run( ) unless caller;
1;

