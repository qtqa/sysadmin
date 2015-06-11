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

$ ./testparser.pl -method <single|full> workdir [-delete ] [-verbose] [-sqloutput <file>] [-reload] [-limit <DATE>]

Scan through logs for one or several builds in one go.

=head2 OPTIONS

=over

=item -method <method>

Method used in scanning the directories
See L<METHOD> for more information.

=item -delete

Deletes the old database tables before inserting new data.

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

=item B<FULL>

With FULL the script goes through all the build folders and
creates a new database. The workdir should point to the top
level of the hierarchy.
E.g. C</var/results>

=back

=head1 EXAMPLES OF USAGE

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
use POSIX; #to be able to do 'ceil'

my $BUILDSTATEFILE = "state.json.gz";
my $BUILDLOGFILE = "log.txt.gz";
my $VERBOSE = 0;

my %cfg_table = ("linux-g++_shadow-build_Ubuntu_11.10_x86" => { 'host_os' => 'linux',
                                                           'host_version' => 'Ubuntu_11.10',
                                                           'host_arch' => 'x86',
                                                           'host_compiler' => 'g++',
                                                           'target_os' => 'linux',
                                                           'target_version' => 'Ubuntu_11.10',
                                                           'target_architecture' => 'x86',
                                                           'target_compiler' => 'g++',
                                                           'feature' => 'shadow-build',
                                                         },
                    "linux-g++_bin-pkg-config_Ubuntu_11.10_x86" => { 'host_os' => 'linux',
                                                           'host_version' => 'Ubuntu_11.10',
                                                           'host_arch' => 'x86',
                                                           'host_compiler' => 'g++',
                                                           'target_os' => 'linux',
                                                           'target_version' => 'Ubuntu_11.10',
                                                           'target_architecture' => 'x86',
                                                           'target_compiler' => 'g++',
                                                           'feature' => 'pkg-config',
                                                         },
                    "linux-android-g++_Ubuntu_12.04_x64" => { 'host_os' => 'linux',
                                                           'host_version' => 'Ubuntu_12.04',
                                                           'host_arch' => 'x86_64',
                                                           'host_compiler' => 'g++',
                                                           'target_os' => 'android',
                                                           'target_version' => 'android',
                                                           'target_architecture' => 'armeabi-v7a',
                                                           'target_compiler' => 'g++',
                                                           'feature' => '',
                                                         },
                    "linux-g++_no-widgets_Ubuntu_12.04_x64" => { 'host_os' => 'linux',
                                                           'host_version' => 'Ubuntu_12.04',
                                                           'host_arch' => 'x86_64',
                                                           'host_compiler' => 'g++',
                                                           'target_os' => 'linux',
                                                           'target_version' => 'Ubuntu_12.04',
                                                           'target_architecture' => 'x86_64',
                                                           'target_compiler' => 'g++',
                                                           'feature' => 'no-widgets',
                                                         },
                    "linux-g++_no-widgets_Ubuntu_12.04_x64" => { 'host_os' => 'linux',
                                                           'host_version' => 'Ubuntu_12.04',
                                                           'host_arch' => 'x86_64',
                                                           'host_compiler' => 'g++',
                                                           'target_os' => 'linux',
                                                           'target_version' => 'Ubuntu_12.04',
                                                           'target_architecture' => 'x86_64',
                                                           'target_compiler' => 'g++',
                                                           'feature' => 'no-widgets',
                                                         },
                    "linux-g++_static_Ubuntu_12.04_x64" => { 'host_os' => 'linux',
                                                           'host_version' => 'Ubuntu_12.04',
                                                           'host_arch' => 'x86_64',
                                                           'host_compiler' => 'g++',
                                                           'target_os' => 'linux',
                                                           'target_version' => 'Ubuntu_12.04',
                                                           'target_architecture' => 'x86_64',
                                                           'target_compiler' => 'g++',
                                                           'feature' => 'static',
                                                         },
                    "linux-android-g++_Ubuntu_14.04_x64" => { 'host_os' => 'linux',
                                                           'host_version' => 'Ubuntu_14.04',
                                                           'host_arch' => 'x86_64',
                                                           'host_compiler' => 'g++',
                                                           'target_os' => 'android',
                                                           'target_version' => 'android',
                                                           'target_architecture' => 'armeabi-v7a',
                                                           'target_compiler' => 'g++',
                                                           'feature' => '',
                                                         },
                    "linux-android_armeabi-g++_Ubuntu_12.04_x64" => { 'host_os' => 'linux',
                                                           'host_version' => 'Ubuntu_12.04',
                                                           'host_arch' => 'x86_64',
                                                           'host_compiler' => 'g++',
                                                           'target_os' => 'android',
                                                           'target_version' => 'android',
                                                           'target_architecture' => 'armeabi',
                                                           'target_compiler' => 'g++',
                                                           'feature' => '',
                                                         },
                    "linux-android_armeabi-g++_Ubuntu_14.04_x64" => { 'host_os' => 'linux',
                                                           'host_version' => 'Ubuntu_14.04',
                                                           'host_arch' => 'x86_64',
                                                           'host_compiler' => 'g++',
                                                           'target_os' => 'android',
                                                           'target_version' => 'android',
                                                           'target_architecture' => 'armeabi',
                                                           'target_compiler' => 'g++',
                                                           'feature' => '',
                                                         },
                    "linux-g++_developer-build_OpenSuSE_13.1_x64" => { 'host_os' => 'linux',
                                                           'host_version' => 'OpenSuSE_13.1',
                                                           'host_arch' => 'x86_64',
                                                           'host_compiler' => 'g++',
                                                           'target_os' => 'linux',
                                                           'target_version' => 'OpenSuSE_13.1',
                                                           'target_architecture' => 'x86_64',
                                                           'target_compiler' => 'g++',
                                                           'feature' => 'developer_build',
                                                         },
                    "linux-g++_developer-build_qtnamespace_qtlibinfix_RHEL65_x64" => { 'host_os' => 'linux',
                                                           'host_version' => 'RHEL_6.5',
                                                           'host_arch' => 'x86_64',
                                                           'host_compiler' => 'g++',
                                                           'target_os' => 'linux',
                                                           'target_version' => 'RHEL_6.5',
                                                           'target_architecture' => 'x86_64',
                                                           'target_compiler' => 'g++',
                                                           'feature' => 'developer_build, qtnamespace, qtlibinfix',
                                                         },
                    "linux-g++_developer-build_qtnamespace_qtlibinfix_RHEL_6.6_x64" => { 'host_os' => 'linux',
                                                           'host_version' => 'RHEL_6.6',
                                                           'host_arch' => 'x86_64',
                                                           'host_compiler' => 'g++',
                                                           'target_os' => 'linux',
                                                           'target_version' => 'RHEL_6.6',
                                                           'target_architecture' => 'x86_64',
                                                           'target_compiler' => 'g++',
                                                           'feature' => 'developer_build, qtnamespace, qtlibinfix',
                                                         },
                    "linux-g++_no-widgets_Ubuntu_14.04_x64" => { 'host_os' => 'linux',
                                                           'host_version' => 'Ubuntu_14.04',
                                                           'host_arch' => 'x86_64',
                                                           'host_compiler' => 'g++',
                                                           'target_os' => 'linux',
                                                           'target_version' => 'Ubuntu_14.04',
                                                           'target_architecture' => 'x86_64',
                                                           'target_compiler' => 'g++',
                                                           'feature' => 'no-widgets',
                                                         },
                    "linux-g++_shadow-build_Ubuntu_14.04_x64" => { 'host_os' => 'linux',
                                                           'host_version' => 'Ubuntu_14.04',
                                                           'host_arch' => 'x86_64',
                                                           'host_compiler' => 'g++',
                                                           'target_os' => 'linux',
                                                           'target_version' => 'Ubuntu_14.04',
                                                           'target_architecture' => 'x86_64',
                                                           'target_compiler' => 'g++',
                                                           'feature' => 'shadow-build',
                                                         },
                    "linux-g++_static_Ubuntu_14.04_x64" => { 'host_os' => 'linux',
                                                           'host_version' => 'Ubuntu_14.04',
                                                           'host_arch' => 'x86_64',
                                                           'host_compiler' => 'g++',
                                                           'target_os' => 'linux',
                                                           'target_version' => 'Ubuntu_14.04',
                                                           'target_architecture' => 'x86_64',
                                                           'target_compiler' => 'g++',
                                                           'feature' => 'static',
                                                         },
                    "linux-imx6-armv7a_Ubuntu_12.04_x64" => { 'host_os' => 'linux',
                                                           'host_version' => 'Ubuntu_12.04',
                                                           'host_arch' => 'x86_64',
                                                           'host_compiler' => 'g++',
                                                           'target_os' => 'b2qt',
                                                           'target_version' => 'b2qt_1.6',
                                                           'target_architecture' => 'armv7a',
                                                           'target_compiler' => 'g++',
                                                           'feature' => '',
                                                         },
                    "linux-imx6-armv7a_Ubuntu_14.04_x64" => { 'host_os' => 'linux',
                                                           'host_version' => 'Ubuntu_14.04',
                                                           'host_arch' => 'x86_64',
                                                           'host_compiler' => 'g++',
                                                           'target_os' => 'b2qt',
                                                           'target_version' => 'b2qt_1.6',
                                                           'target_architecture' => 'armv7a',
                                                           'target_compiler' => 'g++',
                                                           'feature' => '',
                                                         },
                    "linux-qnx-armv7le_Ubuntu_12.04_x64" => { 'host_os' => 'linux',
                                                           'host_version' => 'Ubuntu_12.04',
                                                           'host_arch' => 'x86_64',
                                                           'host_compiler' => 'g++',
                                                           'target_os' => 'qnx',
                                                           'target_version' => 'QNX_6.6.0',
                                                           'target_architecture' => 'armv7le',
                                                           'target_compiler' => 'g++',
                                                           'feature' => '',
                                                         },
                    "linux-qnx-armv7le_Ubuntu_14.04_x64" => { 'host_os' => 'linux',
                                                           'host_version' => 'Ubuntu_14.04',
                                                           'host_arch' => 'x86_64',
                                                           'host_compiler' => 'g++',
                                                           'target_os' => 'qnx',
                                                           'target_version' => 'QNX_6.6.0',
                                                           'target_architecture' => 'armv7le',
                                                           'target_compiler' => 'g++',
                                                           'feature' => '',
                                                         },
                    "linux-qnx650-armv7le_Ubuntu_12.04_x64" => { 'host_os' => 'linux',
                                                           'host_version' => 'Ubuntu_12.04',
                                                           'host_arch' => 'x86_64',
                                                           'host_compiler' => 'g++',
                                                           'target_os' => 'qnx',
                                                           'target_version' => 'QNX_6.5.0',
                                                           'target_architecture' => 'armv7le',
                                                           'target_compiler' => 'g++',
                                                           'feature' => '',
                                                         },
                    "linux-qnx650-armv7le_Ubuntu_14.04_x64" => { 'host_os' => 'linux',
                                                           'host_version' => 'Ubuntu_14.04',
                                                           'host_arch' => 'x86_64',
                                                           'host_compiler' => 'g++',
                                                           'target_os' => 'qnx',
                                                           'target_version' => 'QNX_6.5.0',
                                                           'target_architecture' => 'armv7le',
                                                           'target_compiler' => 'g++',
                                                           'feature' => '',
                                                         },
                    "linux-qnx-x86_Ubuntu_12.04_x64" => { 'host_os' => 'linux',
                                                           'host_version' => 'Ubuntu_12.04',
                                                           'host_arch' => 'x86_64',
                                                           'host_compiler' => 'g++',
                                                           'target_os' => 'qnx',
                                                           'target_version' => 'QNX_6.5.0',
                                                           'target_architecture' => 'x86',
                                                           'target_compiler' => 'g++',
                                                           'feature' => '',
                                                         },
                    "linux-qnx-x86_Ubuntu_14.04_x64" => { 'host_os' => 'linux',
                                                           'host_version' => 'Ubuntu_14.04',
                                                           'host_arch' => 'x86_64',
                                                           'host_compiler' => 'g++',
                                                           'target_os' => 'qnx',
                                                           'target_version' => 'QNX_6.5.0',
                                                           'target_architecture' => 'x86',
                                                           'target_compiler' => 'g++',
                                                           'feature' => '',
                                                         },
                    "macx-clang_developer-build_qtnamespace_OSX_10.7" => { 'host_os' => 'osx',
                                                           'host_version' => 'OSX_10.7',
                                                           'host_arch' => 'x86_64',
                                                           'host_compiler' => 'clang',
                                                           'target_os' => 'osx',
                                                           'target_version' => 'OSX_10.7',
                                                           'target_architecture' => 'x86_64',
                                                           'target_compiler' => 'clang',
                                                           'feature' => 'developer-build, qtnamespace',
                                                         },
                    "macx-clang_bin-pkg-config_OSX_10.7" => { 'host_os' => 'osx',
                                                           'host_version' => 'OSX_10.7',
                                                           'host_arch' => 'x86_64',
                                                           'host_compiler' => 'clang',
                                                           'target_os' => 'osx',
                                                           'target_version' => 'OSX_10.7',
                                                           'target_architecture' => 'x86_64',
                                                           'target_compiler' => 'clang',
                                                           'feature' => 'pkg-config',
                                                         },
                    "macx-clang_developer-build_OSX_10.10" => { 'host_os' => 'osx',
                                                           'host_version' => 'OSX_10.10',
                                                           'host_arch' => 'x86_64',
                                                           'host_compiler' => 'clang',
                                                           'target_os' => 'osx',
                                                           'target_version' => 'OSX_10.10',
                                                           'target_architecture' => 'x86_64',
                                                           'target_compiler' => 'clang',
                                                           'feature' => 'developer-build',
                                                         },
                    "macx-clang_developer-build_OSX_10.9" => { 'host_os' => 'osx',
                                                           'host_version' => 'OSX_10.9',
                                                           'host_arch' => 'x86_64',
                                                           'host_compiler' => 'clang',
                                                           'target_os' => 'osx',
                                                           'target_version' => 'OSX_10.9',
                                                           'target_architecture' => 'x86_64',
                                                           'target_compiler' => 'clang',
                                                           'feature' => '',
                                                         },
                    "macx-clang_no-framework_OSX_10.8" => { 'host_os' => 'osx',
                                                           'host_version' => 'OSX_10.8',
                                                           'host_arch' => 'x86_64',
                                                           'host_compiler' => 'clang',
                                                           'target_os' => 'osx',
                                                           'target_version' => 'OSX_10.8',
                                                           'target_architecture' => 'x86_64',
                                                           'target_compiler' => 'clang',
                                                           'feature' => '',
                                                         },
                    "macx-clang_static_OSX_10.9" => { 'host_os' => 'osx',
                                                           'host_version' => 'OSX_10.8',
                                                           'host_arch' => 'x86_64',
                                                           'host_compiler' => 'clang',
                                                           'target_os' => 'osx',
                                                           'target_version' => 'OSX_10.8',
                                                           'target_architecture' => 'x86_64',
                                                           'target_compiler' => 'clang',
                                                           'feature' => 'static',
                                                         },
                    "macx-ios-clang_OSX_10.9" => { 'host_os' => 'osx',
                                                           'host_version' => 'OSX_10.8',
                                                           'host_arch' => 'x86_64',
                                                           'host_compiler' => 'clang',
                                                           'target_os' => 'ios',
                                                           'target_version' => 'ios_crap',
                                                           'target_architecture' => 'x86_64',
                                                           'target_compiler' => 'clang',
                                                           'feature' => '',
                                                         },
                    "win32-mingw48_developer-build_qtlibinfix_opengl_Windows_7" => { 'host_os' => 'windows',
                                                           'host_version' => 'Windows_7',
                                                           'host_arch' => 'x86',
                                                           'host_compiler' => 'mingw_4.8.2',
                                                           'target_os' => 'windows',
                                                           'target_version' => 'Windows_7',
                                                           'target_architecture' => 'x86',
                                                           'target_compiler' => 'mingw_4.8.2',
                                                           'feature' => 'developer-build, qtlibinfix, opengl',
                                                         },
                    "win32-mingw491_developer-build_qtlibinfix_opengl_Windows_7" => { 'host_os' => 'windows',
                                                           'host_version' => 'Windows_7',
                                                           'host_arch' => 'x86',
                                                           'host_compiler' => 'mingw_4.9.1',
                                                           'target_os' => 'windows',
                                                           'target_version' => 'Windows_7',
                                                           'target_architecture' => 'x86',
                                                           'target_compiler' => 'mingw_4.9.1',
                                                           'feature' => 'developer-build, qtlibinfix, opengl',
                                                         },
                    "win32-mingw491_developer-build_qtlibinfix_Windows_7" => { 'host_os' => 'windows',
                                                           'host_version' => 'Windows_7',
                                                           'host_arch' => 'x86',
                                                           'host_compiler' => 'mingw_4.9.1',
                                                           'target_os' => 'windows',
                                                           'target_version' => 'Windows_7',
                                                           'target_architecture' => 'x86',
                                                           'target_compiler' => 'mingw_4.9.1',
                                                           'feature' => 'developer-build, qtlibinfix',
                                                         },
                    "win32-msvc2010_bin-pkg-config_Windows_7" => { 'host_os' => 'windows',
                                                           'host_version' => 'Windows_7',
                                                           'host_arch' => 'x86',
                                                           'host_compiler' => 'msvc',
                                                           'target_os' => 'windows',
                                                           'target_version' => 'Windows_7',
                                                           'target_architecture' => 'x86',
                                                           'target_compiler' => 'msvc',
                                                           'feature' => 'pkg-config',
                                                         },
                    "win32-msvc2010_Windows_7" => { 'host_os' => 'windows',
                                                           'host_version' => 'Windows_7',
                                                           'host_arch' => 'x86',
                                                           'host_compiler' => 'msvc',
                                                           'target_os' => 'windows',
                                                           'target_version' => 'Windows_7',
                                                           'target_architecture' => 'x86',
                                                           'target_compiler' => 'msvc',
                                                           'feature' => '',
                                                         },
                    "win32-msvc2010_developer-build_angle_Windows_7" => { 'host_os' => 'windows',
                                                           'host_version' => 'Windows_7',
                                                           'host_arch' => 'x86',
                                                           'host_compiler' => 'msvc',
                                                           'target_os' => 'windows',
                                                           'target_version' => 'Windows_7',
                                                           'target_architecture' => 'x86',
                                                           'target_compiler' => 'msvc',
                                                           'feature' => 'developer-build',
                                                         },
                    "win32-msvc2010_developer-build_qtnamespace_Windows_7" => { 'host_os' => 'windows',
                                                           'host_version' => 'Windows_7',
                                                           'host_arch' => 'x86',
                                                           'host_compiler' => 'msvc',
                                                           'target_os' => 'windows',
                                                           'target_version' => 'Windows_7',
                                                           'target_architecture' => 'x86',
                                                           'target_compiler' => 'msvc',
                                                           'feature' => 'developer-build, qtnamespace',
                                                         },
                    "win32-msvc2010_opengl_dynamic_Windows_7" => { 'host_os' => 'windows',
                                                           'host_version' => 'Windows_7',
                                                           'host_arch' => 'x86',
                                                           'host_compiler' => 'msvc',
                                                           'target_os' => 'windows',
                                                           'target_version' => 'Windows_7',
                                                           'target_architecture' => 'x86',
                                                           'target_compiler' => 'msvc',
                                                           'feature' => 'opengl',
                                                         },
                    "win32-msvc2010_static_Windows_7" => { 'host_os' => 'windows',
                                                           'host_version' => 'Windows_7',
                                                           'host_arch' => 'x86',
                                                           'host_compiler' => 'msvc',
                                                           'target_os' => 'windows',
                                                           'target_version' => 'Windows_7',
                                                           'target_architecture' => 'x86',
                                                           'target_compiler' => 'msvc',
                                                           'feature' => 'static',
                                                         },
                    "win64-msvc2012_developer-build_qtnamespace_Windows_81" => { 'host_os' => 'windows',
                                                           'host_version' => 'Windows_8.1',
                                                           'host_arch' => 'x86_64',
                                                           'host_compiler' => 'msvc',
                                                           'target_os' => 'windows',
                                                           'target_version' => 'Windows_8.1',
                                                           'target_architecture' => 'x86_64',
                                                           'target_compiler' => 'msvc',
                                                           'feature' => 'developer-build, qtnamespace',
                                                         },
                    "win64-msvc2013_developer-build_qtnamespace_Windows_81" => { 'host_os' => 'windows',
                                                           'host_version' => 'Windows_8.1',
                                                           'host_arch' => 'x86_64',
                                                           'host_compiler' => 'msvc',
                                                           'target_os' => 'windows',
                                                           'target_version' => 'Windows_8.1',
                                                           'target_architecture' => 'x86_64',
                                                           'target_compiler' => 'msvc',
                                                           'feature' => 'developer-build, qtnamespace',
                                                         },
                    "wince70embedded-armv4i-msvc2008_Windows_7" => { 'host_os' => 'windows',
                                                           'host_version' => 'Windows_7',
                                                           'host_arch' => 'x86',
                                                           'host_compiler' => 'msvc',
                                                           'target_os' => 'windows_ce',
                                                           'target_version' => 'windows_ce',
                                                           'target_architecture' => 'x86',
                                                           'target_compiler' => 'msvc',
                                                           'feature' => '',
                                                         },
                    "winphone-arm-msvc2013_Windows_81" => { 'host_os' => 'windows',
                                                           'host_version' => 'Windows_8.1',
                                                           'host_arch' => 'x86_64',
                                                           'host_compiler' => 'msvc',
                                                           'target_os' => 'winphone',
                                                           'target_version' => 'winphone',
                                                           'target_architecture' => 'x86_64',
                                                           'target_compiler' => 'msvc',
                                                           'feature' => '',
                                                         },
                    "winrt-x64-msvc2013_Windows_81" => { 'host_os' => 'windows',
                                                           'host_version' => 'Windows_8.1',
                                                           'host_arch' => 'x86_64',
                                                           'host_compiler' => 'msvc',
                                                           'target_os' => 'winrt',
                                                           'target_version' => 'winrt',
                                                           'target_architecture' => 'x86_64',
                                                           'target_compiler' => 'msvc',
                                                           'feature' => '',
                                                         });

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
    if ($options{method} !~ m/^(full|single)$/) {
        print "Unknown method.\n";
        exit 1;
    }

    $options{workpath} = pop(@args);
    if (! -d $options{workpath}) {
        print "Workpath \"$options{workpath}\" not found.\n";
        exit 1;
    }

    if (defined $options{delete}) {
        print "Warning: Deleting current database tables.\n";
        print "You have 5 seconds to abort.\n";
        sleep(5);
    }

    if (defined $options{reload}) {
        if ($options{method} !~ m/^single$/) {
            print "Option -reload can be used only with the 'single' method.\n";
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
    $logdata{INSIGNIFICANT} |= exists_in_array(\@filecontent, qr/^Note: qt.tests.insignificant is set, but the tests succeeded./);
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
    # stores the state if we're between "Testing" and "Totals:", meaning, we're storing autotestdata.
    my $testdata = 0;
    my $testsetname = "";
    foreach my $line (@filecontent) {
        $line =~ s/[\n|\r]$//g;
        $phase = 1 if ($line =~ m/^$RESULTPARTSTR$/);
        $phase = 2 if ($line =~ m/^$RESULTPARTSTR2$/);
        $autotest = 1 if ($line =~ m/#=#.*?#=#\s\>(.*)$/);
        $autotest = 0 if ($line =~ m/#=#.*?#=#\s\<(.*)\s#=# Elapsed (\d+) second\(s\).$/);

        if (1 == $autotest) {
            if ($line =~ m/^QtQA::App::TestRunner: begin (.*):\s\[/) {
                $testsetname = $1;
                next if ($testsetname =~ m/license/);
                next if ($testsetname =~ m/tst_headers/);
                next if ($testsetname =~ m/tst_bic/);
                $testdata = 1;
                # set initial values for a new test set
                if (!$testresults{all_tests}{$testsetname}) {
                    $testresults{all_tests}{$testsetname}{runs} = 0;
                    $testresults{all_tests}{$testsetname}{passed} = 0;
                    $testresults{all_tests}{$testsetname}{failed} = 0;
                    $testresults{all_tests}{$testsetname}{skipped} = 0;
                    $testresults{all_tests}{$testsetname}{blacklisted} = 0;
                    $testresults{all_tests}{$testsetname}{insignificant} = 0;
                    $testresults{all_tests}{$testsetname}{duration} = 0;
                    $testresults{all_tests}{$testsetname}{overall} = 1;             # by default we assume that tests will fail ;)
                }
                $testresults{all_tests}{$testsetname}{runs}++;
                $total_autotests++;
            } elsif ($line =~ m/^QtQA::App::TestRunner: test failed, running again to see if it is flaky/) {
                $testdata = 1;
                $testresults{all_tests}{$testsetname}{runs}++;
            } elsif ($testdata and $line =~ m/^Totals: (\d+) passed, (\d+) failed, (\d+) skipped, (\d+) blacklisted/) {
                $testresults{all_tests}{$testsetname}{passed} = $1;
                $testresults{all_tests}{$testsetname}{failed} = $2;
                $testresults{all_tests}{$testsetname}{skipped} = $3;
                $testresults{all_tests}{$testsetname}{blacklisted} = $4;
            } elsif ($testdata and $line =~ m/^\d+\% tests passed, (\d+) test(?:s)? failed out of (\d+)/) {
                $testresults{all_tests}{$testsetname}{passed} = $2-$1;
                $testresults{all_tests}{$testsetname}{failed} = $1;
            } elsif ($testdata and $line =~ m/^QtQA::App::TestRunner: Process exited due to signal (\d+); dumped core/) {
                $testresults{all_tests}{$testsetname}{overall} = $1;                # use exit code
            } elsif ($testdata and $line =~ m/^QtQA::App::TestRunner: end .*: (.*) seconds, exit code (\d+)/) {
                $testresults{all_tests}{$testsetname}{duration} = ceil($1*10);      # duration in deciseconds
                $testresults{all_tests}{$testsetname}{overall} = $2;                # passed (0)
                $testdata = 0;
            }
        }

        if (2 == $phase) {
            if ($line =~ m/^\s{2}(.*?)\s*(\[insignificant\])*$/) {
                print "Found test case '$1'\n" if $VERBOSE;
                if (!defined $2) {
                    push (@{$testresults{failed_tests}}, $1);
                } elsif ("[insignificant]" eq $2) {
                    push (@{$testresults{insignificant_failed_tests}}, $1);
                    if (exists $testresults{all_tests}{$1}) {
                        $testresults{all_tests}{$1}{insignificant} = 1;
                    }
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
    sql_drop_table($dbh, "testrow_run");
    sql_drop_table($dbh, "testrow");
    sql_drop_table($dbh, "testfunction_run");
    sql_drop_table($dbh, "testfunction");
    sql_drop_table($dbh, "testset_run");
    sql_drop_table($dbh, "testset");
    sql_drop_table($dbh, "phase_run");
    sql_drop_table($dbh, "phase");
    sql_drop_table($dbh, "conf_run");
    sql_drop_table($dbh, "conf");
    sql_drop_table($dbh, "compiler");
    sql_drop_table($dbh, "platform");
    sql_drop_table($dbh, "project_run");
    sql_drop_table($dbh, "project");
    sql_drop_table($dbh, "branch");
    sql_drop_table($dbh, "state");
    sql_drop_table($dbh, "db_status");

}

sub sql_create_tables
{
    my $dbh = shift;
    print "Creating new tables.\n";

    $dbh->{AutoCommit} = 0;  # enable transactions, if possible
    $dbh->{RaiseError} = 1;

    eval {
        $dbh->do (
            "CREATE TABLE IF NOT EXISTS db_status (
                refreshed             TIMESTAMP             NOT NULL,
                refresh_in_progress   BOOL                  NOT NULL,
                logs_current          INT UNSIGNED          NOT NULL,
                logs_total            INT UNSIGNED          NOT NULL
            ) ENGINE MyISAM"
        );

        # gives the db_status table initial values, since only one row is used in this table
        if ("0E0" eq $dbh->do ("SELECT * FROM db_status")) {
            $dbh->do (
                "INSERT IGNORE INTO db_status (refreshed, refresh_in_progress, logs_current, logs_total)
                    VALUES ('2015-05-01 00:00', 0, 0, 0);"
            );
        }

        $dbh->do (
            "CREATE TABLE IF NOT EXISTS branch (
                id                    TINYINT UNSIGNED      NOT NULL  AUTO_INCREMENT,
                name                  VARCHAR(20)           NOT NULL,
                UNIQUE INDEX unique_branch (name),
                CONSTRAINT branch_pk PRIMARY KEY (id)
            ) ENGINE MyISAM"
        );

        $dbh->do (
            "CREATE TABLE IF NOT EXISTS compiler (
                id                    TINYINT UNSIGNED      NOT NULL  AUTO_INCREMENT,
                compiler              VARCHAR(20)           NULL DEFAULT NULL,
                UNIQUE INDEX unique_compiler (compiler),
                CONSTRAINT compiler_pk PRIMARY KEY (id)
            ) ENGINE MyISAM"
        );

        $dbh->do (
            "CREATE TABLE IF NOT EXISTS conf (
                id                    SMALLINT UNSIGNED     NOT NULL  AUTO_INCREMENT,
                host_id               SMALLINT UNSIGNED     NOT NULL,
                target_id             SMALLINT UNSIGNED     NOT NULL,
                host_compiler_id      TINYINT UNSIGNED      NOT NULL,
                target_compiler_id    TINYINT UNSIGNED      NOT NULL,
                name                  VARCHAR(100)          NOT NULL,
                features              VARCHAR(100)          NULL DEFAULT NULL,
                UNIQUE INDEX unique_conf (name),
                CONSTRAINT conf_pk PRIMARY KEY (id)
            ) ENGINE MyISAM"
        );

        $dbh->do (
            "CREATE TABLE IF NOT EXISTS conf_run (
                id                    MEDIUMINT UNSIGNED    NOT NULL  AUTO_INCREMENT,
                conf_id               SMALLINT UNSIGNED     NOT NULL,
                project_run_id        MEDIUMINT UNSIGNED    NOT NULL,
                forcesuccess          BOOL                  NOT NULL,
                insignificant         BOOL                  NOT NULL,
                result                ENUM('SUCCESS','FAILURE','ABORTED','undef')    NOT NULL,
                total_testsets        INT UNSIGNED          NOT NULL,
                timestamp             TIMESTAMP             NOT NULL,
                duration              TIME                  NOT NULL,
                CONSTRAINT conf_run_pk PRIMARY KEY (id)
            ) ENGINE MyISAM"
        );

        $dbh->do (
            "CREATE TABLE IF NOT EXISTS phase (
                id                    TINYINT UNSIGNED      NOT NULL  AUTO_INCREMENT,
                name                  VARCHAR(100)          NOT NULL,
                UNIQUE INDEX unique_phase (name),
                CONSTRAINT phase_pk PRIMARY KEY (id)
            ) ENGINE MyISAM"
        );

        $dbh->do (
            "CREATE TABLE IF NOT EXISTS phase_run (
                id                    MEDIUMINT UNSIGNED    NOT NULL  AUTO_INCREMENT,
                phase_id              TINYINT UNSIGNED      NOT NULL,
                conf_run_id           MEDIUMINT UNSIGNED    NOT NULL,
                start                 TIMESTAMP             NOT NULL,
                end                   TIMESTAMP             NOT NULL,
                CONSTRAINT phase_run_pk PRIMARY KEY (id)
            ) ENGINE MyISAM"
        );

        $dbh->do (
            "CREATE TABLE IF NOT EXISTS platform (
                id                    SMALLINT UNSIGNED     NOT NULL  AUTO_INCREMENT,
                os                    VARCHAR(10)           NOT NULL,
                os_version            VARCHAR(20)           NULL DEFAULT NULL,
                arch                  VARCHAR(20)           NULL DEFAULT NULL,
                UNIQUE INDEX unique_platform (os,os_version,arch),
                CONSTRAINT platform_pk PRIMARY KEY (id)
            ) ENGINE MyISAM"
        );

        $dbh->do (
            "CREATE TABLE IF NOT EXISTS project (
                id                    TINYINT UNSIGNED      NOT NULL  AUTO_INCREMENT,
                name                  VARCHAR(30)           NOT NULL,
                UNIQUE INDEX unique_project (name),
                CONSTRAINT project_pk PRIMARY KEY (id)
            ) ENGINE MyISAM"
        );

        $dbh->do (
            "CREATE TABLE IF NOT EXISTS project_run (
                id                    MEDIUMINT UNSIGNED    NOT NULL  AUTO_INCREMENT,
                project_id            TINYINT UNSIGNED      NOT NULL,
                branch_id             TINYINT UNSIGNED      NOT NULL,
                state_id              TINYINT UNSIGNED      NOT NULL,
                build_number          MEDIUMINT UNSIGNED    NOT NULL,
                result                ENUM('SUCCESS','FAILURE','ABORTED')    NOT NULL,
                timestamp             TIMESTAMP             NOT NULL,
                duration              TIME                  NOT NULL,
                UNIQUE INDEX unique_project_run (project_id,branch_id,state_id,build_number),
                CONSTRAINT project_run_pk PRIMARY KEY (id)
            ) ENGINE MyISAM"
        );

        $dbh->do (
            "CREATE TABLE IF NOT EXISTS state (
                id                    TINYINT UNSIGNED      NOT NULL  AUTO_INCREMENT,
                name                  VARCHAR(30)           NOT NULL,
                UNIQUE INDEX unique_state (name),
                CONSTRAINT state_pk PRIMARY KEY (id)
            ) ENGINE MyISAM"
        );

        $dbh->do (
            "CREATE TABLE IF NOT EXISTS testfunction (
                id                    MEDIUMINT UNSIGNED    NOT NULL  AUTO_INCREMENT,
                testset_id            SMALLINT UNSIGNED     NOT NULL,
                name                  VARCHAR(50)           NOT NULL,
                UNIQUE INDEX unique_testfunction (testset_id,name),
                CONSTRAINT testfunction_pk PRIMARY KEY (id)
            ) ENGINE MyISAM"
        );

        $dbh->do (
            "CREATE TABLE IF NOT EXISTS testfunction_run (
                id                    INT UNSIGNED          NOT NULL  AUTO_INCREMENT,
                testfunction_id       MEDIUMINT UNSIGNED    NOT NULL,
                testset_run_id        INT UNSIGNED          NOT NULL,
                result                ENUM('na','pass','fail','xpass','xfail','skip','bpass','bfail','bxpass','bxfail','bskip')    NOT NULL    DEFAULT 'na',
                duration              SMALLINT UNSIGNED     NOT NULL,
                CONSTRAINT testfunction_run_pk PRIMARY KEY (id)
            ) ENGINE MyISAM"
        );

        $dbh->do (
            "CREATE TABLE IF NOT EXISTS testrow (
                id                    MEDIUMINT UNSIGNED    NOT NULL  AUTO_INCREMENT,
                testfunction_id       MEDIUMINT UNSIGNED    NOT NULL,
                name                  VARCHAR(100)          NOT NULL,
                UNIQUE INDEX unique_testdata (testfunction_id,name),
                CONSTRAINT testrow_pk PRIMARY KEY (id)
            ) ENGINE MyISAM"
        );

        $dbh->do (
            "CREATE TABLE IF NOT EXISTS testrow_run (
                testrow_id            MEDIUMINT UNSIGNED    NOT NULL,
                testfunction_run_id   INT UNSIGNED          NOT NULL,
                result                ENUM('pass','fail','xpass','xfail','skip','bpass','bfail','bxpass','bxfail','bskip')    NOT NULL,
                CONSTRAINT testrow_run_pk PRIMARY KEY (testrow_id,testfunction_run_id)
            ) ENGINE MyISAM"
        );

        $dbh->do (
            "CREATE TABLE IF NOT EXISTS testset (
                id                    SMALLINT UNSIGNED     NOT NULL  AUTO_INCREMENT,
                project_id            TINYINT UNSIGNED      NOT NULL,
                name                  VARCHAR(50)           NOT NULL,
                UNIQUE INDEX unique_testset (project_id,name),
                CONSTRAINT testset_pk PRIMARY KEY (id)
            ) ENGINE MyISAM"
        );

        $dbh->do (
            "CREATE TABLE IF NOT EXISTS testset_run (
                id                    INT UNSIGNED          NOT NULL  AUTO_INCREMENT,
                testset_id            SMALLINT UNSIGNED     NOT NULL,
                conf_run_id           MEDIUMINT UNSIGNED    NOT NULL,
                run                   TINYINT UNSIGNED      NOT NULL,
                result                ENUM('passed','failed','ipassed','ifailed')    NOT NULL,
                duration              SMALLINT UNSIGNED     NOT NULL,
                testcases_passed      SMALLINT UNSIGNED     NOT NULL,
                testcases_failed      SMALLINT UNSIGNED     NOT NULL,
                testcases_skipped     SMALLINT UNSIGNED     NOT NULL,
                testcases_blacklisted SMALLINT UNSIGNED     NOT NULL,
                CONSTRAINT testset_run_pk PRIMARY KEY (id)
            ) ENGINE MyISAM"
        );

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

    # split the full project name into project (anything before first "_"), branch (anything between first and last "_") and state (anything after last "_")
    my ($projectname, $branchname, $statename) = $datahash{FULLDISPLAYNAME} =~ m/^(.*?)_(.*)_(.*)/;

    open(OUTPUT, ($output ? ">>$output" : ">&STDOUT"));

    $dbh->{AutoCommit} = 0;  # enable transactions, if possible
    $dbh->{RaiseError} = 1;
    if (defined $options{reload}) {
        eval {
            # if 'reload' is defined in options, remove possible data from sql database before storing new data
            print "Deleting old data from database.\n";

            my $query =
                "DELETE FROM phase_run
                    WHERE conf_run_id IN (
                        SELECT conf_run.id
                            FROM conf_run
                                INNER JOIN project_run ON conf_run.project_run_id = project_run.id
                                INNER JOIN project ON project_run.project_id = project.id
                                INNER JOIN branch ON project_run.branch_id = branch.id
                                INNER JOIN state ON project_run.state_id = state.id
                            WHERE project.name = \"$projectname\" AND
                                branch.name = \"$branchname\" AND
                                state.name = \"$statename\" AND
                                project_run.build_number = $datahash{BUILD_NUMBER}
                    )";
            print "$query\n" if $VERBOSE or $output;
            $dbh->do ($query) or print "removal of old data in phase_run failed: $!\n" if !$output;

            $query =
                "DELETE FROM testrow_run
                    WHERE testfunction_run_id IN (
                        SELECT testfunction_run.id
                            FROM testfunction_run
                                INNER JOIN testset_run ON testfunction_run.testset_run_id = testset_run.id
                                INNER JOIN conf_run ON testset_run.conf_run_id = conf_run.id
                                INNER JOIN project_run ON conf_run.project_run_id = project_run.id
                                INNER JOIN project ON project_run.project_id = project.id
                                INNER JOIN branch ON project_run.branch_id = branch.id
                                INNER JOIN state ON project_run.state_id = state.id
                            WHERE project.name = \"$projectname\" AND
                                branch.name = \"$branchname\" AND
                                state.name = \"$statename\" AND
                                project_run.build_number = $datahash{BUILD_NUMBER}
                    )";
            print "$query\n" if $VERBOSE or $output;
            $dbh->do ($query) or print "removal of old data in testrow_run failed: $!\n" if !$output;

            $query =
                "DELETE FROM testfunction_run
                    WHERE testset_run_id IN (
                        SELECT testset_run.id
                            FROM testset_run
                                INNER JOIN conf_run ON testset_run.conf_run_id = conf_run.id
                                INNER JOIN project_run ON conf_run.project_run_id = project_run.id
                                INNER JOIN project ON project_run.project_id = project.id
                                INNER JOIN branch ON project_run.branch_id = branch.id
                                INNER JOIN state ON project_run.state_id = state.id
                            WHERE project.name = \"$projectname\" AND
                                branch.name = \"$branchname\" AND
                                state.name = \"$statename\" AND
                                project_run.build_number = $datahash{BUILD_NUMBER}
                    )";
            print "$query\n" if $VERBOSE or $output;
            $dbh->do ($query) or print "removal of old data in testfunction_run failed: $!\n" if !$output;

            $query =
                "DELETE FROM testset_run
                    WHERE conf_run_id IN (
                        SELECT conf_run.id
                            FROM conf_run
                                INNER JOIN project_run ON conf_run.project_run_id = project_run.id
                                INNER JOIN project ON project_run.project_id = project.id
                                INNER JOIN branch ON project_run.branch_id = branch.id
                                INNER JOIN state ON project_run.state_id = state.id
                            WHERE project.name = \"$projectname\" AND
                            branch.name = \"$branchname\" AND
                            state.name = \"$statename\" AND
                            project_run.build_number = $datahash{BUILD_NUMBER}
                    )";
            print "$query\n" if $VERBOSE or $output;
            $dbh->do ($query) or print "removal of old data in testset_run failed: $!\n" if !$output;

            $query =
                "DELETE FROM conf_run
                    WHERE project_run_id IN (
                        SELECT project_run.id
                            FROM project_run
                                WHERE project_run.project_id = (SELECT id FROM project WHERE name = \"$projectname\") AND
                                    project_run.branch_id = (SELECT id FROM branch WHERE name = \"$branchname\") AND
                                    project_run.state_id = (SELECT id FROM state WHERE name = \"$statename\") AND
                                    project_run.build_number = $datahash{BUILD_NUMBER}
                    )";
            print "$query\n" if $VERBOSE or $output;
            $dbh->do ($query) or print "removal of old data in conf_run failed: $!\n" if !$output;

            $query =
                "DELETE FROM project_run
                    WHERE project_id = (SELECT project.id FROM project WHERE project.name = \"$projectname\") AND
                        branch_id = (SELECT branch.id FROM branch WHERE  branch.name = \"$branchname\") AND
                        state_id = (SELECT state.id FROM state WHERE state.name = \"$statename\") AND
                        build_number = $datahash{BUILD_NUMBER}";
            print "$query\n" if $VERBOSE or $output;
            $dbh->do ($query) or print "removal of old data in project_run failed: $!\n" if !$output;

            $dbh->commit;   # commit the changes if we get this far
        };
        if ($@) {
            warn "Transaction aborted because $@";
            eval { $dbh->rollback };
        } else {
            print "Data deleted.\n";
        }
    }

    print "Storing data to database.\n";

    eval {
        # Default timestamp and duration to zero if cannot be read from the log
        my $timestamp = $datahash{TIMESTAMP} ? "\"$datahash{TIMESTAMP}\"" : "0";
        my $duration = $datahash{DURATION} ? "\"$datahash{DURATION}\"" : "0";

        # insert data into project tables

        if ("0E0" eq $dbh->do ("SELECT name FROM project WHERE name = \"$projectname\"")) {
            my $query =
                "INSERT INTO project (name) VALUES (\"$projectname\")";
            print OUTPUT "$query\n" if $VERBOSE or $output;
            $dbh->do ($query) or print "insert into project failed: $!\n" if !$output;
        }

        if ("0E0" eq $dbh->do ("SELECT name FROM branch WHERE name = \"$branchname\"")) {
            my $query =
                "INSERT INTO branch (name) VALUES (\"$branchname\")";
            print OUTPUT "$query\n" if $VERBOSE or $output;
            $dbh->do ($query) or print "insert into branch failed: $!\n" if !$output;
        }

        if ("0E0" eq $dbh->do ("SELECT name FROM state WHERE name = \"$statename\"")) {
            my $query =
                "INSERT INTO state (name) VALUES (\"$statename\")";
            print OUTPUT "$query\n" if $VERBOSE or $output;
            $dbh->do ($query) or print "insert into state failed: $!\n" if !$output;
        }

        my $query =
            "INSERT INTO project_run (project_id, branch_id, state_id, build_number, result, timestamp, duration)
                SELECT project.id, branch.id, state.id, $datahash{BUILD_NUMBER}, \"$datahash{RESULT}\", $timestamp, $duration
                    FROM project, branch, state
                    WHERE project.name = \"$projectname\" AND
                        branch.name = \"$branchname\" AND
                        state.name = \"$statename\"";
        print OUTPUT "$query\n" if $VERBOSE or $output;
        $dbh->do ($query) or print "insert into project_run failed: $!\n" if !$output;

        #insert data into configuration tables

        foreach my $cfg (keys %{$datahash{cfg}}) {
            if (!exists $cfg_table{$cfg}) {
                print OUTPUT "ERROR: Configuration '$cfg' not defined for this parser.\n";
                next;
            }
            my $host_os           = $cfg_table{$cfg}{'host_os'};
            my $host_version      = $cfg_table{$cfg}{'host_version'};
            my $host_arch         = $cfg_table{$cfg}{'host_arch'};
            my $host_compiler     = $cfg_table{$cfg}{'host_compiler'};
            my $target_os         = $cfg_table{$cfg}{'target_os'};
            my $target_version    = $cfg_table{$cfg}{'target_version'};
            my $target_arch       = $cfg_table{$cfg}{'target_architecture'};
            my $target_compiler   = $cfg_table{$cfg}{'target_compiler'};
            my $feature           = $cfg_table{$cfg}{'feature'};

            my $forcesuccess_cfg  = $datahash{cfg}{$cfg}{logdata}{FORCESUCCESS} ? $datahash{cfg}{$cfg}{logdata}{FORCESUCCESS} : 0;
            my $insignificant_cfg = $datahash{cfg}{$cfg}{logdata}{INSIGNIFICANT} ? $datahash{cfg}{$cfg}{logdata}{INSIGNIFICANT} : 0;
            my $total_autotests   = $datahash{cfg}{$cfg}{testresults}{TOTAL_AUTOTESTS} ? $datahash{cfg}{$cfg}{testresults}{TOTAL_AUTOTESTS} : 0;
            my $timestamp_cfg     = $datahash{cfg}{$cfg}{builddata}{TIMESTAMP} ? "\"$datahash{cfg}{$cfg}{builddata}{TIMESTAMP}\"" : "0";
            my $duration_cfg      = $datahash{cfg}{$cfg}{builddata}{DURATION} ? "\"$datahash{cfg}{$cfg}{builddata}{DURATION}\"" : "0";

            if ("0E0" eq $dbh->do ("SELECT os, os_version, arch FROM platform WHERE os = \"$host_os\" AND os_version = \"$host_version\" AND arch = \"$host_arch\"")) {
                my $query =
                    "INSERT INTO platform (os,os_version,arch) VALUES (\"$host_os\",\"$host_version\",\"$host_arch\")";
                print OUTPUT "$query\n" if $VERBOSE or $output;
                $dbh->do ($query) or print "insert into platform failed: $!\n" if !$output;
            }

            if ("0E0" eq $dbh->do ("SELECT os, os_version, arch FROM platform WHERE os = \"$target_os\" AND os_version = \"$target_version\" AND arch = \"$target_arch\"")) {
                my $query =
                    "INSERT INTO platform (os,os_version,arch) VALUES (\"$target_os\",\"$target_version\",\"$target_arch\")";
                print OUTPUT "$query\n" if $VERBOSE or $output;
                $dbh->do ($query) or print "insert into platform failed: $!\n" if !$output;
            }

            if ("0E0" eq $dbh->do ("SELECT compiler FROM compiler WHERE compiler = \"$host_compiler\"")) {
                my $query =
                    "INSERT INTO compiler (compiler) VALUES (\"$host_compiler\")";
                print OUTPUT "$query\n" if $VERBOSE or $output;
                $dbh->do ($query) or print "insert into compiler failed: $!\n" if !$output;
            }

            if ("0E0" eq $dbh->do ("SELECT compiler FROM compiler WHERE compiler = \"$target_compiler\"")) {
                my $query =
                    "INSERT INTO compiler (compiler) VALUES (\"$target_compiler\")";
                print OUTPUT "$query\n" if $VERBOSE or $output;
                $dbh->do ($query) or print "insert into compiler failed: $!\n" if !$output;
            }

           if ("0E0" eq $dbh->do ("SELECT name FROM conf WHERE name = \"$cfg\"")) {
                my $query =
                    "INSERT INTO conf (host_id, target_id, host_compiler_id, target_compiler_id, name, features) VALUES (
                        (SELECT id FROM platform WHERE platform.os = \"$host_os\" AND platform.os_version = \"$host_version\" AND platform.arch = \"$host_arch\"),
                        (SELECT id FROM platform WHERE platform.os = \"$target_os\" AND platform.os_version = \"$target_version\" AND platform.arch = \"$target_arch\"),
                        (SELECT id FROM compiler WHERE compiler.compiler = \"$host_compiler\"),
                        (SELECT id FROM compiler WHERE compiler.compiler = \"$target_compiler\"),
                        \"$cfg\",
                        \"$feature\" )";
                print OUTPUT "$query\")\n" if $VERBOSE or $output;
                $dbh->do ($query) or print "insert into conf failed: $!\n" if !$output;
            }

            my $query =
                "INSERT INTO conf_run (conf_id, project_run_id, forcesuccess, insignificant, result, total_testsets, timestamp, duration)
                    SELECT conf.id, project_run.id, $forcesuccess_cfg, $insignificant_cfg, \"$datahash{cfg}{$cfg}{builddata}{RESULT}\", $total_autotests, $timestamp_cfg, $duration_cfg
                        FROM conf, project_run
                        WHERE conf.name = \"$cfg\" AND
                            project_run.project_id = (SELECT id FROM project WHERE name = \"$projectname\") AND
                            project_run.branch_id = (SELECT id FROM branch WHERE name = \"$branchname\") AND
                            project_run.state_id = (SELECT id FROM state WHERE name = \"$statename\") AND
                            project_run.build_number =  $datahash{BUILD_NUMBER}";
            print OUTPUT "$query\n" if $VERBOSE or $output;
            $dbh->do ($query) or print "insert into conf_run failed: $!\n" if !$output;

            #insert data into test tables

            if (defined $datahash{cfg}{$cfg}{testresults}{all_tests}) {
                my $timestamp_cfg = $datahash{cfg}{$cfg}{builddata}{TIMESTAMP} ? "\"$datahash{cfg}{$cfg}{builddata}{TIMESTAMP}\"" : "0";
                foreach my $test (keys %{$datahash{cfg}{$cfg}{testresults}{all_tests}}) {

                    if ("0E0" eq $dbh->do ("SELECT testset.name FROM testset INNER JOIN project ON testset.project_id = project.id
                        WHERE testset.name = \"$test\" AND project.name = \"$projectname\"")) {
                        my $query =
                            "INSERT INTO testset (project_id, name) SELECT id, \"$test\" FROM project WHERE project.name = \"$projectname\"";
                        print OUTPUT "$query\n" if $VERBOSE or $output;
                        $dbh->do ($query) or print "insert into testset failed: $!\n" if !$output;
                    }

                    my $testset_result;
                    if ($datahash{cfg}{$cfg}{testresults}{all_tests}{$test}{overall} == 0) {
                        $testset_result = $datahash{cfg}{$cfg}{testresults}{all_tests}{$test}{insignificant} ? "\"ipassed\"" : "\"passed\"";
                    } else {
                        $testset_result = $datahash{cfg}{$cfg}{testresults}{all_tests}{$test}{insignificant} ? "\"ifailed\"" : "\"failed\"";
                    }
                    my $query =
                        "INSERT INTO testset_run (testset_id, conf_run_id, run, result, duration, testcases_passed, testcases_failed, testcases_skipped, testcases_blacklisted)
                            SELECT testset.id,
                                    conf_run.id,
                                    $datahash{cfg}{$cfg}{testresults}{all_tests}{$test}{runs},
                                    $testset_result,
                                    $datahash{cfg}{$cfg}{testresults}{all_tests}{$test}{duration},
                                    $datahash{cfg}{$cfg}{testresults}{all_tests}{$test}{passed},
                                    $datahash{cfg}{$cfg}{testresults}{all_tests}{$test}{failed},
                                    $datahash{cfg}{$cfg}{testresults}{all_tests}{$test}{skipped},
                                    $datahash{cfg}{$cfg}{testresults}{all_tests}{$test}{blacklisted}
                                FROM testset, conf_run
                                WHERE testset.name = \"$test\" AND
                                testset.project_id = (SELECT id FROM project WHERE project.name = \"$projectname\") AND
                                conf_run.id = (
                                    SELECT conf_run.id
                                        FROM conf_run
                                            INNER JOIN conf ON conf_run.conf_id = conf.id
                                            INNER JOIN project_run ON conf_run.project_run_id = project_run.id
                                            INNER JOIN project ON project_run.project_id = project.id
                                            INNER JOIN branch ON project_run.branch_id = branch.id
                                            INNER JOIN state ON project_run.state_id = state.id
                                        WHERE conf.name = \"$cfg\" AND
                                            project_run.build_number = $datahash{BUILD_NUMBER} AND
                                            project.name = \"$projectname\" AND
                                            branch.name = \"$branchname\" AND
                                            state.name = \"$statename\" )";
                    print "$cfg - $test\n" if $VERBOSE;
                    print OUTPUT "$query\n" if $VERBOSE or $output;
                    $dbh->do ($query) or print "insert into testset_run failed: $!\n" if !$output;
                }
            }

            #insert data into phase tables

            if (defined $datahash{cfg}{$cfg}{phases}) {
                foreach my $phase (keys(%{$datahash{cfg}{$cfg}{phases}})) {
                    print "$cfg - $phase\n" if $VERBOSE;
                    my $parent = $datahash{cfg}{$cfg}{phases}{$phase}{parent} ? $datahash{cfg}{$cfg}{phases}{$phase}{parent} : "";
                    my $start = $datahash{cfg}{$cfg}{phases}{$phase}{start} ? $datahash{cfg}{$cfg}{phases}{$phase}{start} : "";
                    my $end = $datahash{cfg}{$cfg}{phases}{$phase}{end} ? $datahash{cfg}{$cfg}{phases}{$phase}{end} : "";

                    if ("0E0" eq $dbh->do ("SELECT name FROM phase WHERE name = \"$phase\"")) {
                        my $query =
                            "INSERT INTO phase (name) VALUES (\"$phase\")";
                        print OUTPUT "$query\n" if $VERBOSE or $output;
                        $dbh->do ($query) or print "insert into phase failed: $!\n" if !$output;
                    }

                    my $query =
                        "INSERT INTO phase_run (phase_id, conf_run_id, start, end)
                            SELECT phase.id, conf_run.id, \"$start\", \"$end\"
                                FROM phase, conf_run
                                WHERE phase.name = \"$phase\" AND
                                    conf_run.id = (
                                        SELECT conf_run.id
                                            FROM conf_run
                                                INNER JOIN conf ON conf_run.conf_id = conf.id
                                                INNER JOIN project_run ON conf_run.project_run_id = project_run.id
                                                INNER JOIN project ON project_run.project_id = project.id
                                                INNER JOIN branch ON project_run.branch_id = branch.id
                                                INNER JOIN state ON project_run.state_id = state.id
                                            WHERE conf.name = \"$cfg\" AND
                                                project_run.build_number = $datahash{BUILD_NUMBER} AND
                                                project.name = \"$projectname\" AND
                                                branch.name = \"$branchname\" AND
                                                state.name = \"$statename\" )";
                    print OUTPUT "$query\n" if $VERBOSE or $output;
                    $dbh->do ($query) or print "insert into phase_run failed: $!\n" if !$output;
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

    $dbh->do ("UPDATE db_status SET refreshed=\"$table{date}\", refresh_in_progress=$table{rebuild}, logs_current=$table{current}, logs_total=$table{total};");
}

sub run
{
    my %options = process_arguments(@ARGV);

    my @inputfolders;

    if ($options{method} =~ m/^full$/) {
        @inputfolders = @{(get_all_folders($options{workpath}, $options{datelimit}))};
    }
    elsif ($options{method} =~ m/^single$/) {
        @inputfolders = @{(check_single_folder($options{workpath}))};
    }

    my $dbh = sql_connect();

    sql_drop_tables($dbh) if (defined $options{delete});
    sql_create_tables($dbh);

    my %db_status = (
        date    => DateTime->now(),
        rebuild => 1,
        current => 0,
        total   => 0,
       );

    #loop through each build folder one by one
    for my $index (0 .. $#inputfolders) {
        my $inputfolder = $inputfolders[$index];
        $db_status{current} = $index+1;
        $db_status{total} = $#inputfolders+1;

        print "Processing $inputfolder...\n";
        sql_update_progress($dbh, \%db_status);
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
    $db_status{rebuild} = 0;
    sql_update_progress($dbh, \%db_status);
    sql_disconnect($dbh);
    return;
}
run( @ARGV ) unless caller;
