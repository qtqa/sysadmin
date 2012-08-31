class ci_tester::base {
    # ci_tester setup common to all operating systems
    class { 'baselayout': testuser => $ci_tester::testuser }
    include puppet
    include sshkeys
    include qt_prereqs

    if $ci_tester::jenkins_enabled {
         class { 'jenkins_slave':
            server => $ci_tester::jenkins_server,
            slave_name => $ci_tester::jenkins_slave_name
         }
    }
}
