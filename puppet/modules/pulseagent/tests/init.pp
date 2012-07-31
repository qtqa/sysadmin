class { 'pulseagent':
    user => 'fakeuser',
    group => 'fakegroup'
}

# mock startup(), usually provided by baselayout
define baselayout::startup($user, $path) {
    notice( "startup item created for $user, $path" )
}

# pulseagent is not (yet?) implemented for Windows
if $::operatingsystem != 'windows' {
    selftest::expect { 'startup item created':
        output => "startup item created for fakeuser.*pulse",
    }
}

selftest::expect_no_warnings { 'no warnings from pulseagent': }
