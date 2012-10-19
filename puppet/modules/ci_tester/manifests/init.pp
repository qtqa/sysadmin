# Qt Project CI node for compiling, running autotests
#
class ci_tester(
    # user account used for all testing
    $testuser = $ci_tester::params::testuser,

    # IP address of qt-test-server network test server
    $network_test_server_ip = $ci_tester::params::network_test_server_ip,

    # Jenkins parameters
    $jenkins_enabled = $ci_tester::params::jenkins_enabled,
    $jenkins_server = $ci_tester::params::jenkins_server,
    $jenkins_slave_name = $ci_tester::params::jenkins_slave_name,

    # Use icecream distributed compilation tool?
    $icecc_enabled = $ci_tester::params::icecc_enabled,

    # icecream scheduler host; empty means autodiscovery
    $icecc_scheduler_host = $ci_tester::params::icecc_scheduler_host,

    # local mirror of codereview.qt-project.org
    $qt_gerrit_mirror = $ci_tester::params::qt_gerrit_mirror,

    $distcc_hosts = $ci_tester::params::distcc_hosts

) inherits ci_tester::params {
    case $::kernel {
        Linux:   { include ci_tester::linux }
        Darwin:  { include ci_tester::mac }
        Windows: { include ci_tester::windows }
        default: { warning("No implementation for ci_tester on $::kernel") }
    }
}
