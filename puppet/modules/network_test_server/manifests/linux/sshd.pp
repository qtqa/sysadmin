class network_test_server::linux::sshd {

    package { "openssh-server":
        ensure  =>  present,
    }

    service {
        "ssh":
            enable  =>  true,
            ensure  =>  running,
            require =>  Package["openssh-server"],
        ;
    }

}

