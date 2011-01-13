class baselayout::linux inherits baselayout::unix {
    if $testuser {
        include kdesettings

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

    if $testuser {
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

# Run a command at startup.
# Uses freedesktop $HOME/.config/autostart, which seems to be supported on
# most Linux for several years
define startup($command, $user) {
    file { "/home/$user/.config/autostart/$name.desktop":
        ensure  =>  present,
        owner   =>  $user,
        content =>  template("baselayout/xdg-autostart.desktop.erb"),
        require =>  File["/home/$user/.config/autostart"],
    }
}
