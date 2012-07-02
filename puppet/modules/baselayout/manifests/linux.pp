class baselayout::linux inherits baselayout::unix {

    # Do not use KDE since Ubuntu 11
    if $operatingsystem == "Ubuntu" and $lsbmajdistrelease < 11 {
        $use_kde = 1
    }

    if $testuser {

        if $use_kde {
            include kdesettings
        }

        user { $testuser:
            ensure      =>  present,
            uid         =>  1100,
            gid         =>  $testgroup,
            home        =>  "/home/$testuser",
            managehome  =>  true,
        }
    }

    if $testuser {
        file {
            "/home/$testuser":
                ensure  =>  directory,
                owner   =>  $testuser,
                group   =>  $testgroup,
            ;
            "/home/$testuser/.config":
                ensure  =>  directory,
                owner   =>  $testuser,
                group   =>  $testgroup,
            ;
            "/home/$testuser/.config/autostart":
                ensure  =>  directory,
                owner   =>  $testuser,
                group   =>  $testgroup,
                require =>  File["/home/$testuser/.config"],
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

    $kde = $operatingsystem ? {
        Ubuntu      =>  "kde-full",
        default     =>  "kde",
    }

    $ssh = $operatingsystem ? {
        CentOS      =>  "openssh",
        default     =>  "ssh",
    }
    $sshd = $operatingsystem ? {
        CentOS      =>  "sshd",
        default     =>  "ssh",
    }

    package {
        "$ssh":  ensure => installed;
    }

    if $testuser and $use_kde {
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
