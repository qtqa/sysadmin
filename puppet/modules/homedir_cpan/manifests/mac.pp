class homedir_cpan::mac {
    # Include qt_prereqs because Package["p5-libwww-perl"] is defined there
    # and puppet tests require that module have direct includes for dependencies
    include qt_prereqs

    $home = "/Users/$homedir_cpan::user"

    # Details about the local::lib version we'll install
    $LOCALLIB_VERSION   = "1.008004"

    # Marker file indicating that puppet has installed local::lib
    $LOCALLIB_MARKER    = "$home/perl5/.CREATED_BY_PUPPET"

    # Location for bootstrap script
    $LOCALLIB_BOOTSTRAP = "$home/local-lib-bootstrap.pl"

    # Log file for installation process
    $LOCALLIB_LOG       = "$home/local-lib-bootstrap.log"

    # If this machine has a defined testuser, let's bootstrap local::lib
    # into $HOME/perl5.  We need to do this because, unlike on Linux, there
    # is no simple way to install it to the system (e.g. there is no local::lib
    # in macports).
    if $homedir_cpan::user {
        file { $LOCALLIB_BOOTSTRAP:
            source      =>  "puppet:///modules/homedir_cpan/mac/local-lib-bootstrap.pl",
        }
        file { "/etc/profile.d/local-lib-perl.sh":
            source      =>  "puppet:///modules/homedir_cpan/profile.d/local-lib-perl.sh",
        }

        exec { "install local::lib for $homedir_cpan::user":
            command     => "/usr/bin/sudo -u $homedir_cpan::user -H -i /bin/sh -c '\"$LOCALLIB_BOOTSTRAP $LOCALLIB_VERSION >>$LOCALLIB_LOG 2>&1\" && touch $LOCALLIB_MARKER'",
            creates     => $LOCALLIB_MARKER,
            logoutput   => true,
            require     => [
                File[ $LOCALLIB_BOOTSTRAP ],
                Package["p5-libwww-perl"],
            ],
        }
    }
}
