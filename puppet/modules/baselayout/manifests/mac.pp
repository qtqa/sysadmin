class baselayout::mac inherits baselayout::unix {

    if $baselayout::testuser {
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
            if /bin/test -d /Users/$baselayout::testuser; then \
                /bin/echo -n ok;                   \
            else                                   \
                /bin/echo -n nok;                  \
            fi                                     \
        ")

        if $user_exists == "nok" {
            crit("                                                \
Sorry, some manual setup must be done before puppet can continue! \
Please use the GUI to create a user named `$baselayout::testuser' and enable  \
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
    if $baselayout::testuser {
        exec { "disable $baselayout::testuser autoupdates":
            command =>  "/usr/sbin/softwareupdate --schedule off",
            onlyif  =>  "/bin/sh -c 'softwareupdate --schedule | grep -q -v \"Automatic check is off\"'",
            user    =>  "$baselayout::testuser",
        }
    }

    exec { "disable system sleep timer":
        command =>  "/usr/bin/pmset sleep 0",
        unless  =>  "/bin/sh -c '/usr/bin/pmset -g | /usr/bin/egrep -q '[[:space:]]sleep[[:space:]]+0''",
        user    =>  "root",
    }
    exec { "enable autorestart after power failure":
        command =>  "/usr/bin/pmset autorestart 1",
        unless  =>  "/bin/sh -c '/usr/bin/pmset -g | /usr/bin/egrep -q '[[:space:]]autorestart[[:space:]]+1''",
        user    =>  "root",
    }
    exec { "disable display sleep timer":
        command =>  "/usr/bin/pmset displaysleep 0",
        unless  =>  "/bin/sh -c '/usr/bin/pmset -g | /usr/bin/egrep -q '[[:space:]]displaysleep[[:space:]]+0''",
        user    =>  "root",
    }

    if $baselayout::testuser {
        exec { "disable $baselayout::testuser screensaver":
            command =>  "/usr/bin/defaults -currentHost write com.apple.screensaver idleTime 0",
            unless  =>  "/bin/sh -c '/usr/bin/defaults -currentHost read com.apple.screensaver idleTime | /usr/bin/grep -q '^0$''",
            user    =>  "$baselayout::testuser",
        }
        exec { "set correct locale":
            command =>  "/usr/bin/defaults write -g AppleLocale en_US",
            unless  =>  "/bin/sh -c '/usr/bin/defaults read -g AppleLocale | /usr/bin/grep -q 'en_US''",
            user    =>  "$baselayout::testuser",
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

    # make sure 'at' jobs can be used
    service { "com.apple.atrun":
        ensure => running,
        enable => true,
    }

    if ($macosx_productversion_major >= "10.7") {

        # Make sure java is installed (OSX 10.7 only; earlier have it by default)
        $javadmg = "JavaForOSX.dmg"
        $javavol = "/Volumes/Java for OS X 2012-006"
        $javapkg = "$javavol/JavaForOSX.pkg"

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
        file { "/Users/$baselayout::testuser/Library/Saved Application State":
            ensure  =>  directory,  # it's a directory ...
            purge   =>  true,       # which should always be empty ...
            recurse =>  true,       # (recursively) ...
            force   =>  true,       # and delete directories too, not just files ...
            mode    =>  0500,       # and should never be writable
        }
    }

    # On OSX 10.6, we use sudo from macports, because the shipped sudo is too old to understand
    # the '#includedir' directive, which makes it difficult to install sudoers snippets from puppet.
    if $::macosx_productversion_major == '10.6' {
        include macports

        package { "sudo":
            ensure => installed,
            provider => 'macports',
            before => Exec["Ensure sudoers.d is enabled"],
            require => Exec["Sync macport trees"],
        }

        # make sure 'sudo' always invokes this sudo
        file { "/usr/bin/sudo":
            ensure => "/opt/local/bin/sudo",
            require => Package["sudo"]
        }
    }
}

