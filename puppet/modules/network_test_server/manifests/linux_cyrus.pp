class network_test_server::linux::cyrus {

    package {
        "cyrus-imapd-2.2":  ensure  =>  present;
    }

    service {
        "cyrus2.2":
            enable  =>  true,
            ensure  =>  running,
            require =>  [ Package["cyrus-imapd-2.2"], File["/etc/cyrus.conf"] ],
        ;
    }

    file {
        "/etc/cyrus.conf":
            source  =>  "puppet:///modules/network_test_server/config/cyrus/cyrus.conf",
            require =>  Package["cyrus-imapd-2.2"],
            notify  =>  Service["cyrus2.2"],
        ;
        "/etc/imapd.conf":
            source  =>  "puppet:///modules/network_test_server/config/cyrus/imapd.conf",
            require =>  Package["cyrus-imapd-2.2"],
            notify  =>  Service["cyrus2.2"],
        ;
    }
}

