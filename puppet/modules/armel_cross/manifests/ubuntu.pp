class armel_cross::ubuntu
{
    $mesa_dev_armel_cross_version = '7.11.2-0qtqa7'

    package { "mesa-dev-armel-cross":
        ensure      =>  $mesa_dev_armel_cross_version,
        require     =>  File["/etc/apt/sources.list.d/armel_cross-qtqa.list"],
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



