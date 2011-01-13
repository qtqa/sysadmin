class baselayout::mac inherits baselayout::unix {

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

