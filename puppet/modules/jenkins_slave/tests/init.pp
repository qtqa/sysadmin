# user account used for the test; must actually exist on the system (or a fatal error occurs
# in some puppet versions even in no-op mode), so pick the user running puppet
$testuser = $::id

# above, escaped for usage in regex
$testuser_re = inline_template("<%= Regexp.quote('$testuser') %>")

# group account used for the test; must exist, as for $testuser
$testgroup = $::operatingsystem ? {
    Darwin  =>  "staff",
    default =>  "users"
}

class { 'jenkins_slave':
    user => $testuser,
    group => $testgroup,
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
    output => "startup item created for $testuser_re, .*jenkins-slave.pl"
}

selftest::expect_no_warnings { 'no warnings from jenkins_slave': }
