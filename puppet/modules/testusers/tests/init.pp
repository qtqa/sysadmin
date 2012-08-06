class { 'testusers':
    user => 'fakeuser'
}

if $::operatingsystem != 'windows' {
    exec { "Ensure sudoers.d is enabled":
        command     => "/bin/true",
        refreshonly => true
    }
}

selftest::expect_no_warnings { 'no warnings from testusers': }
