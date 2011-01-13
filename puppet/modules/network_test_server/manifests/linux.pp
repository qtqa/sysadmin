class network_test_server::linux {

    include network_test_server::linux::apache2
    include network_test_server::linux::ssl_certs
    include network_test_server::linux::squid
    include network_test_server::linux::danted
    include network_test_server::linux::vsftpd
    include network_test_server::linux::frox
    include network_test_server::linux::xinetd
    include network_test_server::linux::cyrus
    include network_test_server::linux::samba
    include network_test_server::linux::tmpreaper

    user {
        "qt-test-server":
            ensure      =>  present,
            managehome  =>  true,
        ;
        "qsockstest":
            ensure      =>  present,
            home        =>  "/dev/null",
            password    =>  mkpasswd('AtbhQrjz', 'password'),
            require     =>  Package["mkpasswd"],
            shell       =>  "/bin/false",
        ;
    }

    host {
        "qt-test-server.qt-test-net":
            ip => $ipaddress,
            host_aliases => [ "qt-test-server" ],
        ;
        "localhost.localdomain":
            ip => "127.0.0.1",
            host_aliases => [ "localhost" ],
        ;
    }


    package {
        "mkpasswd":         ensure  =>  present;
    }

    file {
        "/home/qt-test-server/passwords":
            source  =>  "puppet:///modules/network_test_server/config/passwords",
            require =>  User["qt-test-server"],
        ;
        "/home/qt-test-server/iptables":
            source  =>  "puppet:///modules/network_test_server/config/iptables",
            require =>  User["qt-test-server"],
            notify  =>  Exec["load iptables config"],
        ;
        "/etc/rc.local":
            source  =>  "puppet:///modules/network_test_server/init/rc.local",
            mode    =>  0755,
        ;
        "/home/writeables":
            ensure  =>  directory,
        ;
    }

    exec { "load iptables config":
        command     =>  "/bin/sh -c '/sbin/iptables-restore < /home/qt-test-server/iptables'",
        refreshonly =>  true,
        subscribe   =>  File["/home/qt-test-server/iptables"],
    }
}

