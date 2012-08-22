#!/bin/sh

# Bootstrap a clean CentOS 5 system to be managed by puppet.

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

# Ensure DAG repo is present
if ! test -f /etc/yum.repos.d/rpmforge.repo; then
#    yes | rpm -Uvh http://apt.sw.be/redhat/el5/en/i386/rpmforge/RPMS/rpmforge-release-0.3.6-1.el5.rf.i386.rpm
#    yes | rpm -Uvh ftp://ftp.freshrpms.net/pub/dag/redhat/el5/en/x86_64/dag/RPMS/rpmforge-release-0.3.6-1.el5.rf.x86_64.rpm
    yes | rpm -Uvh ftp://ftp.pbone.net/mirror/ftp.sourceforge.net/pub/sourceforge/s/project/ss/sspamm/depencies/RHEL5/rpmforge-release-0.5.1-1.el5.rf.i386.rpm
fi
# Ensure EPEL repo is present
if ! test -f /etc/yum.repos.d/epel.repo; then
#    yes | rpm -Uvh http://download.fedora.redhat.com/pub/epel/5/i386/epel-release-5-3.noarch.rpm
#    yes | rpm -Uvh http://download.fedora.redhat.com/pub/fedora/epel/5/x86_64/epel-release-5-4.noarch.rpm
    yes | rpm -Uvh http://download.fedora.redhat.com/pub/epel/5/i386/epel-release-5-4.noarch.rpm
fi

if ! test -e /usr/bin/puppet; then
    echo Installing puppet...
    yum -y install puppet
else
    echo puppet is already installed
fi

if ! test -e /usr/bin/git; then
    echo Installing git...
    yum -y install git
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

