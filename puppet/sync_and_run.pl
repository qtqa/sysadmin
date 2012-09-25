#!/usr/bin/env perl
#############################################################################
##
## Copyright (C) 2012 Digia Plc and/or its subsidiary(-ies).
## Contact: http://www.qt-project.org/legal
##
## This file is part of the Qt Toolkit.
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

package QtQA::Puppet::SyncAndRun;
use strict;
use warnings;

# This script syncs this repo to latest and does a single puppet run.
# Intended to be used from a cron job.
#
# Important: do not use non-core perl modules here!
# Most things listed in "perldoc perlmodlib" should be safe.

use Carp;
use Cwd qw( abs_path );
use English qw( -no_match_vars );
use File::Path qw( mkpath );
use File::Spec::Functions;
use FindBin;
use Getopt::Long;
use IO::Socket::INET;

# export a few things so that autotests can find and run puppet without
# duplicating our code
use parent 'Exporter';
our @EXPORT = qw(
    find_puppet
);

our $DIR = abs_path( $FindBin::Bin );
our $CACHEDIR = catfile( $DIR, 'cache' );
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

# Prints --help message and exits.
sub usage
{
    warn <<'END_USAGE';
Usage: sync_and_run.pl [OPTIONS]

Update sysadmin repository and run puppet.

Options:

  --help                        Print this help.

  --facts-from-reverse-dns      Override some facter facts according to the results of a reverse
                                DNS lookup on the current host.

                                This is a workaround for systems whose local idea of their FQDN
                                do not match reality (for example, Windows systems who don't set
                                their hostname via DHCP).

                                To improve reliability, the reverse DNS lookup results are cached
                                on disk.  If the lookup fails for any reason, the values from
                                cache are used instead.

                                At least the 'hostname', 'domain' and 'fqdn' facts are affected
                                by this option.

END_USAGE

    exit 2;
}

# Returns the "primary" IP address of this machine, where "primary"
# means the IP address used for outgoing connections to a world-accessible
# host.
# Dies on error.
sub find_primary_ip
{
    # This may be any host with a high likelihood of never going away.
    # 8.8.8.8:53 == Google Public DNS
    my $remotehost = '8.8.8.8';
    my $remoteport = 53;

    my $socket = IO::Socket::INET->new(
        PeerAddr => $remotehost,
        PeerPort => $remoteport,
        Proto => 'tcp',
    );
    if (!$socket) {
        die "connect to $remotehost:$remoteport: $!";
    }

    return $socket->sockhost( );
}

# Returns the "primary" FQDN for this machine, meaning the FQDN
# according to a reverse DNS lookup of the return value of find_primary_ip().
# This function invokes the "nslookup" utility.
# Dies on error.
sub find_primary_fqdn
{
    my ($ip) = find_primary_ip( );

    my $output = qx(nslookup $ip 2>&1);
    if ($? != 0) {
        die "nslookup failed: $?\noutput: $output\n";
    }

    my $name;
    while (
        $output =~ m{
            (?:
                # unix style
                # 113.136.30.172.in-addr.arpa     name = bq-menoetius.apac.nokia.com.
                \Qin-addr.arpa\E
                \s+
                name
                \s*
                =
                \s*
                (?<name>[^\s]+)
                \.
                \s
            )
            |
            (?:
                # windows style
                # Name:    bq-menoetius.apac.nokia.com
                Name:
                \s+
                (?<name>[^\s]+)
                \s
            )
        }xmsg
    ) {
        $name = $+{ name };
    }

    if (!$name) {
        die "fqdn not found by reverse dns. nslookup output:\n$output\n";
    }

    return $name;
}

# Returns a cached value for $key.
# Dies on error or if there is no cached value.
sub cached
{
    my ($key) = @_;
    my $cachefile = catfile( $CACHEDIR, $key );
    open( my $fh, '<', $cachefile ) || die "open $cachefile: $!";

    my $value = <$fh>;
    chomp $value;

    $value || die "$cachefile is empty";

    return $value;
}

# Writes $value to the on-disk cache, under the given $key.
# Dies on error.
sub write_cache
{
    my ($key, $value) = @_;

    if (! -d $CACHEDIR) {
        mkpath( $CACHEDIR );
    }

    my $cachefile = catfile( $CACHEDIR, $key );
    open( my $fh, '>', $cachefile ) || die "open $cachefile for write: $!";
    print $fh "$value\n";

    return;
}

# Returns a value either from $sub_ref->(), or from the cache
# entry for $key.
#
# If $sub_ref succeeds, the calculated value is written to the cache.
# Otherwise, a warning is printed and the value is read from the cache.
#
# Dies if $sub_ref fails and reading from the cache also fails.
sub get_cacheable_value
{
    my ($key, $sub_ref) = @_;

    my $value;

    eval {
        $value = $sub_ref->();
    };

    my $error;
    if (!($error = $@)) {
        eval {
            write_cache( $key, $value );
        };
        if (my $error = $@) {
            warn "while writing $key to cache: $error\n";
        }
        return $value;
    }

    warn "$error\nTrying to use cached $key...\n";

    return cached( $key );
}

# Writes some values to the hashref $env which, if set in the system environment,
# will override some facter facts according to the values returned by a reverse
# DNS lookup.
#
# This is a workaround for machines whose local set hostname and/or domain name
# can't be trusted.  Notably, most Windows machines fall into this category,
# because they generally don't set their hostname via DHCP and may also be
# limited to a host name with max length of 15 characters (for NetBIOS).
sub modify_env_from_rdns
{
    my ($env) = @_;

    my $fqdn;
    eval {
        $fqdn = get_cacheable_value( 'fqdn', \&find_primary_fqdn );
    };
    if (my $error = $@) {
        warn "$error\nWarning: could not set environment from reverse DNS!\n";
        return;
    }

    # 'bq-menoetius.apac.nokia.com' => ('bq-menoetius', 'apac.nokia.com')
    my ($hostname, $domain) = split( /\./, $fqdn, 2 );

    $env->{ FACTER_hostname } = $hostname;
    $env->{ FACTER_domain } = $domain;
    # No need to set FACTER_fqdn, it is calculated from the above two

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

            # On Windows, we need to use a separate config file
            '--config', "$DIR/puppet-win32.conf"
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

sub run_takeown
{
    # Use the takeown command to ensure the current user has rights to the puppet
    # directory (on Windows).
    #
    # Errors are not fatal.
    #
    system( qw(takeown /F . /R /D Y) );

    return;
}

sub run
{
    chdir_or_die( $DIR );

    if (-f 'disable_puppet') {
        print "not doing anything because $DIR/disable_puppet exists\n";
        return;
    }

    if ($WINDOWS) {
        run_takeown( );
    }

    my $facts_from_reverse_dns = 0;
    GetOptions(
        'facts-from-reverse-dns' => \$facts_from_reverse_dns,
        'help|h|?' => \&usage,
    );

    local %ENV = %ENV;
    if ($facts_from_reverse_dns) {
        modify_env_from_rdns( \%ENV );
    }

    for my $gitdir ($DIR, "$DIR/private") {
        update_git_dir( $gitdir );
    }

    run_puppet( );

    return;
}

run( ) unless caller;
1;

