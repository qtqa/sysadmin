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

our $DIR = abs_path( $FindBin::Bin );

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
    system_or_carp( qw(git diff) );

    chdir_or_die( $DIR );

    return;
}

sub run_puppet
{
    exec(
        'puppet',
        '--confdir', $DIR,
        @ARGV,
        '--logdest', 'syslog',
        "$DIR/manifests/site.pp",
    );

    die "exec: $!";
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

