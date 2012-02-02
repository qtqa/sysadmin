#!/bin/sh

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
    if [ "x$1" = "x-scratchbox" ]; then
        echo "Running pulse agent through scratchbox" | logger -s -t pulseagent
        SBHOME=/scratchbox/users/$USER/$HOME
        MY_ENV=$SBHOME/pulse-agent.env
        rm -f $MY_ENV
        if test -d $SBHOME/pulse_java; then
            echo "JAVA_HOME=$HOME/pulse_java; export JAVA_HOME" >> $MY_ENV
        fi
        /usr/bin/scratchbox -e $MY_ENV pulse-agent/bin/pulse start 2>&1 | logger -s -t pulseagent
    else
        $PULSEDIR/bin/pulse start 2>&1 | logger -s -t pulseagent
    fi

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

