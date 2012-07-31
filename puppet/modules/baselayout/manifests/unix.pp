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
    }
}

