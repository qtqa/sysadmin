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

# Bootstrap a clean CentOS 5 system to be managed by puppet.

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

set -e
set -x

# Ensure DAG repo is present
if ! test -f /etc/yum.repos.d/rpmforge.repo; then
#    yes | rpm -Uvh http://apt.sw.be/redhat/el5/en/i386/rpmforge/RPMS/rpmforge-release-0.3.6-1.el5.rf.i386.rpm
#    yes | rpm -Uvh ftp://ftp.freshrpms.net/pub/dag/redhat/el5/en/x86_64/dag/RPMS/rpmforge-release-0.3.6-1.el5.rf.x86_64.rpm
    yes | rpm -Uvh ftp://ftp.pbone.net/mirror/ftp.sourceforge.net/pub/sourceforge/s/project/ss/sspamm/depencies/RHEL5/rpmforge-release-0.5.1-1.el5.rf.i386.rpm
fi
# Ensure EPEL repo is present
if ! test -f /etc/yum.repos.d/epel.repo; then
#    yes | rpm -Uvh http://download.fedora.redhat.com/pub/epel/5/i386/epel-release-5-3.noarch.rpm
#    yes | rpm -Uvh http://download.fedora.redhat.com/pub/fedora/epel/5/x86_64/epel-release-5-4.noarch.rpm
    yes | rpm -Uvh http://download.fedora.redhat.com/pub/epel/5/i386/epel-release-5-4.noarch.rpm
fi

if ! test -e /usr/bin/puppet; then
    echo Installing puppet...
    yum -y install puppet
else
    echo puppet is already installed
fi

if ! test -e /usr/bin/git; then
    echo Installing git...
    yum -y install git
else
    echo git is already installed
fi

if ! test -d /var/qtqa/sysadmin; then
    echo "Grabbing $REPO ..."
    mkdir -p /var/qtqa
    git clone "$REPO" /var/qtqa/sysadmin
fi

echo "Configuring this node..."
/var/qtqa/sysadmin/puppet/nodecfg.pl -interactive

# Run puppet once.
echo "Running puppet..."
/var/qtqa/sysadmin/puppet/sync_and_run.pl
set +x
echo 'All done :-)'

