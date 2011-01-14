HOW TO USE THIS REPOSITORY FOR A NETWORK TEST SERVER
====================================================

This README is a fast-track for those who only want to set up a network
test server for use with Qt's network autotests.  Check `README' for
more general details about this repo.


== Phase 1: basic machine setup

Obtain a physical or virtual machine, with an IPv4 interface available.

Suggested specs are at least:

 RAM:  512MB
 CPUs: 1
 Storage: 8GB

Install Ubuntu 10.04, preferably with no GUI.

During installation, set the hostname to `qt-test-server'.

Set root password to anything you like.

When prompted for a non-root user account, use "qt" as the username with
any password you like.

Don't enable automatic updates.

Don't install any extra packages (except openssh-server, if you need it).

The machine needs outbound access to the Internet for accessing this git
repository and Ubuntu package repositories, and needs to accept inbound
access on whatever local network contains the device(s) on which you'll
be running Qt network tests.
If this requires more than one network interface for your setup, you must
ensure that the eth0 interface is set up as the interface which Qt network
tests will connect to.

USE A TRUSTED NETWORK ONLY!  The test server uses trivial weak passwords
and runs open proxies!


== Phase 2: puppet setup

It is recommended that you watch the system log during this step.
For example, run `tail -f /var/log/syslog &' before proceeding.

From this git repo, put bootstrap/ubuntu1004_bootstrap.sh onto the machine,
and run it.  e.g.

# wget http://qt.gitorious.org/projects/qtqa/repos/sysadmin/blobs/raw/master/bootstrap/ubuntu1004_bootstrap.sh \
    && sh ./ubuntu1004_bootstrap.sh git://qt.gitorious.org/qtqa/sysadmin.git

This will install and run puppet.  Since you named the machine qt-test-server
in the previous step (right?), puppet knows to set up the machine as a network
test server.

If all went well, you should have seen many entries in syslog about
puppet setting up various things.  Puppet will now be run periodically
to enforce correct setup of the machine (see `crontab -l') and automatically
track changes to this git repository.

Due to minor bootstrap issues, in order to complete the setup it will most
likely be necessary to run puppet a second time.  You may wait for this to
happen `naturally' or manually run /var/qtqa/sysadmin/puppet/sync_and_run.sh .

It is recommended to reboot the machine after the initial puppet setup to
ensure that all services are correctly launched after boot, and that no outdated
configuration files remain in use.

As a brief test to see if the server is working, you can try the networkselftest
Qt autotest.  On a machine _other_ than the network test server, put the test server
IP address into /etc/hosts as qt-test-server.qt-test-net, and run the networkselftest
testcase from Qt; if everything is set up correctly, it should 100% pass.
