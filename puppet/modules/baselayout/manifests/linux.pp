class baselayout::linux inherits baselayout::unix {

    # Do not use KDE since Ubuntu 11
    if $::operatingsystem == "Ubuntu" and $::lsbmajdistrelease < 11 {
        $use_kde = 1
    }

    if $baselayout::testuser {

        if $use_kde {
            include kdesettings
        }

        user { $baselayout::testuser:
            ensure      =>  present,
            uid         =>  1100,
            gid         =>  $baselayout::testgroup,
            home        =>  "/home/$baselayout::testuser",
            managehome  =>  true,
        }
    }

    if $baselayout::testuser {
        file {
            "/home/$baselayout::testuser":
                ensure  =>  directory,
                owner   =>  $baselayout::testuser,
                group   =>  $baselayout::testgroup,
            ;
            "/home/$baselayout::testuser/.config":
                ensure  =>  directory,
                owner   =>  $baselayout::testuser,
                group   =>  $baselayout::testgroup,
            ;
            "/home/$baselayout::testuser/.config/autostart":
                ensure  =>  directory,
                owner   =>  $baselayout::testuser,
                group   =>  $baselayout::testgroup,
                require =>  File["/home/$baselayout::testuser/.config"],
            ;
        }
    }

    # Fix up network interface naming
    file {
        "/etc/udev/rules.d/70-persistent-net.rules":
            source  =>  "puppet:///modules/baselayout/linux/etc/udev/rules.d/70-persistent-net.rules",
            owner   =>  "root",
            group   =>  "root",
        ;
    }

    $kde = $::operatingsystem ? {
        Ubuntu      =>  "kde-full",
        default     =>  "kde",
    }

    $ssh = $::operatingsystem ? {
        CentOS      =>  "openssh",
        default     =>  "ssh",
    }
    $sshd = $::operatingsystem ? {
        CentOS      =>  "sshd",
        default     =>  "ssh",
    }

    package {
        "$ssh":  ensure => installed;
    }

    if $baselayout::testuser and $use_kde {
        package {
            "$kde": ensure => installed;
        }
    }

    service { $sshd:
        ensure      =>  running,
        enable      =>  true,
        require     =>  Package[$ssh],
    }
}

# deprecated wrapper for baselayout::startup
define startup($command, $user, $terminal=false) {
    warning("'startup' is deprecated, use 'baselayout::startup'")

    baselayout::startup { $name:
        path => $command,
        user => $user,
        terminal => $terminal,
    }
}
