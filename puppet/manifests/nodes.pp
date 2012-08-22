node default {
    if $::operatingsystem == "Darwin" {
        # Hack for macs to work around http://projects.puppetlabs.com/issues/2331 :
        # On the first run, use the possibly broken `darwinport' provider (so that
        # compilation at least can succeed), but warn about it.  The first run
        # should install the unbroken `macports' provider, which we will then use
        # at the second run.
        $macports_rb = "/opt/local/lib/ruby/site_ruby/1.8/puppet/provider/package/macports.rb"
        $macports_installed = generate('/bin/sh', '-c', "   \
            if /bin/test -f $macports_rb; then              \
                /bin/echo -n yes;                           \
            else                                            \
                /bin/echo -n no;                            \
            fi                                              \
        ")

        if $macports_installed == "yes" {
            $macports_provider = "macports"
        }
        else {
            $macports_provider = "darwinport"
            warning(
"Using `darwinport' provider to install packages. \
Due to puppet bug http://projects.puppetlabs.com/issues/2331, some packages \
may not install correctly. This should be resolved the next time puppet \
is run on this host."
            )
        }
    }

    include puppet
}

#====================== Mac ===================================================

node 'lion-tester.test.qt-project.org' inherits default {
    class { 'baselayout': testuser => 'qt' }
    include qt_prereqs
    include hosts
    include pulseagent
    include ccache
    include qadungeon
    include homedir_cpan
    include homedir_virtualenv

    # Note: distcc is commented here because the appropriate $hosts parameter
    # is not known at this level.
    # To use distcc, in your node definition in private_nodes you should have
    # something like:
    #
    # class { "distcc": hosts => [ "localhost", "host1", "host2", "host3" ] }

    include distccd
    include puppet
    include sshkeys
}

node 'snowleopard-tester.test.qt-project.org' inherits default {
    class { 'baselayout': testuser => 'qt' }
    include qt_prereqs
    include hosts
    include pulseagent
    include ccache
    include qadungeon
    include homedir_cpan
    include homedir_virtualenv

    # class { "distcc": hosts => [ "localhost", "host1", "host2", "host3" ] }

    include distccd
    include puppet
    include sshkeys
}

node 'snowleopard-parallels-server.test.qt-project.org' inherits default {
    include puppet
    include distccd
}

node 'legacy-snowleopard-tester.test.qt-project.org' inherits default {
    class { 'baselayout': testuser => 'pulseagent' }
    include qt_prereqs
    include hosts
    include pulseagent
    include ccache
    #include distcc
    include distccd
    include puppet
    include sshkeys
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

#====================== Linux =================================================
node 'linux-tester.test.qt-project.org' inherits default {
    class { 'baselayout': testuser => 'qt' }
    include puppet
    include qt_prereqs
    include hosts
    include sshkeys
    include qadungeon
    include ccache
    include crosscompilers
    include intel_compiler
    include vmware_tools

    # Allow test machines to install modules from cpan under $HOME/perl5
    include homedir_cpan

    # Allow test machines to install python modules with pip or easy_install
    # to $HOME/python26
    include homedir_virtualenv

    # Provide small filesystem for testing of out-of-space errors
    include smallfs
}

node 'ubuntu1004-x86.test.qt-project.org' inherits 'linux-tester.test.qt-project.org' {
    include pulseagent
    include icecc
    include testcocoon
    include testusers
}

node 'ubuntu1110-x64.test.qt-project.org' inherits 'linux-tester.test.qt-project.org' {
    class { "pulseagent": short_datadir => true }
    # icecc initialisation delayed until scheduler address is known
    #include icecc
    include testcocoon
    include testusers
}

node 'ubuntu1110-x86.test.qt-project.org' inherits 'linux-tester.test.qt-project.org' {
    class { "pulseagent": short_datadir => true }
    include icecc
    include testcocoon
    include armel_cross
    include testusers
}

node 'ubuntu1204-x64.test.qt-project.org' inherits 'linux-tester.test.qt-project.org' {
    class { "pulseagent": short_datadir => true }
    include icecc
    include testusers
}

#====================== Windows ===============================================

node 'windows7-msvc2010-x86.test.qt-project.org' inherits 'default' {
    class { 'baselayout': testuser => 'pulse' }
    include qt_prereqs
    include sshkeys
    include mesa3d
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

# Note: we match any domain here, rather than just `qt-test-net', because facter
# refuses to consider `qt-test-net' a valid domain name, since it doesn't contain a dot
node /^qt-test-server\./ inherits default {
    include puppet
    include network_test_server
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

node 'ci.qt-project.org', 'ci-dev.qt-project.org' inherits default {
    include jenkins_server
}

#====================== Test machines ========================================

# The real hostnames of all test machines are maintained in a private file, sorry

# Allow for additional nodes to be declared in a private_nodes module
import "private_nodes"
