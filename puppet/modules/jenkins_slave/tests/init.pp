class { 'jenkins_slave':
    user => 'fakeuser',
    group => 'fakegroup',
    server => 'http://jenkins.example.com/',
}

# mock startup(), normally provided by baselayout
define baselayout::startup ($user, $path, $arguments='') {
    notice( "startup item created for $user, $path $arguments" )
}

if $::operatingsystem != 'windows' {
    exec { "Ensure sudoers.d is enabled":
        command     => "/bin/true",
        refreshonly => true
    }
}

selftest::expect { 'startup item created':
    output => "startup item created for fakeuser, .*jenkins-slave.pl"
}

selftest::expect_no_warnings { 'no warnings from jenkins_slave': }
