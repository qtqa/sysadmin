class bb_tester::params {
    $testuser = 'qt'

    $network_test_server_ip = undef
    $jenkins_enabled = false
    $jenkins_server = undef
    $jenkins_slave_name = $::hostname
    $vmware_enabled = true

    $icecc_enabled = true
    $icecc_scheduler_host = ''

    $distcc_enabled = true
    $distcc_hosts = 'localhost'

    $qt_gerrit_mirror = undef
}
