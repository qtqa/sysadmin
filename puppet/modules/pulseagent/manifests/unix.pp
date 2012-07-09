class pulseagent::unix {
    $homedir = $operatingsystem ? {
        Darwin  =>  "/Users/$testuser",
        Solaris =>  "/export/home/$testuser",
        default =>  "/home/$testuser",
    }
    $tar = $operatingsystem ? {
        Solaris =>  "/usr/sfw/bin/gtar",
        default =>  "tar",
    }
    $tar_strip = $operatingsystem ? {
        Solaris =>  "--strip-path",
        default =>  "--strip-components",
    }
    $fetch_to_stdout = $operatingsystem ? {
        Darwin  =>  "curl",
        default =>  "wget -O -",
    }
    $pulseagent_dir = "$homedir/pulse-agent"

    file { "pulseagent directory":
        name    =>  $pulseagent_dir,
        ensure  =>  directory,
        owner   =>  "$testuser",
        group   =>  "$testgroup",
    }

    file { "$homedir/.pulse2-agent":
        ensure  =>  directory,
        owner   =>  $testuser,
        group   =>  $testgroup,
    }

    exec { "install pulseagent":
        subscribe   =>  File["pulseagent directory"],
        # NOTE! tar might use --strip-path or --strip-components, we don't know which in advance.
        # Just try both.
        command     =>  "/bin/sh -c '$fetch_to_stdout $input/pulse-agent-2.1.26.tar.gz > $homedir/pulse-agent-install.tar.gz && { $tar -xvzf $homedir/pulse-agent-install.tar.gz -C $pulseagent_dir --strip-component 1 || $tar -xvzf $homedir/pulse-agent-install.tar.gz -C $pulseagent_dir --strip-path 1; } && chown -R $testuser:$testgroup $pulseagent_dir'",
        creates     =>  "$homedir/pulse-agent/bin/pulse",
    }

    $pulsescript = "$homedir/pulse-agent.sh"

    if $pulseagent_short_datadir {
        file {
            "pulseagent build directory":
                name    =>  "/build",
                ensure  =>  directory,
                owner   =>  $testuser,
                group   =>  $testgroup,
            ;
            "pulseagent configuration":
                name    =>  "$homedir/.pulse2-agent/config.properties",
                ensure  =>  present,
                owner   =>  $testuser,
                group   =>  $testgroup,
                source  =>  "puppet:///modules/pulseagent/config.properties",
                mode    =>  0644,
                require =>  File["$homedir/.pulse2-agent"],
            ;
         }
    }

    file { "pulseagent script":
        name    =>  $pulsescript,
        ensure  =>  present,
        owner   =>  "$testuser",
        group   =>  "$testgroup",
        source  =>  "puppet:///modules/pulseagent/pulse-agent.sh",
        mode    =>  0755,
    }
}

