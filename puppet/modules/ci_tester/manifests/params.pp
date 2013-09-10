class ci_tester::params {
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

    if ($::operatingsystem == 'Ubuntu') and ($::operatingsystemrelease == '11.10') {
        if $::architecture == 'i386' {
            $armel_cross_enabled = true
        } else {
            $armel_cross_enabled = false
        }
    } else {
        $armel_cross_enabled = false
    }

    $qt_gerrit_mirror = undef
}
