class pulseserver::linux inherits pulseserver::unix {
    exec { "/bin/sh -c 'wget $input/java/jdk-6u21-linux-i586.rpm -O /tmp/jdk-install.rpm && /usr/bin/yum --nogpgcheck -y -d 0 -e 0 localinstall /tmp/jdk-install.rpm && rm /tmp/jdk-install.rpm'":
        creates     =>  "/usr/bin/java",
    }

    # Use lighttpd for simple port 80 -> 8080 redirect.
    package { "lighttpd":
        ensure => present,
    }

    file { "/etc/lighttpd/conf.d/pulseserver-redirect.conf":
        ensure => present,
        content => template("pulseserver/lighttpd-redirect.conf.erb"),
        require => Package["lighttpd"],
    }

    file { "/etc/lighttpd/lighttpd.conf":
        ensure => present,
        source => "puppet:///modules/pulseserver/lighttpd.conf",
        require => Package["lighttpd"],
    }

    file { "/etc/security/limits.conf":
        ensure => present,
        source => "puppet:///modules/pulseserver/limits.conf",
    }

    file { "/var/www/lighttpd":
        ensure => directory,
    }

    service { "lighttpd":
        subscribe => File["/etc/lighttpd/lighttpd.conf", "/etc/lighttpd/conf.d/pulseserver-redirect.conf", "/var/www/lighttpd"],
        ensure    => running,
        enable    => true,
    }

    service { "iptables":
        ensure    => stopped,
        enable    => false,
    }

    file { "/etc/init.d/pulseserver":
        ensure => present,
        source => "puppet:///modules/pulseserver/pulseserver.init",
    }

    service { "pulseserver":
        ensure    => running,
        enable    => true,
        hasstatus => true,
        require   => File["/etc/init.d/pulseserver"],
    }
}

