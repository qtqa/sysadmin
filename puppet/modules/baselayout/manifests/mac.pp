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
            crit("                                                \
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
        "/etc/launchd.conf":
            ensure      =>  present,
            source      =>  "puppet:///modules/baselayout/mac/launchd.conf",
        ;

        # Erase any broken global gitconfig.
        # ~/.gitconfig is the one canonical place for git configuration
        "/opt/local/etc/gitconfig":	# macports git
            ensure  =>  absent,
        ;
        "/etc/gitconfig":               # xcode git
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

    # Allow core dumps to work on Mac; see Technical Note TN2124 for details.
    # /cores must exist and be writable by an unprivileged user ...
    file { "/cores":
        ensure  =>  directory,
        mode    =>  1777,
    }

    # ... and must be cleaned up regularly (note that core dumps can
    # be very large, typically much larger than e.g. on Linux).
    # Here we delete anything which is 1 day old or more.
    cron { "clean cores":
        command =>  "/usr/bin/find /cores -type f -mtime +0 -delete",
        user    =>  root,
        minute  =>  [ 20 ],  # hourly at 20 past the hour
        require =>  File["/cores"],
    }

    if ($macosx_productversion_major == "10.7") {

        # Make sure java is installed (OSX 10.7 only; earlier have it by default)
        $javadmg = "JavaForMacOSX10.7.dmg"
        $javavol = "/Volumes/Java for Mac OS X 10.7"
        $javapkg = "$javavol/JavaForMacOSX10.7.pkg"

        exec { "install java":
            # note, initial detach is in case the volume was mounted from a previous attempt
            command     =>  "/bin/sh -c '

                hdiutil detach \"$javavol\"             ;
                curl -O \"$input/mac/$javadmg\"         &&
                hdiutil attach \"./$javadmg\"           &&
                installer -pkg \"$javapkg\" -target /   &&
                hdiutil detach \"$javavol\"             &&
                rm -f \"./$javadmg\"

            '",
            path        =>  "/usr/bin:/bin:/usr/sbin:/sbin",
            creates     =>  "/Library/Java/Home/bin/java",
            logoutput   =>  "true",
        }

        # Make sure we never keep "Saved Application States".
        # This is Lion's "Resume" feature.
        #
        # This can cause the terminal containing the Pulse agent to be restarted after a reboot,
        # as well as being started normally, so there will be left +1 terminal window after each
        # reboot.  It also potentially screws up the state of autotests, as various QtGui
        # autotests are leaving behind some unknown state here.
        #
        file { "/Users/$testuser/Library/Saved Application State":
            ensure  =>  directory,  # it's a directory ...
            purge   =>  true,       # which should always be empty ...
            recurse =>  true,       # (recursively) ...
            mode    =>  0500,       # and should never be writable
        }
    }
}

