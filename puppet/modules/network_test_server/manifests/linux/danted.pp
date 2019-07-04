class network_test_server::linux::danted {

    if $::operatingsystem == "Ubuntu" {
        # FIXME: tests were written against this ancient dante-server,
        # and they now fail against current versions.
        # For compatibility with the pre-puppet test server, we'll use this
        # old version too, but the tests should really be fixed.
        $dante_deb = $architecture ? {
            i386    =>  'dante-server_1.1.14-2_i386.deb',
            x86_64  =>  'dante-server_1.1.14-2_ia64.deb',
            default =>  err("architecture $architecture is not supported!"),
        }

        exec { "fetch old dante-server":
            creates =>  "/tmp/$dante_deb",
            command =>  "/usr/bin/wget -O /tmp/$dante_deb http://snapshot.debian.org/archive/debian/20050312T000000Z/pool/main/d/dante/$dante_deb",
        }

        package { "dante-server":
            provider    =>  dpkg,
            ensure      =>  latest,
            source      =>  "/tmp/$dante_deb",
            require     =>  [ Exec["fetch old dante-server"], File["/etc/init.d/danted-authenticating"] ],
        }
    }
    else {
        # Note: OS other than Ubuntu are also likely to hit the above issue.
        # Fix it as needed.
        package { "dante-server":
            ensure  =>  present,
            require =>  File["/etc/init.d/danted", "/etc/init.d/danted-authenticating"],
        }
    }

    service {
        "danted":
            enable  =>  true,
            ensure  =>  running,
            hasstatus=> false,
            require =>  Package["dante-server"],
        ;
        "danted-authenticating":
            enable  =>  true,
            ensure  =>  running,
            hasstatus=> false,
            require =>  Package["dante-server"],
        ;
    }

    file {
        "/etc/danted.conf":
            source  =>  "puppet:///modules/network_test_server/config/danted/danted.conf",
            require =>  Package["dante-server"],
            notify  =>  Service["danted"],
        ;
        "/etc/danted-authenticating.conf":
            source  =>  "puppet:///modules/network_test_server/config/danted/danted-authenticating.conf",
            require =>  Package["dante-server"],
            notify  =>  Service["danted-authenticating"],
        ;
        "/etc/init.d/danted":
            source  =>  "puppet:///modules/network_test_server/init/danted",
        ;
        "/etc/init.d/danted-authenticating":
            source  =>  "puppet:///modules/network_test_server/init/danted",
        ;
        "/etc/logrotate.d/sockd":
            source  =>  "puppet:///modules/network_test_server/logrotate.d/sockd",
        ;
        "/etc/logrotate.d/sockd-authenticating":
            source  =>  "puppet:///modules/network_test_server/logrotate.d/sockd-authenticating",
    }

}

