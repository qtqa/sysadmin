class ci_tester::linux inherits ci_tester::base {
    include ccache
    include crosscompilers
    include android
    include qnx

    if $ci_tester::vmware_enabled {
        include vmware_tools
    }

    # Allow test machines to install modules from cpan under $HOME/perl5
    include homedir_cpan
    # Install CPAN modules needed outside building
    include cpan

    # Allow test machines to install python modules with pip or easy_install
    # to $HOME/python26
    include homedir_virtualenv

    # Provide small filesystem for testing of out-of-space errors
    include smallfs

    include testusers

    if $ci_tester::icecc_enabled {
        class { "icecc":
            scheduler_host => $ci_tester::icecc_scheduler_host
        }
    }

    if $ci_tester::armel_cross_enabled {
        include armel_cross
    }
}
