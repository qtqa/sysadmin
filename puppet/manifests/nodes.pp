node default {
    $testgroup = $operatingsystem ? {
        Darwin  =>  "staff",
        Solaris =>  "other",
        default =>  "users",
    }

    # For hosts where you want to use distcc, set this to the appropriate DISTCC_HOSTS
    # Note: prefer usage of icecream where possible, since that has a scheduler
    $distcc_hosts = [ "localhost" ]

    if $operatingsystem == "Darwin" {
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

node 'snowleopard-tester.test.qt.nokia.com' inherits default {
    $testuser = "qt"
    include baselayout
    include qt_prereqs
    include pulseagent
    include ccache
    include qadungeon

    # Note: distcc is commented here because it must be set _after_ $distcc_hosts
    # is set to the correct value - and that value isn't known yet.
    # To use distcc, in your node definition in private_nodes you should have
    # something like:
    #
    # $distcc_hosts = [ "localhost", "host1", "host2", "host3" ]
    # include distcc

    include distccd
    include puppet
    include sshkeys
}

node 'snowleopard-parallels-server.test.qt.nokia.com' inherits default {
    include puppet
    include distccd
}

node 'legacy-snowleopard-tester.test.qt.nokia.com' inherits default {
    $testuser = "pulseagent"
    include baselayout
    include qt_prereqs
    include pulseagent
    include ccache
    #include distcc
    include distccd
    include puppet
    include sshkeys
}

node 'snowleopard-packager.test.qt.nokia.com' inherits default {
    $testuser = "pulseagent"
    include baselayout
    include qt_prereqs
    include pulseagent
    include ccache
    include qadungeon
    include puppet
    include sshkeys
}

#====================== Linux =================================================
node 'linux-tester.test.qt.nokia.com' inherits default {
    $testuser = "qt"
    include puppet
    include baselayout
    include qt_prereqs
    include pulseagent
    include sshkeys
    include qadungeon
    include ccache
    include icecc
    include crosscompilers
    include intel_compiler
    include vmware_tools
    include symbian_linux

    # Allow test machines to install modules from cpan under $HOME/perl5
    include homedir_cpan
}

node 'maemo-tester.test.qt.nokia.com' inherits default {
    $testuser = "qt"
    include puppet
    include baselayout
    include qt_prereqs
    include sshkeys
    include ccache
    include icecc
    include crosscompilers
    include vmware_tools

    # scratchbox stuff
    include scratchbox
    include scratchbox_qadungeon
    include scratchbox_pulseagent
    include scratchbox_baselayout
}

node 'ubuntu1004-x86.test.qt.nokia.com' inherits 'linux-tester.test.qt.nokia.com' {
}

node 'meego-obs-client.test.qt.nokia.com' inherits 'linux-tester.test.qt.nokia.com' {
    include meego_osc
}

#====================== Servers =================================================

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

    include integrator_www

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
node 'solaris-master.test.qt.nokia.com' inherits default {
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
}

# containers themselves
node 'solaris-container.test.qt.nokia.com' inherits default {
    $zone = true
    $testuser = pulse
    include baselayout
    include pulseagent
    include puppet
    include qadungeon
    include ccache
    include sshkeys
    include qt_prereqs
}

#====================== Test machines ========================================

# The real hostnames of all test machines are maintained in a private file, sorry

# Allow for additional nodes to be declared in a private_nodes module
import "private_nodes"
