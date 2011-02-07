class baselayout::mac inherits baselayout::unix {

    if $testuser {
        # On Mac, we can't safely create the test user ourselves.
        #
        # The puppet Mac user provider does not seem to set up everything
        # in the same way as setting up through the UI (for example, it lacks
        # `managehome' feature).
        #
        # Also, there doesn't seem to be a cleanly scriptable way to enable
        # automated login. Therefore, we'll output to syslog that the
        # maintainer of this test machine must manually set up the test
        # user before we can continue.

        $user_exists = generate('/bin/sh', '-c', " \
            if /bin/test -d /Users/$testuser; then \
                /bin/echo -n ok;                   \
            else                                   \
                /bin/echo -n nok;                  \
            fi                                     \
        ")

        if $user_exists == "nok" {
            fail("                                                \
Sorry, some manual setup must be done before puppet can continue! \
Please use the GUI to create a user named `$testuser' and enable  \
automatic login for that user.                                    \
            ")
        }
    }

    File {
        owner   =>  "root",
        group   =>  "wheel",
        mode    =>  0444,
    }

    file {
        "/etc/profile":
            source  =>  "puppet:///modules/baselayout/mac/etc_profile",
        ;
        "/etc/profile.d":
            ensure  =>  "directory",
            mode    =>  0555,
        ;
        "/etc/profile.d/99homepath.sh":
            source  =>  "puppet:///modules/baselayout/mac/profile.d/99homepath.sh",
            require =>  File["/etc/profile.d"],
        ;
        
        "/etc/syslog.conf":
            ensure      =>  present,
            source      =>  "puppet:///modules/baselayout/mac/syslog.conf",
        ;

        # Erase any broken global gitconfig.
        # ~/.gitconfig is the one canonical place for git configuration
        "/opt/local/etc/gitconfig":
            ensure  =>  absent,
        ;
    }

    # Disable automatic updates;
    # needs to be done for root and testuser
    exec { "disable root autoupdates":
        command =>  "/usr/sbin/softwareupdate --schedule off",
        onlyif  =>  "/bin/sh -c 'softwareupdate --schedule | grep -q -v \"Automatic check is off\"'",
        user    =>  "root",
    }
    if $testuser {
        exec { "disable $testuser autoupdates":
            command =>  "/usr/sbin/softwareupdate --schedule off",
            onlyif  =>  "/bin/sh -c 'softwareupdate --schedule | grep -q -v \"Automatic check is off\"'",
            user    =>  "$testuser",
        }
    }
}

