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
    include pulseagent
    include ccache
    include qadungeon
    include puppet
    include sshkeys
}

# These hostnames will soon become deprecated.
node 'lion-tester.test.qt-project.org' inherits default {
    include ci_tester
}

node 'snowleopard-tester.test.qt-project.org' inherits default {
    include ci_tester
}


#====================== Linux =================================================
# These hostnames will soon become deprecated.
node 'linux-tester.test.qt-project.org' inherits default {
    include ci_tester
}

node 'ubuntu1004-x86.test.qt-project.org' inherits default {
    include ci_tester
}

node 'ubuntu1110-x64.test.qt-project.org' inherits default {
    include ci_tester
}

node 'ubuntu1110-x86.test.qt-project.org' inherits default {
    include ci_tester
}

node 'ubuntu1204-x64.test.qt-project.org' inherits default {
    include ci_tester
}

#====================== Windows ===============================================
# This hostname will soon become deprecated.
node 'windows7-msvc2010-x86.test.qt-project.org' inherits default {
    include ci_tester
}

#====================== Servers ===============================================

node 'pulse.test.qt.nokia.com' inherits default {
    # disable git cache because multiple gits will be running concurrently
    $disable_git_cache = true

    include vmware_tools
    include baselayout
    include sshkeys
    include pulseserver
    include pulseserver_qadungeon

    include puppet
}

node 'integrator.test.qt.nokia.com' inherits default {
    include baselayout
    include sshkeys

    include puppet
    include qtintegration
}

# simple file server
node 'binaries.test.qt.nokia.com' inherits default {
    include puppet
    include sshkeys
    include simple_fileserver
}

# test results server
node 'testr.test.qt.nokia.com' inherits default {
    include puppet
    include sshkeys
    include testr
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
    # Pulse.  Also, this is useful anyway because people manually do builds on
    # the master sometimes.
    include qt_prereqs
    include hosts
}

# containers themselves
node 'solaris-container.test.qt-project.org' inherits default {
    $zone = true
    class { 'baselayout': testuser => 'pulse' }
    include pulseagent
    include puppet
    include qadungeon
    include ccache
    include sshkeys
    include qt_prereqs
    include hosts
}

#====================== Test servers =========================================
# This hostname will soon become deprecated.
node 'ci.qt-project.org', 'ci-dev.qt-project.org' inherits default {
    include ci_server
}

#====================== Test machines ========================================

# The real hostnames of all test machines are maintained in a private file, sorry

# Allow for additional nodes to be declared in a private_nodes module
import "private_nodes"
