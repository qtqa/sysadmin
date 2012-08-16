# not supported outside of Windows
if $::operatingsystem == 'windows' {
    # mock the script resource because we may not have permission to read it
    file { "C:\\qtqa\\bin\\qtqa-reg.pl": }

    registry::value { "screensaver off":
        key => 'HKU\someuser',
        value => 'screensaver',
        data => '0',
        ensure => present,
    }
    selftest::expect { "first key created":
        output => 'Exec\[.*qtqa-reg\.pl write -path "HKU\\someuser\\screensaver"',
    }

    registry::value { "some HKLM key":
        key => 'HKLM\thing1\thing2',
        value => 'thing3',
        type => 'expand',
        ensure => present,
    }
    selftest::expect { "second key created":
        output => 'Exec\[.*qtqa-reg\.pl write -path "HKLM\\thing1\\thing2\\thing3".*-type "REG_EXPAND_SZ"',
    }

    registry::value { "other HKLM key":
        key => 'HKLM\thing1\thing2',
        value => 'thing4',
        ensure => absent,
    }

    selftest::expect_no_warnings { "no warnings from registry::value": }
}
