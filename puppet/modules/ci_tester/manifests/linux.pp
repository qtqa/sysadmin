class ci_tester::linux inherits ci_tester::base {
    include qadungeon
    include ccache
    include crosscompilers
    include intel_compiler
    include vmware_tools

    # Allow test machines to install modules from cpan under $HOME/perl5
    include homedir_cpan

    # Allow test machines to install python modules with pip or easy_install
    # to $HOME/python26
    include homedir_virtualenv

    # Provide small filesystem for testing of out-of-space errors
    include smallfs

    include testusers

    if $ci_tester::pulseagent_enabled {
        class { "pulseagent":
            short_datadir => $ci_tester::pulseagent_short_datadir
        }
    }

    if $ci_tester::icecc_enabled {
        class { "icecc":
            scheduler_host => $ci_tester::icecc_scheduler_host
        }
    }

    if $ci_tester::testcocoon_enabled {
        include testcocoon
    }
}
