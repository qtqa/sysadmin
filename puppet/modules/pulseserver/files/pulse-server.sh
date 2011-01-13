#!/bin/sh

# If called with `--daemonize', fork and output only to log
if [ "x$1" = "x--daemonize" ]; then
    shift
    "$0" "$@" >/dev/null 2>&1 &
    exit 0
fi

PULSEDIR=$HOME/pulse

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
    -Xmx1024m \
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

