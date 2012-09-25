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

# If called with `--daemonize', fork and output only to log
if [ "x$1" = "x--daemonize" ]; then
    shift
    "$0" "$@" >/dev/null 2>&1 &
    exit 0
fi

PULSEDIR=$HOME/pulse
cd $PULSEDIR

# Make sure the server always has $HOME/bin at front of PATH
# This is for the git wrapper, hook scripts, etc.
if ! echo $PATH | egrep -q "^$HOME/bin:"; then
    # Remove any existing instance of $HOME/bin
    OLD_PATH=$PATH
    PATH=$(echo $PATH | sed -r -e "s|:$HOME/bin||")
    PATH="$HOME/bin:$PATH"
    echo "Modified PATH from $OLD_PATH to $PATH" | logger -s -t pulseserver
    export PATH
fi

# Extra options for the JVM.
JAVA_OPTS="\
    -Dpulse.enable.request.logging=true \
    -Dpulse.jetty.max.threads=1024 \
    -Xmx2048m \
    -Dcom.sun.management.jmxremote=true \
    -Dcom.sun.management.jmxremote.port=8199 \
    -Dcom.sun.management.jmxremote.ssl=false \
    -Dcom.sun.management.jmxremote.authenticate=false \
    -verbose:gc \
    -XX:+PrintGCTimeStamps \
    -XX:+PrintGCDetails \
    -Xloggc:/home/pulseserver/pulse/logs/gc.log"
export JAVA_OPTS

sleepint=8
maxsleep=1800
while true; do
    $PULSEDIR/bin/pulse start 2>&1 | logger -s -t pulseserver

    # It's always an error if Pulse stops, unless we're shutting down.
    # We'll try hard to restart Pulse.
    if test $sleepint -gt $maxsleep; then
        echo "Pulse server failed too often, giving up" | logger -s -t pulseserver
        exit 2
    fi

    echo "Pulse server exited unexpectedly! Will attempt restart in $sleepint seconds" | logger -s -t pulseserver
    sleep $sleepint
    sleepint=$(expr $sleepint \* 2)
done

