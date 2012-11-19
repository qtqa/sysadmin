class macports {

    #*
    # Enforce macports configuration
    #*
    file { "macports.conf":
        name    =>  "/opt/local/etc/macports/macports.conf",
        source  =>  "puppet:///modules/macports/macports.conf",
        notify  => Exec["Sync macport trees"],
    }

    file { "sources.conf":
        name    =>  "/opt/local/etc/macports/sources.conf",
        source  =>  "puppet:///modules/macports/sources.conf",
        notify  => Exec["Sync macport trees"],
    }

    #*
    # Put macports apps into PATH by default
    #*
    file { "/etc/profile.d/macports.sh":
        ensure  =>  present,
        source  =>  "puppet:///modules/macports/macports.sh",
    }

    exec { "Sync macport trees":
        command =>  "/opt/local/bin/port -v selfupdate && /opt/local/bin/port sync",
        require => File["macports.conf", "sources.conf"],
        refreshonly => true,
    }
}

