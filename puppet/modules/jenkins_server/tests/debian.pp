if $::operatingsystem == 'windows' {
    selftest::skip_all { "jenkins_server is not supported on Windows": }
}

include jenkins_server
include jenkins_server::debian

selftest::expect_no_warnings { "no warnings from jenkins_server::debian": }
