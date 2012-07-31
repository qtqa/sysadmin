if $::operatingsystem != 'windows' {
    class { 'kdesettings':
        user => 'fakeuser',
        group => 'fakegroup'
    }
}

selftest::expect_no_warnings { 'no warnings from kdesettings': }
