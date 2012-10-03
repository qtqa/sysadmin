class baselayout::unix inherits baselayout::base {
    if $baselayout::testuser {
        $homepath = $::operatingsystem ? {
            Darwin  =>  "/Users/$baselayout::testuser",
            Solaris =>  "/export/home/$baselayout::testuser",
            default =>  "/home/$baselayout::testuser",
        }

        Git::Config {
            user => $baselayout::testuser,
        }

        git::config {
            "qtqa.hardgit.cachedir": content => "~/.git_object_cache";
            "core.autocrlf": content => "false";
        }

        file { "$homepath/bin":
            ensure  =>  directory,
            mode    =>  0755,
            owner   =>  $baselayout::testuser,
            group   =>  $baselayout::testgroup,
        }

        $rootgroup = $::operatingsystem ? {
            Darwin => 'wheel',
            default => 'root',
        }

        file { "/etc/sudoers.d":
            ensure  =>  directory,
            mode    =>  0755,
            owner   =>  root,
            group   =>  $rootgroup,
        }

        $grep = $::operatingsystem ? {
            Darwin => '/usr/bin/grep',
            default => '/bin/grep',
        }

        # On OSX 10.6, we use sudo from macports, which uses a different prefix for sudoers configs;
        # for the sake of convenience, we keep using /etc/sudoers.d
        $sudoers_prefix = $::macosx_productversion_major ? {
            '10.6' => "/opt/local",
            default => ""
        }
        exec { "Ensure sudoers.d is enabled":
            command => "/bin/sh -c 'echo \"#includedir /etc/sudoers.d\" >> $sudoers_prefix/etc/sudoers'",
            unless  => "$grep -F '#includedir /etc/sudoers.d' $sudoers_prefix/etc/sudoers",
            require => File["/etc/sudoers.d"]
        }
    }
}

