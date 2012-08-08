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

        exec { "Ensure sudoers.d is enabled":
            command => "/bin/sh -c 'echo \"#includedir /etc/sudoers.d\" >> /etc/sudoers'",
            unless  => "$grep -F '#includedir /etc/sudoers.d' /etc/sudoers",
            require => File["/etc/sudoers.d"]
        }
    }
}

