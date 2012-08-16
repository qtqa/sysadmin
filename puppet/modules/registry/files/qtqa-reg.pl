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

=head1 NAME

qtqa-reg.pl - manage registry values

=head1 SYNOPSIS

  perl qtqa-reg.pl <command> -path Some\Registry\Path [-data "some data"] [-type sometype]

Check, delete or create a registry value.

=head2 OPTIONS

=over

=item -path PATH

Specifies the path of the registry value.

In registry terminology, this should consist of the "key" and the "value" in a single
string, separated by a backslash; for example, 'HKEY_CURRENT_USER\Control Panel\Sound\Beep'.

=item -data DATA

Specifies the desired data for the registry value.

=item -type TYPE

Specifies the desired type for the registry value.

This should be a string of the form displayed in "regedit", e.g. "REG_SZ" for a string.

The type affects how the given data is parsed.
For example, "0x00000001" is interpreted as a literal string if the REG_SZ type is used,
or interpreted as an integer of value 1 if the REG_DWORD type is used.

=back

=head2 COMMANDS

=over

=item check

Check if the given registry value exists.

The data and type of the value are checked if and only if the -data and -type parameters are used.

Exits with a zero exit code if the check succeeds, non-zero otherwise.

This is designed to be called from an 'onlyif' or 'unless' parameter within a puppet Exec type.

=item write

Write the given registry value. -data and -type parameters are mandatory.

=item delete

Delete the given registry value. -data and -type parameters are ignored.

=back

=cut

use strict;
use warnings;

use Getopt::Long;
use Pod::Usage;
use Win32::TieRegistry( ArrayValues => 1 );
use Win32API::Registry;

# Given a registry type string (e.g. 'REG_SZ'), returns the integer
# constant for that string, or dies if the string is not valid.
sub parse_type
{
    my ($typestr) = @_;

    if ($typestr !~ m{\AREG_}) {
        die "'$typestr' is not a valid type string";
    }

    # each valid REG_ constant is available in the Win32API::Registry package
    my $sub = Win32API::Registry->can( $typestr );
    if (!$sub) {
        die "'$typestr' is not a known type";
    }

    return $sub->();
}

# Given a path string, returns a hashref of the decomposed paths.
# Dies on error.
#
# The input should be a single string referring to a Registry value, e.g.:
#    HKEY_CURRENT_USER\Control Panel\Sound\Beep
#
# The returned hashref has the following keys:
#    key => the 'key' part of the path only (e.g. 'HKEY_CURRENT_USER\Control Panel\Sound')
#    value => the 'value' part of the path only (e.g. 'Beep')
#    lookup => a lookup key for the value, usable with Win32::TieRegistry.
#
# The abbreviations used by the puppetlabs-registry module are also supported here
# (e.g. 'HKU' for 'HKEY_USERS').
#
# When referring to HKEY_USERS, it is permitted to use a username rather than a SID in the
# first part of the path. In this case, this function will replace the username with the
# appropriate SID. This is intended to match the logic discussed on
# http://projects.puppetlabs.com/issues/14555, for forward-compatibility with the
# puppetlabs-registry module.
#
# Example:
#
#   parse_path( 'HKU\testuser\Control Panel\Desktop\CursorBlinkRate' )
#
# returns:
#
#   {
#      key => 'HKEY_USERS\S-1-5-21-2428153592-2434233159-1299285348-1000\Control Panel\Desktop',
#      value => 'CursorBlinkRate',
#      lookup => 'HKEY_USERS\S-1-5-21-2428153592-2434233159-1299285348-1000\Control Panel\Desktop\\CursorBlinkRate',
#   }
#
sub parse_path
{
    my ($path) = @_;
    if ($path !~
        m{
            \A
            ([^\\]+)
            \\
            (.+?)
            \\
            ([^\\]+)
            \z
        }xms
    ) {
        die "'$path' is not recognized as a valid path";
    }

    my $hive = $1;
    my $key = $2;
    my $value = $3;

    # replace some aliases
    my %alias = (
        hku => 'HKEY_USERS',
        hklm => 'HKEY_LOCAL_MACHINE',
        hkcc => 'HKEY_CURRENT_CONFIG',
        hkcu => 'HKEY_CURRENT_USER',
        hkcr => 'HKEY_CLASSES_ROOT',
    );
    if (my $replace = $alias{ lc $hive }) {
        $hive = $replace;
    }

    # replace username with SID
    if ($hive eq 'HKEY_USERS') {
        my ($user, $rest) = split(/\\/, $key, 2);
        if ($user !~ m{\AS-[0-9\-]+\z}) {
            my $sid = qx(wmic path win32_useraccount where 'name="$user"' get SID);
            if ($?) {
                die "Can't get SID for user $user: wmic exited with status $?";
            }
            if ($sid !~ m{(S-[0-9\-]+)}) {
                die "Can't find SID in wmic output (for $user)";
            }
            $user = $1;
        }
        $key = "$user\\$rest";
    }

    return {
        key => "$hive\\$key",
        value => $value,
        # Win32::TieRegistry lookup style; key and value separated
        # by double-delimiter
        lookup => "$hive\\$key\\\\$value",
    };
}

