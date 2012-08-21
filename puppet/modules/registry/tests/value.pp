# mock the registry class and script file here,
# otherwise the test may fail if the real script exists,
# is owned by SYSTEM, and we are running the test as a
# normal user
class registry {
    $script = "C:\\fakescript"
    if $::operatingsystem == 'windows' {
        file { $script: }
    }
}

include registry

# not supported outside of Windows
if $::operatingsystem == 'windows' {
    # expected default view args
    $view_args = $::architecture ? {
        x64 => '-view64',
        default => '',
    }

    registry::value { "screensaver off":
        key => 'HKU\someuser',
        value => 'screensaver',
        data => '0',
        ensure => present,
    }
    selftest::expect { "first key created":
        output => "Exec\\[.*fakescript write $view_args -path \"HKU\\\\someuser\\\\screensaver\"",
    }

    registry::value { "some HKLM key":
        key => 'HKLM\thing1\thing2',
        value => 'thing3',
        type => 'expand',
        view => '32',
        ensure => present,
    }
    selftest::expect { "second key created":
        output => "Exec\\[.*fakescript write -view32 -path \"HKLM\\\\thing1\\\\thing2\\\\thing3\".*-type \"REG_EXPAND_SZ\"",
    }

    registry::value { "other HKLM key":
        key => 'HKLM\thing1\thing2',
        value => 'thing4',
        ensure => absent,
    }

    selftest::expect_no_warnings { "no warnings from registry::value": }
}
