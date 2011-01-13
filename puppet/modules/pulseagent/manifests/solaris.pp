class pulseagent::solaris inherits pulseagent::unix {
    File {
        owner   =>  "root",
        group   =>  "sys",
        mode    =>  0644,
    }

    file {
        "/var/svc/manifest/site/pulseagent.xml":
            ensure  =>  present,
            source  =>  "puppet:///modules/pulseagent/solaris/pulseagent.xml",
        ;
        "$homedir/svc-pulseagent":
            ensure  =>  present,
            source  =>  "puppet:///modules/pulseagent/solaris/svc-pulseagent",
            mode    =>  0755,
        ;
    }

    exec { "svccfg import":
        name        =>  "/usr/sbin/svccfg import /var/svc/manifest/site/pulseagent.xml",
        subscribe   =>  File["/var/svc/manifest/site/pulseagent.xml","$homedir/svc-pulseagent"],
        refreshonly =>  true,
    }

    service { "pulseagent":
        ensure      =>  running,
        enable      =>  true,
        subscribe   =>  Exec["svccfg import"],
    }

}