# Die if a registry value is not as expected.
# 'path' is mandatory. 'data' and 'type' are optional.
sub reg_check
{
    my (%args) = @_;
    my $path = $args{ path };
    my $data = $args{ data };
    my $type = $args{ type };

    my $lookup = $path->{ lookup };
    my @got = @{ $Registry->{ $lookup } || [] };
    @got || die "$lookup does not exist\n";

    if (defined($data) && $data ne $got[0]) {
        die "have data: '$got[0]', want data: '$data'\n";
    }
    if (defined($type) && $type ne $got[1]) {
        die "have type: '$got[1]', want type: '$type'\n";
    }

    print "$lookup looks OK.\n";
    return;
}

# Delete a registry value, or die on error.
# 'path' is mandatory, other arguments are ignored.
sub reg_delete
{
    my (%args) = @_;
    my $lookup = $args{ path }{ lookup };

    if (not exists $Registry->{ $lookup }) {
        print "$lookup does not exist - nothing to do.\n";
        return;
    }

    $Registry->AllowSave(1) || die "Can't get write access to registry: $!";
    delete $Registry->{ $lookup };
    undef $Registry;
    print "Deleted $lookup.\n";

    return;
}

# Write a registry value, or die on error.
# An existing value at the given path will be overwritten.
# 'path', 'data' and 'type' are all mandatory.
sub reg_write
{
    my (%args) = @_;
    my $lookup = $args{ path }{ lookup };
    my $data = $args{ data };
    my $type = $args{ type };

    $Registry->AllowSave(1) || die "Can't get write access to registry: $!";

    # Note, we must ensure all intermediate keys exist (they cannot be
    # created automatically by a single dereference)
    my $part = q{};
    while ($args{ path }{ key } =~ m{((?:\\)?[^\\]+)}g) {
        $part .= $1;
        if (! exists $Registry->{ $part }) {
            $Registry->{ $part } = {};
            print "Created empty $part\n";
        }
    }

    $Registry->{ $lookup } = [ $data, $type ];
    undef $Registry;

    print "Wrote $lookup.\n";

    return;
}

# Main entry point
sub run
{
    my $mode = shift @ARGV;

    if (!$mode || $mode =~ m{\A-}) {
        pod2usage(1);
    }

    my $path;
    my $data;
    my $type;

    GetOptions(
        'path=s' => \$path,
        'data=s' => \$data,
        'type=s' => \$type,
    );

    $path || die "Missing mandatory -path option\n";
    $path = parse_path( $path );

    if ($mode eq 'delete') {
        return reg_delete( path => $path );
    }

    if ($type) {
        $type = parse_type( $type );
    }

    if ($mode eq 'check') {
        return reg_check( path => $path, data => $data, type => $type );
    }

    $data || die "Missing mandatory -data option\n";
    $type || die "Missing mandatory -type option\n";

    if ($mode eq 'write') {
        return reg_write( path => $path, data => $data, type => $type );
    }

    die "Unknown operation '$mode'\n";
}

run() unless caller;
1;
