class baselayout::unix {
    if $testuser {
        $homepath = $operatingsystem ? {
            Darwin  =>  "/Users/$testuser",
            Solaris =>  "/export/home/$testuser",
            default =>  "/home/$testuser",
        }

        #*
        # Enforce correct git configuration
        #*
        Git::Config {
            user => $testuser,
        }

        git::config {
            "url.$qtgitreadonly.insteadof": content => "qtgitreadonly:";
            "qtqa.hardgit.cachedir": content => "~/.git_object_cache";
            "qtqa.hardgit.location": content => $location;
            "qtqa.hardgit.server.qtgitreadonly.primary": content => $qtgitreadonly;
            "qtqa.hardgit.server.qtgitreadonly.mirror-$location": content => $qtgitreadonly_local;
            "core.autocrlf": content => "false";
            "user.name": content => "Qt Continuous Integration System";
            "user.email": content => "qt-info@nokia.com";
        }

        file { "$homepath/bin":
            ensure  =>  directory,
            mode    =>  0755,
            owner   =>  $testuser,
            group   =>  $testgroup,
        }
    }
}

