class meego_osc::ubuntu {
    
    if (!$testuser) {
        fail("testuser should be set before including meego_osc")
    }

    $home = "/home/$testuser"

    # This file needs to be secret because it contains build.meego.com
    # user credentials
    secret_file { "$home/.oscrc":
        source      =>  "meego/meego_oscrc",
    }
    file { "$home/.oscrc":
        owner   =>  $testuser,
        mode    =>  0644,
        require =>  Secret_file["$home/.oscrc"],
    }
    file {
        "$home/.config/osc":
            ensure  =>  directory,
            require =>  File["$home/.config"],
        ;
        "$home/.config/osc/trusted-certs":
            ensure  =>  directory,
            require =>  File["$home/.config/osc"],
        ;
        "$home/.config/osc/trusted-certs/api.meego.com_443.pem":
            source  =>  "puppet:///modules/meego_osc/api.meego.com_443.pem",
            require =>  File["$home/.config/osc/trusted-certs"],
        ;
    }

    # Allow passwordless sudo for /usr/bin/build
    file { "/etc/sudoers.d/10_${testuser}_meego_osc":
        owner   =>  "root",
        mode    =>  0440,
        content =>  template("meego_osc/sudoers_passwordless_build.erb"),
    }

    exec { "meego-osc apt-update":
        command     => "/usr/bin/apt-get update",
        refreshonly => true,
    }
    file {
        "/etc/apt/sources.list.d/meego-osc.list":
            source  =>  "puppet:///modules/meego_osc/meego-osc.list",
            notify  =>  Exec["meego-osc apt-update"],
            require =>  File["/etc/apt/apt.conf.d/90nokia-meego-osc"],
        ;
        "/etc/apt/apt.conf.d/90nokia-meego-osc":
            source  =>  "puppet:///modules/meego_osc/90nokia-meego-osc.apt-conf",
        ;
    }

    Package {
        require =>  File["/etc/apt/sources.list.d/meego-osc.list"],
    }
    package {
        "osc":                      ensure  =>  present;
        "build":                    ensure  =>  present;
        # For ARM builds
        "qemu-kvm-extras-static":   ensure  =>  present;
        # For `make spec'
        "meego-packaging-tools":    ensure  =>  present;
    }
    
    # modules used by obs-dispatch.pl
    include cpan
    cpan_package {
        "Parse::RPM::Spec":;
        "Text::Diff":;
    }
}

