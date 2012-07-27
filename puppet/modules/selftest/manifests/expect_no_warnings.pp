# selftest::expect_no_warnings informs 20-puppet-tests.t that any observed warnings
# should be treated as a failure.

define selftest::expect_no_warnings() {
    notice( "test-expect-no-warnings: $name" )
}
