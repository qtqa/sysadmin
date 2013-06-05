class homedir_cpan::opensuse {
    package {
        # We use the local::lib module to implement $HOME/perl5
        "perl-local-lib":    ensure  =>  installed;
    }

    file { "/etc/profile.d/local-lib-perl.sh":
        ensure  =>  present,
        source  =>  "puppet:///modules/homedir_cpan/profile.d/local-lib-perl.sh",
        require =>  Package["perl-local-lib"],
    }
}

