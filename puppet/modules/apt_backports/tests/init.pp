selftest::expect_no_warnings { "no warnings for apt_backports": }

if $::operatingsystem == 'Ubuntu' or $::operatingsystem == 'Debian' {

    # These Linux should have default parameters ...
    include apt_backports

} elsif $::kernel == 'Linux' {

    # Other Linux do not
    class { "apt_backports":
        base_url => 'some-fake-url',
        sections => 'some sections',
    }

}

# ...and OS other than Linux are not supported at all.
