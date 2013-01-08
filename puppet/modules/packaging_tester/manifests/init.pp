# Qt Project CI node for compiling, running autotests
#
class packaging_tester(
    # user account used for all testing
    $testuser = $packaging_tester::params::testuser,

    # IP address of qt-test-server network test server
    # $network_test_server_ip = $packaging_tester::params::network_test_server_ip,

    # Jenkins parameters
    $jenkins_enabled = $packaging_tester::params::jenkins_enabled,
    $jenkins_server = $packaging_tester::params::jenkins_server,
    $jenkins_slave_name = $packaging_tester::params::jenkins_slave_name,

    # Deploy VMWare related modules as well?
    $vmware_enabled = $packaging_tester::params::vmware_enabled,

    # Use icecream distributed compilation tool?
    $icecc_enabled = $packaging_tester::params::icecc_enabled,

    # icecream scheduler host; empty means autodiscovery
    $icecc_scheduler_host = $packaging_tester::params::icecc_scheduler_host,

    # local mirror of codereview.qt-project.org
    $qt_gerrit_mirror = $packaging_tester::params::qt_gerrit_mirror,

    # Use distcc distributed compilation tool?
    $distcc_enabled = $packaging_tester::params::distcc_enabled,

    $distcc_hosts = $packaging_tester::params::distcc_hosts

) inherits packaging_tester::params {
    case $::kernel {
        Linux:   { include packaging_tester::linux }
        Darwin:  { include packaging_tester::mac }
        Windows: { include packaging_tester::windows }
        default: { warning("No implementation for packaging_tester on $::kernel") }
    }
}
