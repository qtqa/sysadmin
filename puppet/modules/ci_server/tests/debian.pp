if $::operatingsystem == 'windows' {
    selftest::skip_all { "ci_server is not supported on Windows": }
}

# mock git::config to avoid "Invalid user: fakeuser" from certain puppet versions
define git::config(
    $file = '<default>',
    $user = '<default>',
    $content = '<default>',
    $key = '<default>'
) {
}

include ci_server
include ci_server::debian

selftest::expect_no_warnings { "no warnings from ci_server::debian": }
