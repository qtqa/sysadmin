class network_test_server::linux::vsftpd {

    package {
        "vsftpd":           ensure  =>  present;
    }

    service {
        "vsftpd":
            enable  =>  true,
            ensure  =>  running,
            require =>  [ Package["vsftpd"], File["/home/qt-test-server/ftp"] ],
        ;
    }

    user {
        "ftp":
            ensure      =>  present,
            home        =>  "/home/qt-test-server/ftp",
            require     =>  Package["vsftpd"],
        ;

        "ftptest":
            ensure      =>  present,
            home        =>  "/home/qt-test-server/ftp",
            require     =>  [ Package["mkpasswd"], Package["vsftpd"] ],
            password    =>  mkpasswd('OfmgZrrC', 'password'),
        ;
    }

    file {
        "/etc/vsftpd.conf":
            source  =>  "puppet:///modules/network_test_server/config/vsftpd/vsftpd.conf",
            require =>  Package["vsftpd"],
            notify  =>  Service["vsftpd"],
        ;
        "/etc/vsftpd.user_list":
            source  =>  "puppet:///modules/network_test_server/config/vsftpd/user_list",
            require =>  Package["vsftpd"],
            notify  =>  Service["vsftpd"],
        ;
        "/home/qt-test-server/ftp":
            source  =>  "puppet:///modules/network_test_server/ftp",
            recurse =>  remote,
            require =>  User["qt-test-server"],
        ;
        "/var/ftp":
            ensure  =>  "/home/qt-test-server/ftp",
            require =>  File["/home/qt-test-server/ftp"],
        ;

        # testdata with special permissions
        "/home/qt-test-server/ftp/pub/file-not-readable.txt":
            source  =>  "puppet:///modules/network_test_server/ftp/pub/file-not-readable.txt",
            mode    =>  0600,
            require =>  File["/home/qt-test-server/ftp"],
        ;

        # ftp incoming dir
        "/home/qt-test-server/ftp/qtest/upload":
            ensure  =>  directory,
            mode    =>  1777,
            require =>  File["/home/qt-test-server/ftp"],
        ;
    }
}

