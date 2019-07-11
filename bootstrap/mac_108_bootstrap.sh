#!/bin/sh
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

# Bootstrap a clean OSX 10.8 system to be managed by puppet.
# NOTE: because OSX does not ship with a compiler by default, and installing
# puppet via macports needs a compiler, this script may be a bit prone
# to failure if you do not install gcc and macports yourself first.

REPO="$1"
if [ "x$REPO" = "x" ]; then
    {
        echo "Usage: $(basename $0) git://some/git/repo [http://some/input/location]"
        echo ""
        echo "Set up this machine to be managed using the puppet config in the given"
        echo "git repository (e.g. git://code.qt.io/qtqa/sysadmin.git) and optional"
        echo "http location for downloads (e.g., http://ci-files01-hki.ci.local/input/mac)"
    } 1>&2
    exit 2
fi

INPUT="$2"
if [ "x$INPUT" = "x" ]; then
   INPUT=http://ci-files01-hki.ci.local/input/mac
fi

WORKDIR=$HOME/bootstrap_tmp

set -e
#set -x

mkdir -p $WORKDIR
cd $WORKDIR

# Ensures xcode is installed.
# xcode is required for using macports.
if ! test -e /Applications/Xcode.app;  then
    echo Installing xcode...
    curl $INPUT/xcode_4.5.2_mountain_lion.dmg -o xcode.dmg
    hdiutil attach ./xcode.dmg
    cp -R /Volumes/Xcode/Xcode.app /Applications/Xcode.app
    # These pkgs are normally installed when you launch XCode first time
    # Install them here to make sure XCode installation is similar as normal users
    installer -pkg /Applications/Xcode.app/Contents/Resources/Packages/MobileDevice.pkg -target /
    installer -pkg /Applications/Xcode.app/Contents/Resources/Packages/MobileDeviceDevelopment.pkg -target /
    hdiutil detach /Volumes/Xcode
else
    echo xcode is already installed
fi

# Ensures xcode command line tools are installed.
if ! gcc -v > /dev/null 2>&1; then
    echo Installing xcode cltools...
    curl $INPUT/xcode_4.5.2_cltools_mountain_lion.dmg -o xcode_cltools.dmg
    hdiutil attach ./xcode_cltools.dmg
    installer -pkg /Volumes/Command\ Line\ Tools\ \(Mountain\ Lion\)/Command\ Line\ Tools\ \(Mountain\ Lion\).mpkg -target /
    hdiutil detach /Volumes/Command\ Line\ Tools\ \(Mountain\ Lion\)
    # User must accept XCode license agreement before macports can be successfully used
    xcodebuild -license
else
    echo xcode cltools is already installed
fi

# Ensures macports is installed.
# macports is required for installing puppet.
if ! test -e /opt/local/bin/port; then
    echo Installing macports...
    curl $INPUT/MacPorts-2.1.2-10.8-MountainLion.pkg -o macports.pkg
    installer -pkg ./macports.pkg -target /
    /opt/local/bin/port -v selfupdate
    /opt/local/bin/port sync
else
    echo macports is already installed
fi

# Ensures puppet is installed.
if ! test -e /opt/local/bin/puppet; then
    echo Installing puppet...
    /opt/local/bin/port install puppet
else
    echo puppet is already installed
fi

# Ensures git is installed.
if ! test -e /opt/local/bin/git; then
    echo Installing git...
    /opt/local/bin/port install git-core
else
    echo git is already installed
fi

# Ensures perl is installed.
if ! test -e /opt/local/bin/perl; then
    echo Installing perl...
    /opt/local/bin/port install perl5
else
    echo perl is already installed
fi

if ! test -d /var/qtqa/sysadmin; then
    echo "Grabbing $REPO ..."
    mkdir -p /var/qtqa
    git clone "$REPO" /var/qtqa/sysadmin
fi

echo "Configuring this node..."
/usr/bin/env PATH=/opt/local/bin:$PATH /var/qtqa/sysadmin/puppet/nodecfg.pl -interactive

# Run puppet once.
# From this point on, all setup of this machine is done via puppet.
echo "Running puppet..."
/usr/bin/env PATH=/opt/local/bin:$PATH /var/qtqa/sysadmin/puppet/sync_and_run.pl
set +x
echo 'All done :-)'
