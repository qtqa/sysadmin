#!/bin/bash

# Bootstrap a clean Ubuntu 10.04 system to be managed by puppet.

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

if ! test -e /usr/bin/git; then
    echo Installing git...
    apt-get -y -o DPkg::Options::=--force-confnew install git-core
else
    echo git is already installed
fi

PUPPETLIST_FILE=/etc/apt/sources.list.d/bootstrap-puppet.list
if ! test -e $PUPPETLIST_FILE; then
    echo Setting up bootstrap-puppet.list...
    echo -e 'deb http://apt.puppetlabs.com/ lucid main\ndeb-src http://apt.puppetlabs.com/ lucid main' > $PUPPETLIST_FILE
    apt-key adv --keyserver keyserver.ubuntu.com --recv 4BD6EC30
    apt-get update
else
    echo bootstrap-puppet.list already exist
fi


if ! test -e /usr/bin/puppet; then
    echo Installing puppet...
    apt-get -y -o DPkg::Options::=--force-confnew install puppet
    rm -f $PUPPETLIST_FILE
else
    echo puppet is already installed
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
