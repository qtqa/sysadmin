selftest::expect_no_warnings { 'baselayout prints no warnings': }

node default {
    class { 'baselayout': }
}
