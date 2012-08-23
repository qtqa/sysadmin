#!/bin/sh

# Bootstrap a clean OSX 10.7 system to be managed by puppet.
# NOTE: because OSX does not ship with a compiler by default, and installing
# puppet via macports needs a compiler, this script may be a bit prone
# to failure if you do not install gcc and macports yourself first.

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

WORKDIR=$HOME/bootstrap_tmp
INPUT=http://bq-qastore.apac.nokia.com/public/input/mac

set -e
#set -x

mkdir -p $WORKDIR
cd $WORKDIR

# Ensures xcode is installed.
# xcode is required for using macports.
if ! gcc -v > /dev/null 2>&1; then
    echo install xcode first 1>&2
    exit 2
else
    echo xcode is already installed
fi

# Ensures macports is installed.
# macports is required for installing puppet.
if ! test -e /opt/local/bin/port; then
    echo install macports first 1>&2
    exit 2
else
    echo macports is already installed
fi

# Ensures puppet is installed.
if ! test -e /opt/local/bin/puppet; then
    echo Installing puppet...
    /opt/local/bin/port install puppet
else
    echo puppet is already installed
fi

if ! test -d /var/qtqa/sysadmin; then
    echo "Grabbing $REPO ..."
    mkdir -p /var/qtqa
    git clone "$REPO" /var/qtqa/sysadmin
fi

echo "Configuring this node..."
/usr/bin/env PATH=/opt/local/bin:$PATH /var/qtqa/sysadmin/puppet/nodecfg.pl -interactive

# Run puppet once.
# From this point on, all setup of this machine is done via puppet.
echo "Running puppet..."
/usr/bin/env PATH=/opt/local/bin:$PATH /var/qtqa/sysadmin/puppet/sync_and_run.pl
set +x
echo 'All done :-)'
