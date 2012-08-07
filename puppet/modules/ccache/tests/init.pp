node default {
    class { 'ccache':
        user => 'fakeuser',
    }
}

if $::kernel == 'Linux' {
    file { '/home/fakeuser/bin': }

    selftest::expect { '/usr/bin/ccache is run for fakeuser':
        output => 'Exec\[[^\]]*/usr/bin/ccache'
    }

    selftest::expect { 'gcc -> ccache symlink created for fakeuser':
        output => 'Ccache::Link\[gcc\]/File\[/home/fakeuser/bin/gcc\]'
    }
}

if $::kernel == 'Darwin' {
    file { '/Users/fakeuser/bin': }
}

# TODO: add other OS?

selftest::expect_no_warnings { 'no warnings from ccache': }
