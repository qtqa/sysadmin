class homedir_cpan::mac {
    # Details about the local::lib version we'll install
    $LOCALLIB_VERSION   = "1.008004"

    # Marker file indicating that puppet has installed local::lib
    $LOCALLIB_MARKER    = "/Users/$testuser/perl5/.CREATED_BY_PUPPET"

    # Location for bootstrap script
    $LOCALLIB_BOOTSTRAP = "/Users/$testuser/local-lib-bootstrap.pl"

    # Log file for installation process
    $LOCALLIB_LOG       = "/Users/$testuser/local-lib-bootstrap.log"

    # If this machine has a defined testuser, let's bootstrap local::lib
    # into $HOME/perl5.  We need to do this because, unlike on Linux, there
    # is no simple way to install it to the system (e.g. there is no local::lib
    # in macports).
    if $testuser {
        file { $LOCALLIB_BOOTSTRAP:
            source      =>  "puppet:///modules/homedir_cpan/mac/local-lib-bootstrap.pl",
        }
        file { "/etc/profile.d/local-lib-perl.sh":
            source      =>  "puppet:///modules/homedir_cpan/profile.d/local-lib-perl.sh",
        }

        exec { "install local::lib for $testuser":
            command     => "/usr/bin/sudo -u $testuser -H -i /bin/sh -c '

    $LOCALLIB_BOOTSTRAP $LOCALLIB_VERSION >>$LOCALLIB_LOG 2>&1 && touch $LOCALLIB_MARKER

            '",

            creates     => $LOCALLIB_MARKER,
            logoutput   => true,
            require     => File[ $LOCALLIB_BOOTSTRAP ],
        }
    }
}
