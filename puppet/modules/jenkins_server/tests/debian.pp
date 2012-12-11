if $::operatingsystem == 'windows' or $::operatingsystem == 'darwin' {
    selftest::skip_all { "jenkins_server is not supported on Windows or Mac": }
}

# mock git::config to avoid "Invalid user: fakeuser" from certain puppet versions
define git::config(
    $file = '<default>',
    $user = '<default>',
    $content = '<default>',
    $key = '<default>'
) {
}

include jenkins_server
include jenkins_server::debian

selftest::expect_no_warnings { "no warnings from jenkins_server::debian": }
