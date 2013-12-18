# BlackBerry CI node for compiling, running autotests
#
class bb_tester(
    # user account used for all testing
    $testuser = $bb_tester::params::testuser,

    # IP address of qt-test-server network test server
    $network_test_server_ip = $bb_tester::params::network_test_server_ip,

    # Jenkins parameters
    $jenkins_enabled = $bb_tester::params::jenkins_enabled,
    $jenkins_server = $bb_tester::params::jenkins_server,
    $jenkins_slave_name = $bb_tester::params::jenkins_slave_name,

    # Deploy VMWare related modules as well?
    $vmware_enabled = $bb_tester::params::vmware_enabled,

    # Use icecream distributed compilation tool?
    $icecc_enabled = $bb_tester::params::icecc_enabled,

    # icecream scheduler host; empty means autodiscovery
    $icecc_scheduler_host = $bb_tester::params::icecc_scheduler_host,

    # local mirror of codereview.qt-project.org
    $qt_gerrit_mirror = $bb_tester::params::qt_gerrit_mirror,

    # Use distcc distributed compilation tool?
    $distcc_enabled = $bb_tester::params::distcc_enabled,

    $distcc_hosts = $bb_tester::params::distcc_hosts

) inherits bb_tester::params {
    case $::operatingsystem {
        Ubuntu:  { include bb_tester::ubuntu }
        default: { warning("No implementation for bb_tester on $::operatingsystem") }
    }
}
