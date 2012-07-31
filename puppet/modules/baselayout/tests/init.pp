selftest::expect { 'something is done with $testuser':
    # here, we simply verify that fakeuser is used _somehow_
    # (to ensure at least that the value is looked up correctly)
    output => $::kernel ? {
        Linux => 'File\[/home/fakeuser/bin\]',
        Darwin => 'File\[/Users/fakeuser/bin\]',
        windows => '.',     # FIXME: add something meaningful
    }
}

selftest::expect_no_warnings { 'baselayout prints no warnings': }

node default {
    class { 'baselayout':
        testuser => 'fakeuser',
        testgroup => 'fakegroup',
    }
}
