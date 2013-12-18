class bb_tester::base {
    # bb_tester setup common to all operating systems
    class { 'baselayout':
        testuser => $bb_tester::testuser,
        qt_gerrit_mirror => $bb_tester::qt_gerrit_mirror
    }
    include puppet

    if $bb_tester::vmware_enabled {
        include vmware_deployment
    }

    if $bb_tester::jenkins_enabled {
         class { 'jenkins_slave':
            server => $bb_tester::jenkins_server,
            slave_name => $bb_tester::jenkins_slave_name
         }
    }
}
