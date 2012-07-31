class { 'testusers':
    user => 'fakeuser'
}

selftest::expect_no_warnings { 'no warnings from testusers': }
