# Hacked up blastwave installer function.
# Built-in blastwave package provider is not working for us 
define csw_package($ensure) {
    if ($ensure == "present") or ($ensure == "installed") {
        exec { "/opt/csw/bin/pkgutil -y -i $name":
            creates => "/opt/csw/bin/$name",
        }
    }
    if $ensure == "absent" {
        exec { "/opt/csw/bin/pkgutil -y -r $name":
            onlyif => "/usr/bin/test -f /opt/csw/bin/$name",
        }
    }
}

class baselayout::solaris inherits baselayout::unix {
    if $baselayout::testuser {
        $homedir = "/export/home/$baselayout::testuser"

        file { "$homedir/local.profile":
            ensure      =>  present,
            owner       =>  $baselayout::testuser,
            group       =>  $baselayout::testgroup,
            source      =>  "puppet:///modules/baselayout/solaris/local.profile",
        }

        file { "$homedir/.profile":
            ensure      =>  present,
            owner       =>  $baselayout::testuser,
            group       =>  $baselayout::testgroup,
            source      =>  "puppet:///modules/baselayout/solaris/local.profile",
        }
    }

    file { "/etc/syslog.conf":
        ensure      =>  present,
        source      =>  "puppet:///modules/baselayout/solaris/syslog.conf",
    }

    file { "/etc/coreadm.conf":
        ensure      =>  present,
        source      =>  "puppet:///modules/baselayout/solaris/coreadm.conf",
    }

    file { "/var/core":
        ensure      =>  directory,
    }

    if $zone == false {
        csw_package {
            "wget": ensure => installed;
        }
    }
}

