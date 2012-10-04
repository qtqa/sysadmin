class armel_cross::ubuntu
{
    # Everything depends on this
    Package {
        require     =>  exec["apt-get update for armel_cross-qtqa"],
    }

    package {
        "mesa-dev-armel-cross":     ensure => '7.11.2-0qtqa7';          # for QtGui
        "openssl-dev-armel-cross":  ensure => '1.0.0e-0qtqa1';          # for QtNetwork
        "libedit-dev-armel-cross":  ensure => '2.11-20080614-2.2qtqa5'; # for QtJsonDb
    }

    package { "g++-arm-linux-gnueabi":
        ensure      =>  present,
    }

    # Enables the repository providing the desired version of mesa-dev-armel-cross.
    file { "/etc/apt/sources.list.d/armel_cross-qtqa.list":
        ensure      =>  present,
        content     =>  template("armel_cross/armel_cross-qtqa.list.erb"),
        notify      =>  Exec["apt-get update for armel_cross-qtqa"],
    }

    # Runs apt-get update after new .list file provision
    exec { "apt-get update for armel_cross-qtqa":
        command     =>  "/usr/bin/apt-get update",
        refreshonly =>  true,
    }
}



