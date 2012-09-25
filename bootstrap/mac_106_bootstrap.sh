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

# Bootstrap a clean OSX 10.6 system to be managed by puppet.
# NOTE: because OSX does not ship with a compiler by default, and installing
# puppet via macports needs a compiler, this script may be a bit prone
# to failure if you do not install gcc and macports yourself first.

REPO="$1"
if [ "x$REPO" = "x" ]; then
    {
        echo "Usage: $(basename $0) git://some/git/repo"
        echo ""
        echo "Set up this machine to be managed using the puppet config in the given"
        echo "git repository (e.g. git://qt.gitorious.org/qtqa/sysadmin.git)"
    } 1>&2
    exit 2
fi

WORKDIR=$HOME/bootstrap_tmp
INPUT=http://bq-qastore.apac.nokia.com/public/input/mac

set -e
#set -x

mkdir -p $WORKDIR
cd $WORKDIR

# Ensures xcode is installed.
# xcode is required for using macports.
if ! gcc -v > /dev/null 2>&1; then
    echo Installing xcode...
    curl $INPUT/xcode3210a432.dmg -o xcode.dmg
    hdiutil attach ./xcode.dmg
    installer -pkg /Volumes/Xcode/Xcode.mpkg -target /
    hdiutil detach /Volumes/Xcode/
else
    echo xcode is already installed
fi

# Ensures macports is installed.
# macports is required for installing puppet.
if ! test -e /opt/local/bin/port; then
    echo Installing macports...
    curl $INPUT/MacPorts-1.8.2-10.6-SnowLeopard.dmg -o macports.dmg
    hdiutil attach ./macports.dmg
    installer -pkg /Volumes/MacPorts-1.8.2/MacPorts-1.8.2.pkg -target /
    hdiutil detach /Volumes/MacPorts-1.8.2

    cat >/opt/local/etc/macports/sources.conf <<EOF
rsync://rsync.macports.org/release/ports/ [default]
EOF

    /opt/local/bin/port sync
else
    echo macports is already installed
fi

# Ensures java development headers are installed.
# This used to be a part of OSX, but now has to be installed separately,
# and it is needed by some software in macports.
# See https://trac.macports.org/ticket/26939
if ! test -e /System/Library/Frameworks/JavaVM.framework/Headers/jni.h; then
    curl $INPUT/javadeveloper_10.6_10m3261.dmg -o javadev.dmg
    hdiutil attach ./javadev.dmg
    installer -pkg "/Volumes/Java Developer/JavaDeveloper.pkg" -target /
    hdiutil detach "/Volumes/Java Developer"
else
    echo Java development headers are already installed
fi

# Ensures puppet is installed.
if ! test -e /opt/local/bin/puppet; then
    echo Installing puppet...
    /opt/local/bin/port install puppet
else
    echo puppet is already installed
fi

if ! test -e /opt/local/bin/git; then
    echo Installing git...
    # git was renamed to git-core in macports; allow for either name
    if /opt/local/bin/port info git >/dev/null 2>&1; then
        /opt/local/bin/port install git
    else
        /opt/local/bin/port install git-core
    fi
else
    echo git is already installed
fi

if ! test -d /var/qtqa/sysadmin; then
    echo "Grabbing $REPO ..."
    mkdir -p /var/qtqa
    /opt/local/bin/git clone "$REPO" /var/qtqa/sysadmin
fi

echo "Configuring this node..."
/usr/bin/env PATH=/opt/local/bin:$PATH /var/qtqa/sysadmin/puppet/nodecfg.pl -interactive

# Run puppet once.
# From this point on, all setup of this machine is done via puppet.
echo "Running puppet..."
/usr/bin/env PATH=/opt/local/bin:$PATH /var/qtqa/sysadmin/puppet/sync_and_run.pl
set +x
echo 'All done :-)'
