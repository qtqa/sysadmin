class scratchbox::debian inherits scratchbox::linux {
    exec { "scratchbox apt-update":
        command     => "/usr/bin/apt-get update",
        refreshonly => true,
    }

    file {
        "/etc/apt/sources.list.d/nokia-scratchbox.list":
            content =>  template("scratchbox/nokia-scratchbox.list.erb"),
            notify  =>  Exec["scratchbox apt-update"],
            require =>  File["/etc/apt/apt.conf.d/90nokia-scratchbox"],
        ;
        "/etc/apt/apt.conf.d/90nokia-scratchbox":
            source  =>  "puppet:///modules/scratchbox/90nokia-scratchbox.apt-conf",
        ;
    }

    Package {
        require => File["/etc/apt/apt.conf.d/90nokia-scratchbox", "/etc/apt/sources.list.d/nokia-scratchbox.list"],
    }

    package {
        "osso-scratchbox":  ensure  =>  latest;
        "maemo-assistant":  ensure  =>  latest;
        "scratchbox-devkit-git":        ensure => latest;
        "scratchbox-devkit-doctools":   ensure => latest;
        "scratchbox-devkit-apt-https":  ensure => latest;
        # Note: osso-scratchbox will probably imply these eventually,
        # but at time of writing, it doesn't
        "scratchbox-toolchain-cs2009q3-eglibc2.10-armv7-soft":  ensure => latest;
        "scratchbox-toolchain-cs2009q3-eglibc2.10-i486":        ensure => latest;
        "scratchbox-toolchain-cs2009q3-eglibc2.10-armv7-hard":  ensure => latest;
    }
}

