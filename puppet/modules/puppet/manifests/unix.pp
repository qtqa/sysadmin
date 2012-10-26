class puppet::unix {
    # Hacky: for some systems we want our cron jobs to get some extra dirs
    # added to PATH
    $env = $::operatingsystem ? {
        Solaris =>  "/usr/bin/env PATH=\${PATH}:/opt/csw/bin:/usr/local/bin",
        Darwin  =>  "/usr/bin/env PATH=\${PATH}:/opt/local/bin:/usr/local/bin",
        default =>  "",
    }

    $git = $::operatingsystem ? {
        Solaris =>  "$env git",
        Darwin  =>  $macosx_productversion_major ? {
            # On OSX <  10.7, we use git from macports.
            # On OSX >= 10.7, we use git from xcode which goes to /usr/bin.
            10.5    =>  "/opt/local/bin/git",
            10.6    =>  "/opt/local/bin/git",
            default =>  "/usr/bin/git",
        },
        default =>  "/usr/bin/git",
    }

    $qtqadir = "/var/qtqa"
    $sysadmindir = "$qtqadir/sysadmin"

    file {
        $qtqadir:
            ensure  =>  directory;
        $sysadmindir:
            ensure  =>  directory;
    }

    $puppetrun = $::operatingsystem ? {
        Solaris =>  "$env $sysadmindir/puppet/sync_and_run.pl",
        Darwin  =>  "$env $sysadmindir/puppet/sync_and_run.pl | logger -t puppet -p daemon.error",
        default =>  "$sysadmindir/puppet/sync_and_run.pl | logger -t puppet -p daemon.error",
    }

    $minute1 = fqdn_rand(15)
    $minute2 = 15+$minute1
    $minute3 = 30+$minute1
    $minute4 = 45+$minute1

    cron { "run puppet":
        command =>  $puppetrun,
        user    =>  root,
        minute  =>  [ $minute1, $minute2, $minute3, $minute4 ],
        require =>  File[$sysadmindir],
    }
}
