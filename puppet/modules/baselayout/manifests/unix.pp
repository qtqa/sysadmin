class baselayout::unix inherits baselayout::base {
    if $testuser {
        $homepath = $operatingsystem ? {
            Darwin  =>  "/Users/$testuser",
            Solaris =>  "/export/home/$testuser",
            default =>  "/home/$testuser",
        }

        Git::Config {
            user => $testuser,
        }

        git::config {
            "qtqa.hardgit.cachedir": content => "~/.git_object_cache";
            "core.autocrlf": content => "false";
        }

        file { "$homepath/bin":
            ensure  =>  directory,
            mode    =>  0755,
            owner   =>  $testuser,
            group   =>  $testgroup,
        }
    }
}

