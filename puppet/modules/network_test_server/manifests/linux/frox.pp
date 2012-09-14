class network_test_server::linux::frox {

    package {
        "frox":     ensure  =>  present;
    }

    service {
        "frox":
            enable  =>  true,
            ensure  =>  running,
            require =>  [ Package["frox"], File["/etc/default/frox", "/etc/frox.conf" ] ],
        ;
    }

    file {
        "/etc/default/frox":
            source  =>  "puppet:///modules/network_test_server/config/frox/etc_default_frox",
            require =>  Package["frox"],
            notify  =>  Service["frox"],
        ;
        "/etc/frox.conf":
            source  =>  "puppet:///modules/network_test_server/config/frox/frox.conf",
            require =>  Package["frox"],
            notify  =>  Service["frox"],
        ;
    }
}

