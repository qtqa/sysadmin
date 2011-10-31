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

# Run a command at startup.
# Uses freedesktop $HOME/.config/autostart, which seems to be supported on
# most Linux for several years
# Additional $terminal variable to fix execution issue on Ubuntu 11.10,
# as it is broken on 11.10 (according to apt-file search nothing provides
# xdg-terminal).
define startup($command, $user, $terminal=false) {
    file { "/home/$user/.config/autostart/$name.desktop":
        ensure  =>  present,
        owner   =>  $user,
        mode    =>  0755,
        content =>  template("baselayout/xdg-autostart.desktop.erb"),
        require =>  File["/home/$user/.config/autostart"],
    }
}
