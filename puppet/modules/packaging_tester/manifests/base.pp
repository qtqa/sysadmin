class packaging_tester::base {
    # packaging_tester setup common to all operating systems
    class { 'baselayout':
        testuser => $packaging_tester::testuser,
        qt_gerrit_mirror => $packaging_tester::qt_gerrit_mirror
    }
    include puppet
    include sshkeys
    include icu4c

    if $packaging_tester::vmware_enabled {
        include vmware_deployment
    }

    class { 'qt_prereqs': }

    if $packaging_tester::jenkins_enabled {
         class { 'jenkins_slave':
            server => $packaging_tester::jenkins_server,
            slave_name => $packaging_tester::jenkins_slave_name
         }
    }
}
