class scratchbox_pulseagent::linux {
    $homepath = "/home/$testuser"
    $sbhomepath = "/scratchbox/users/$testuser/home/$testuser"
    $javapath = "$sbhomepath/pulse_java"
    $pulsepath = "$sbhomepath/pulse-agent"

    # This is basically just a tarred up JVM, but the `java' binary has been
    # hacked to use .interp as /lib/ld-host.so, to skip the qemu CPU transparency
    exec { "install java":
        command =>  "/bin/sh -c \"wget $input/java/pulse_scratchbox_jre_1.6.0_15.tar.gz -O - | tar -C $sbhomepath -xvz\"",
        timeout =>  1200,
        creates =>  "$javapath/bin/java",
    }

    exec { "/bin/chown -R $testuser:$testgroup $javapath":
        subscribe   =>  Exec["install java"],
        refreshonly =>  true,
    }

    file { $pulsepath:
        ensure  =>  directory,
        owner   =>  $testuser,
        group   =>  $testgroup,
    }

    file { "$sbhomepath/pulse-agent.sh":
        ensure  =>  absent,
    }

    file { "$homepath/pulse-agent.sh":
        source  =>  "puppet:///modules/pulseagent/pulse-agent.sh",
        owner   =>  $testuser,
        group   =>  $testgroup,
    }

    exec { "install pulse":
        command  =>  "/bin/sh -c \"wget $input/pulse-agent-2.1.26.tar.gz -O - | tar -C $pulsepath --strip-component 1 -xvz\"",
        timeout  =>  1200,
        creates  =>  "$pulsepath/bin/pulse",
        require  => File[$pulsepath],
    }

    exec { "/bin/chown -R $testuser:$testgroup $pulsepath":
        subscribe   =>  Exec["install pulse"],
        refreshonly =>  true,
    }

    startup { "pulseagent":
        command =>  '$HOME/pulse-agent.sh -scratchbox',
        require =>  File["$homepath/pulse-agent.sh"],
        user    =>  $testuser,
    }
}
