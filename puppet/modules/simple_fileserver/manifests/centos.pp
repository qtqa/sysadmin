class simple_fileserver::centos inherits simple_fileserver::linux {
    package { "httpd":
        ensure      =>  present,
    }

    #file { "/etc/httpd/conf.d/simple_fileserver.conf":
    #    ensure      =>  present,
    #    source      =>  "puppet:///modules/simple_fileserver/centos/simple_fileserver.conf",
    #    require     =>  Package["httpd"],
    #}

    file { "/var/www/html":
        ensure      =>  "/home/qt/htdocs",
        require     =>  File["/home/qt/htdocs"],
        force       =>  true,
    }

    service { "httpd":
        ensure      =>  running,
        enable      =>  true,
        subscribe   =>  File["/var/www/html"],
    }
}
