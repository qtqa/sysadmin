node default {
    include puppet
}

#====================== Mac ===================================================

node 'snowleopard-parallels-server.test.qt-project.org' inherits default {
    include puppet
    include distccd
}

node 'snowleopard-packager.test.qt-project.org' inherits default {
    class { 'baselayout': testuser => 'pulseagent' }
    include qt_prereqs
    include hosts
    include ccache
    include qadungeon
    include puppet
    include sshkeys
}


#====================== Servers ===============================================

# simple file server
node 'binaries.test.qt.nokia.com' inherits default {
    include puppet
    include sshkeys
    include simple_fileserver
}

#====================== Solaris ===============================================

# master nodes (those which have containers)
node 'solaris-master.test.qt-project.org' inherits default {
    $zone = false
    include puppet
    include baselayout
    include ccache
    include sshkeys

    # Note that only the master can install packages;
    # that's why the master has qt_prereqs even though it doesn't do builds in
    # CI.  Also, this is useful anyway because people manually do builds on
    # the master sometimes.
    include qt_prereqs
    include hosts
}

# containers themselves
node 'solaris-container.test.qt-project.org' inherits default {
    $zone = true
    class { 'baselayout': testuser => 'pulse' }
    include puppet
    include ccache
    include sshkeys
    include qt_prereqs
    include hosts
}

#====================== Test machines ========================================

# The real hostnames of all test machines are maintained in a private file, sorry
