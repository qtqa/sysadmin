class ci_tester::mac inherits ci_tester::base {
    include pulseagent
    include ccache
    include qadungeon
    include homedir_cpan
    include homedir_virtualenv

    class { "distcc":
        hosts => $ci_tester::distcc_hosts
    }

    include distccd
    include puppet
}
