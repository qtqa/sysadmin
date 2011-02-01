#!/bin/sh

# Bootstrap a clean OSX 10.6 system to be managed by puppet.
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
    echo Installing xcode...
    curl $INPUT/xcode3210a432.dmg -o xcode.dmg
    hdiutil attach ./xcode.dmg
    installer -pkg /Volumes/Xcode/Xcode.mpkg -target /
    hdiutil detach /Volumes/Xcode/
else
    echo xcode is already installed
fi

# Ensures macports is installed.
# macports is required for installing puppet.
if ! test -e /opt/local/bin/port; then
    echo Installing macports...
    curl $INPUT/MacPorts-1.8.2-10.6-SnowLeopard.dmg -o macports.dmg
    hdiutil attach ./macports.dmg
    installer -pkg /Volumes/MacPorts-1.8.2/MacPorts-1.8.2.pkg -target /
    hdiutil detach /Volumes/MacPorts-1.8.2

    cat >/opt/local/etc/macports/sources.conf <<EOF
rsync://rsync.macports.org/release/ports/ [default]
EOF

    /opt/local/bin/port sync
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

if ! test -e /opt/local/bin/git; then
    echo Installing git...
    # git was renamed to git-core in macports; allow for either name
    if /opt/local/bin/port info git >/dev/null 2>&1; then
        /opt/local/bin/port install git
    else
        /opt/local/bin/port install git-core
    fi
else
    echo git is already installed
fi

if ! test -d /var/qtqa/sysadmin; then
    echo "Grabbing $REPO ..."
    mkdir -p /var/qtqa
    /opt/local/bin/git clone "$REPO" /var/qtqa/sysadmin
fi

# Run puppet once.
# From this point on, all setup of this machine is done via puppet.
echo "Running puppet..."
/usr/bin/env PATH=/opt/local/bin:$PATH /var/qtqa/sysadmin/puppet/sync_and_run.sh
set +x
echo 'All done :-)'
echo 'If this host already has an entry in manifests/nodes.pp, nothing needs to be done...'
echo "Otherwise, puppet is ready to go but will not do anything until you add an entry for $(/opt/local/bin/facter fqdn) to manifests/nodes.pp"

