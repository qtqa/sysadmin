class homedir_cpan::mac {
    # Include qt_prereqs because Package["p5-libwww-perl"] is defined there
    # and puppet tests require that module have direct includes for dependencies
    include qt_prereqs

    file { "/etc/profile.d/local-lib-perl.sh":
        source      =>  "puppet:///modules/homedir_cpan/profile.d/local-lib-perl.sh",
    }

    install_local_lib {$baselayout::testuser :}
    if ($baselayout::testuser != $::id) {
        install_local_lib {$::id :}
    }


}

define install_local_lib() {

    if 'root' == $name {
        $home = "/var/root"
    } else {
        $home = "/Users/$name"
    }

    # Details about the local::lib version we'll install
    $LOCALLIB_VERSION   = "1.008004"

    # Marker file indicating that puppet has installed local::lib
    $LOCALLIB_MARKER    = "$home/perl5/.CREATED_BY_PUPPET"

    # Location for bootstrap script
    $LOCALLIB_BOOTSTRAP = "$home/local-lib-bootstrap.pl"

    # Log file for installation process
    $LOCALLIB_LOG       = "$home/local-lib-bootstrap.log"

    file { $LOCALLIB_BOOTSTRAP:
        source      =>  "puppet:///modules/homedir_cpan/mac/local-lib-bootstrap.pl",
    }

    # If this machine has a defined testuser, let's bootstrap local::lib
    # into $HOME/perl5.  We need to do this because, unlike on Linux, there
    # is no simple way to install it to the system (e.g. there is no local::lib
    # in macports).
    exec { "install local::lib for $name":
        command     => "/usr/bin/sudo -u $name -H -i /bin/sh -c 'perl $LOCALLIB_BOOTSTRAP $LOCALLIB_VERSION >>$LOCALLIB_LOG 2>&1 && touch $LOCALLIB_MARKER'",
        creates     => $LOCALLIB_MARKER,
        logoutput   => true,
        require     => [
            File[ $LOCALLIB_BOOTSTRAP ],
            Package["p5-libwww-perl"],
        ],
    }
}
