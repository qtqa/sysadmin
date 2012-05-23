#!/bin/sh
DIR=`dirname $0`

{
    echo "Warning: sync_and_run.sh is deprecated, please use sync_and_run.pl instead."
    echo "This warning can be ignored if it is only seen once on this host."
} | logger -t puppet -p daemon.warning

exec "$DIR/sync_and_run.pl" "$@" 2>&1 | logger -t puppet -p daemon.error
