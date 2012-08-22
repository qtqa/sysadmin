#!/bin/bash

# Bootstrap a clean Ubuntu 11.10 system to be managed by puppet.

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

if ! test -e /usr/bin/puppet; then
    echo Installing puppet...
    apt-get -y -o DPkg::Options::=--force-confnew install puppet
else
    echo puppet is already installed
fi

if ! test -e /usr/bin/git; then
    echo Installing git...
    apt-get -y -o DPkg::Options::=--force-confnew install git-core
else
    echo git is already installed
fi

if ! test -d /var/qtqa/sysadmin; then
    echo "Grabbing $REPO ..."
    mkdir -p /var/qtqa
    git clone "$REPO" /var/qtqa/sysadmin
fi

# Run puppet once.
echo "Running puppet..."
/var/qtqa/sysadmin/puppet/sync_and_run.pl
set +x
echo 'All done :-)'
echo 'If this host already has an entry in manifests/nodes.pp, nothing needs to be done...'
echo "Otherwise, puppet is ready to go but will not do anything until you add an entry for $(facter fqdn) to manifests/nodes.pp"

