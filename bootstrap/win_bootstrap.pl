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

use strict;
use warnings;

# Bootstrap a clean-ish Windows system to be managed by puppet.
# Prereqs:
#  (1) git is installed
#  (2) perl is installed (preferably strawberry perl)

use English qw( -no_match_vars );
use File::Basename qw( dirname );
use File::Path qw( mkpath );
use File::Spec::Functions qw( catfile tmpdir );
use Getopt::Long;
use LWP::UserAgent;

sub usage
{
    my ($exitcode) = @_;

    warn <<'END_USAGE';
Usage: win_bootstrap.pl [options] git://some/git/repo

Set up this machine to be managed using the puppet config in the given
git repository (e.g. git://qt.gitorious.org/qtqa/sysadmin)

Options:

  --puppet-url <url>    Use the specified puppet installer (.msi)

END_USAGE

    if (defined( $exitcode )) {
        exit $exitcode;
    }

    return;
}

sub system_or_die
{
    my (@cmd) = @_;

    system( @cmd );
    if (my $status = $?) {
        local $LIST_SEPARATOR = '] [';
        die "command exited with status $status: [@cmd]\n";
    }

    return;
}

sub find_puppet
{
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

sub maybe_install_puppet
{
    my ($url) = @_;

    my $puppet = find_puppet( );

    if (0 == system($puppet, '--version')) {
        print "Puppet is already installed :)\n";
        return;
    }

    my $ua = LWP::UserAgent->new( );
    my $msi = catfile( tmpdir( ), 'qtqa_puppet.msi' );

    print "Fetching $url ...\n";
    my $response = $ua->get( $url, ':content_file' => $msi );
    if (!$response->is_success) {
        die "failed!\n".$response->decoded_content( );
    }

    print "Installing $msi ...\n";
    system_or_die(qw(msiexec /qb /i), $msi);

    print "Testing puppet ...\n";
    $puppet = find_puppet( );

    system_or_die($puppet, '--version');

    print "puppet installed OK :)\n";

    return;
}

sub maybe_git_clone
{
    my ($url) = @_;

    my $dest = 'c:\qtqa\sysadmin';

    if (-d $dest) {
        print "$dest already exists :)\n";
        return;
    }

    my $dest_parent = dirname( $dest );
    if (! -d $dest_parent) {
        mkpath( $dest_parent );
    }

    # Avoid usage of git_mirror.pl from bootstrap script
    local $ENV{ HARDGIT_SKIP } = 1;

    print "git clone $url ...\n";
    system_or_die( 'git', 'clone', $url, $dest );

    print "OK!\n";

    return;
}

sub configure
{
    print "Configuring this node...\n";

    system_or_die( 'perl', 'c:\qtqa\sysadmin\puppet\nodecfg.pl', '-interactive' );

    return;
}

sub run_puppet
{
    print "Running puppet...\n";

    my $script = 'c:\qtqa\sysadmin\puppet\sync_and_run.bat';

    system_or_die( $script );

    return;
}

sub run
{
    my $puppet_url = 'https://downloads.puppetlabs.com/windows/puppet-3.0.0rc2.msi';

    GetOptions(
        'h|help|?' => sub { usage( 1 ) },
        'puppet-url=s' => \$puppet_url,
    ) || die $!;

    if (@ARGV != 1) {
        warn "Wrong number of arguments.\n";
        usage( 2 );
    }

    my $repo_url = shift @ARGV;

    maybe_install_puppet( $puppet_url );
    maybe_git_clone( $repo_url );

    configure( );
    run_puppet( );

    print "All done :-)\n";

    return;
}

run( ) unless caller;
1;

