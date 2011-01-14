class puppet::unix {
    # Hacky: for some systems we want our cron jobs to get some extra dirs
    # added to PATH
    $env = $operatingsystem ? {
        Solaris =>  "/usr/bin/env PATH=\${PATH}:/opt/csw/bin:/usr/local/bin",
        Darwin  =>  "/usr/bin/env PATH=\${PATH}:/opt/local/bin:/usr/local/bin",
        default =>  "",
    }

    $git = $operatingsystem ? {
        Solaris =>  "$env git",
        Darwin  =>  "/opt/local/bin/git",
        default =>  "/usr/bin/git",
    }

    $qtqadir = "/var/qtqa"
    $sysadmindir = "$qtqadir/sysadmin"

    file { $qtqadir:
        ensure  =>  directory,
    }

    # NOTE: if you do not have access to scm.dev.nokia.troll.no (e.g. you are
    # not on Nokia LAN), then perform this step manually, using the repo that
    # you _do_ have access to
    exec { "git clone sysadmin":
        command     =>  "$git clone git://scm.dev.nokia.troll.no/qa-dungeon/sysadmin.git $sysadmindir",
        require     =>  File[$qtqadir],
        creates     =>  "$sysadmindir/puppet",
    }

    file { $sysadmindir:
        require     =>  Exec["git clone sysadmin"],
    }

    $puppetrun = $operatingsystem ? {
        Solaris =>  "$env $sysadmindir/puppet/sync_and_run.sh",
        Darwin  =>  "$env $sysadmindir/puppet/sync_and_run.sh",
        default =>  "$sysadmindir/puppet/sync_and_run.sh",
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

    # On Nokia LAN, fetch the private overlay.
    if $domain =~ /\.nokia\.com$/ {
        $privatedir = "$sysadmindir/puppet/private"
        exec { "git clone private sysadmin":
            # Note: we cannot use `--branch' option to `git clone' here, because we are not
            # guaranteed to have new enough git everywhere :-(
            command     =>  "/bin/sh -c '
            
    rm -rf $privatedir &&
    $git clone git://scm.dev.nokia.troll.no/qa-dungeon/sysadmin.git $privatedir &&
    cd $privatedir &&
    git checkout -b private origin/private &&
    touch .git/PUPPET_CHECKOUT_COMPLETE
    
            '",
            require     =>  File[$sysadmindir],
            creates     =>  "$privatedir/.git/PUPPET_CHECKOUT_COMPLETE",
        }
    }
}
