#!/bin/sh
# This script syncs this repo to latest and does a single puppet run.
# Intended to be used from a cron job.

DIR=`dirname $0`
# FIXME: is there a standard shell way to do this??
DIR=`perl -mCwd -e "print Cwd::abs_path('$DIR')"`

set -e

cd $DIR

if test -f disable_puppet; then
    echo "not doing anything because $DIR/disable_puppet exists" | logger -t puppet
    exit 0
fi

# Update main checkout, plus `private' overlay (if present)
for gitdir in "$DIR" "$DIR/private"; do
    if ! test -d "$gitdir"; then continue; fi

    cd "$gitdir"
    git pull -q 2>&1 | logger -t puppetgit -p daemon.warning -s

    # If our git repo has somehow become out of sync, these commands will log about it
    git ls-files --full-name --others 2>&1 | egrep -v '^puppet/private/' | sed -e 's/^/untracked: /' | logger -t puppetgit -p daemon.warning -s
    git diff 2>&1 | sed -e 's/^/diff: /' | logger -t puppetgit -p daemon.warning -s

    cd "$DIR"
done

exec puppet --confdir $DIR "$@" --logdest syslog "$DIR/manifests/site.pp" 2>&1 | logger -t puppet -p daemon.error

