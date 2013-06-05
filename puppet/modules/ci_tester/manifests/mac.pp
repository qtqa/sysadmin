class ci_tester::mac inherits ci_tester::base {
    include ccache
    include homedir_cpan
    # Install CPAN modules needed outside building
    include cpan
    include homedir_virtualenv

    if $ci_tester::distcc_enabled {
        class { "distcc":
            hosts => $ci_tester::distcc_hosts
        }

    include distccd
    }

    include puppet
}
