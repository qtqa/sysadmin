include squish

    # e.g. puppet version 2.7 gives warnings about tempdir and input url. Those aren't recognized in puppet's tests.
    if $puppetversion == "3.0.0" {
        selftest::expect_no_warnings { "no warnings from squish": }
    }
