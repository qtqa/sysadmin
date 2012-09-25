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

if [ "x$PULSEAGENT_IN_SCREEN" != "x1" ] && which screen >/dev/null; then
    echo "pulse-agent.sh launched outside of screen ..." \
        | logger -s -t pulseagent

    PULSEAGENT_IN_SCREEN=1
    export PULSEAGENT_IN_SCREEN

    # Check if a pulseagent screen is already running.
    # -r and -X combination tries to send a command to an existing screen
    # session, without actually attaching (which would fail since we
    # have no terminal).
    if screen -r pulseagent -X time; then
        echo "pulseagent screen session already exists - exiting" \
            | logger -s -t pulseagent
        exit 0
    fi

    echo "creating a new pulseagent screen session" | logger -s -t pulseagent
    exec screen -D -m -q -S pulseagent "$0" "$@"
fi

PULSEDIR=$HOME/pulse-agent

# Don't exit on SIGHUP; this allows running this script in a terminal to get
# output displayed on the screen, but not exiting the agent if the terminal is closed
trap true HUP

sleepint=8
maxsleep=1800
echo "pulse-agent.sh launched, entering pulse agent run loop..." | logger -s -t pulseagent
while true; do
    $PULSEDIR/bin/pulse start 2>&1 | logger -s -t pulseagent

    # It's always an error if Pulse stops, unless we're shutting down.
    # We'll try hard to restart the Pulse agent.
    if test $sleepint -gt $maxsleep; then
        echo "Pulse agent failed too often, giving up" | logger -s -t pulseagent
        exit 2
    fi

    echo "Pulse agent exited unexpectedly! Will attempt restart in $sleepint seconds" | logger -s -t pulseagent
    sleep $sleepint
    sleepint=$(expr $sleepint \* 2)
done

