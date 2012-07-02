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

use English qw( -no_match_vars );
use Getopt::Long;
use Pod::Usage;
use Win32::Shortcut;

=head1 NAME

qtqa-manage-lnk.pl - manage Windows shortcuts (lnk files)

=head1 SYNOPSIS

  # create a link with specified properties...
  qtqa-manage-lnk.pl --write Path=c:\test\bin\myscript Arguments=-dothis myshortcut.lnk

  # ... or check that a link exists with these given properties
  qtqa-manage-lnk.pl --check Path=c:\test\bin\myscript Arguments=-dothis myshortcut.lnk

Create or check the content of a Windows shortcut file.

This script is used in the implementation of the baselayout::startup custom puppet type
in this repository.

=head2 OPTIONS

=over

=item --write Key1=Value1 [Key2=Value2 ...] lnkfile

Write all of the specified key/value pairs to the named lnkfile, or die on error.

=item --check Key1=Value1 [Key2=Value2 ...] lnkfile

Check the content of the named lnkfile and die if any of the given key/value pairs don't match
the content of the file.

=back

It is invalid to specify both the --write and --check arguments.

=cut

sub do_check
{
    my ($filename, %data) = @_;

    my $shortcut = Win32::Shortcut->new( );
    $shortcut->Load( $filename ) || die "load $filename failed";

    my @mismatch;
    while (my ($key, $expected_value) = each %data) {
        my $actual_value = $shortcut->{ $key };

        # Windows may munge the case of Path when saving the .lnk.
        # The filesystem is anyway case-insensitive, so ignore case
        # for the comparison.
        if ($key eq 'Path') {
            $actual_value = lc $actual_value;
            $expected_value = lc $expected_value;
        }

        if ($actual_value ne $expected_value) {
            push @mismatch, "$key (wanted: $expected_value, got: $actual_value)\n";
        }
    }

    if (@mismatch) {
        local $LIST_SEPARATOR = "\n  ";
        die "$filename content does not match desired values.\n  @mismatch\n";
    }

    return;
}

sub do_write
{
    my ($filename, %data) = @_;

    my $shortcut = Win32::Shortcut->new( );

    if (-e $filename) {
       $shortcut->Load( $filename ) || die "load $filename failed";
    }

    while (my ($key, $value) = each %data) {
        $shortcut->{ $key } = $value;
    }

    $shortcut->Save( $filename ) || die "save $filename failed";

    return;
}

sub run
{
    my %write;
    my %check;
    my $filename;

    GetOptions(
        'check=s{,}' => \%check,
        'write=s{,}' => \%write,
        'h|help' => sub { pod2usage(1) },
    ) || die;

    if (@ARGV == 1) {
        $filename = shift @ARGV;
    } else {
        local $LIST_SEPARATOR = '] [';
        die "Missing or too many arguments: [@ARGV]";
    }

    if (%check && %write) {
        die "--check and --write options cannot both be set";
    }

    if (%check) {
        return do_check( $filename, %check );
    }

    return do_write( $filename, %write );
}

run( ) unless caller;
1;
