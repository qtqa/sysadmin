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
use 5.010;

=head1 NAME

local-lib-bootstrap.pl - download and install local::lib into $HOME/perl5 on mac

=head1 SYNOPSIS

  # install local::lib version 1.008004 into $HOME/perl5
  $ ./local-lib-bootstrap.pl 1.008004

This is a simple script to automate bootstrapping of local::lib into $HOME/perl5.
Currently it is intended for use on mac (only), as other platforms should have a more
robust way to install local::lib (e.g. the system package manager).

This script absolutely _must not_ use any perl module which is not available by default
with the system perl 5.10 install available on OSX 10.6.

=cut

use Carp;
use LWP::Simple;
use File::Temp qw( tempdir );
use English    qw( -no_match_vars );
use Pod::Usage qw( pod2usage );

# Fetch $remote to $local, robustly
sub fetch
{
    my ($remote, $local) = @_;

    my $tries = 8;
    my $delay = 2;

    while ($tries) {
        my $response = getstore( $remote, $local );
        return if (is_success( $response ));

        print STDERR "downloading $remote failed: $response\n";

        if (--$tries) {
            print STDERR "Will retry in $delay seconds\n";
            sleep $delay;
            $delay = $delay*2;
        }
    }

    croak "downloading $remote failed after repeated attempts\n";
}

sub system_or_die
{
    my (@cmd) = @_;

    print "+ @cmd\n";

    my $status = system( @cmd );
    if ($status != 0) {
        confess "command @cmd exited with status $status";
    }

    return;
}

sub main
{
    if (scalar(@ARGV) != 1) {
        pod2usage( 1 );
    }

    my $VERSION  = shift @ARGV;
    my $BASENAME = "local-lib-$VERSION";
    my $TARBALL  = "$BASENAME.tar.gz";
    my $URL      = "http://search.cpan.org/CPAN/authors/id/A/AP/APEIRON/$TARBALL";

    my $tempdir = tempdir( 'local-lib-bootstrap.XXXXXX', CLEANUP => 1, TMPDIR => 1 );

    chdir( $tempdir ) || die "chdir $tempdir: $!";

    print "Downloading $URL to $TARBALL ...\n";
    fetch( $URL, "$TARBALL" );

    print "Extracting and building ...\n";
    system_or_die( 'tar', '-xvzf', $TARBALL );
    chdir( $BASENAME ) || die "chdir $BASENAME: $!";
    system_or_die( $EXECUTABLE_NAME, 'Makefile.PL', '--bootstrap' );
    system_or_die( 'make' );
    system_or_die( 'make', 'install' );

    print "Done :)\n";

    # Need to chdir out of the tempdir, otherwise it can't be cleaned up
    chdir( '/' ) || die "chdir /: $!";

    return;
}

main if (!caller);
1;
