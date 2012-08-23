#!/bin/bash

# Bootstrap a clean Debian Squeeze system to be managed by puppet.

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
    apt-get -y -o DPkg::Options::=--force-confnew install git
else
    echo git is already installed
fi

BACKPORTS_FILE=/etc/apt/sources.list.d/bootstrap-backports-sources.list
if ! test -e $BACKPORTS_FILE; then
    echo Setting up backports sources...
    echo 'deb http://backports.debian.org/debian-backports squeeze-backports main' > $BACKPORTS_FILE
    apt-get update
else
    echo backports sources already exist
fi

if ! test -e /usr/bin/puppet; then
    echo Installing puppet...
    apt-get -t squeeze-backports -y -o DPkg::Options::=--force-confnew install puppet
else
    echo puppet is already installed
fi

if ! test -d /var/qtqa/sysadmin; then
    echo "Grabbing $REPO ..."
    mkdir -p /var/qtqa
    git clone "$REPO" /var/qtqa/sysadmin
fi

# disable any cdrom sources left over from install
sed -r -e 's|^deb cdrom:|#deb cdrom:|' -i /etc/apt/sources.list

echo "Configuring this node..."
/var/qtqa/sysadmin/puppet/nodecfg.pl -interactive

# Run puppet once.
echo "Running puppet..."
/var/qtqa/sysadmin/puppet/sync_and_run.pl
set +x
echo 'All done :-)'

# remove unneeded temporary backports file
rm -f $BACKPORTS_FILE
