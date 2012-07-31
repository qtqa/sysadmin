class { 'homedir_cpan':
    user => 'fakeuser'
}

if $::kernel == 'Linux' {
    selftest::expect { 'installs cpanm':
        output => 'Exec\[install cpanm for fakeuser\]',
    }
}
