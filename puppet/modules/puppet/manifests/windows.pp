class puppet::windows {
    $qtqadir = "c:\\qtqa"
    $sysadmindir = "$qtqadir\\sysadmin"
    $cmd = "c:\\Windows\\system32\\cmd.exe"

    filebucket { 'main':
        path => "$qtqadir\\puppet-backup",
    }

    file {
        $qtqadir:
            ensure  =>  directory;
        $sysadmindir:
            ensure  =>  directory;
        "$qtqadir\\bin":
            ensure  =>  directory,
            require =>  File[$qtqadir];
        "$qtqadir\\bin\\qtqa-manage-lnk.pl":
            source  =>  "puppet:///modules/puppet/qtqa-manage-lnk.pl",
            require =>  File["$qtqadir\\bin"],
            mode    =>  0755;
    }

    # we do not want to use the puppet service installed by default
    service { "puppet":
        ensure => 'stopped',
        enable => false,
    }

    # FIXME: we should be using the "scheduled_task" resource type for this.
    #
    # This bug prevents us from doing that at the moment:
    # https://projects.puppetlabs.com/issues/13008
    #
    # Also note we execute every 30 minutes as opposed to every 15 minutes on *nix.
    # The reason for this is simply that puppet is slower and more memory-hungry
    # on Windows, therefore it's good to run it a bit less frequently.
    #
    $puppet_schtask = 'qtqa_puppet'
    exec { 'make puppet scheduled task':
        command => "$cmd /C \"\
            \
            schtasks /Create \
              /RU SYSTEM \
              /SC MINUTE \
              /MO 30 \
              /TN $puppet_schtask \
              /TR $sysadmindir\\puppet\\sync_and_run.bat \
            \
        ",
        logoutput => true,
        onlyif => "$cmd /C \"\
            \
            schtasks /query /tn $puppet_schtask & \
            if errorlevel 1 (exit /b 0) else exit /b 1\
            \
        "
    }

    # On Nokia LAN, fetch the private overlay.
    if $::domain =~ /\.nokia\.com\b/ {
        $privatedir = "$sysadmindir\\puppet\\private"
        exec { "git clone private sysadmin":
            # Note: we cannot use `--branch' option to `git clone' here, because we are not
            # guaranteed to have new enough git everywhere :-(
            command     =>  "$cmd /C \
    \
    rd /S /Q $privatedir & \
    git clone git://scm.dev.nokia.troll.no/qa-dungeon/sysadmin.git $privatedir && \
    cd $privatedir && \
    git checkout -b private origin/private && \
    echo 1 > .git\\PUPPET_CHECKOUT_COMPLETE \
    \
            ",
            require     =>  File[$sysadmindir],
            creates     =>  "$privatedir/.git/PUPPET_CHECKOUT_COMPLETE",
        }
    }
}
