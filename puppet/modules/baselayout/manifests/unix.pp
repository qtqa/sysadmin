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
        file { "$homepath/.gitconfig":
            content =>  template("baselayout/gitconfig.erb"),
            owner   =>  $testuser,
            group   =>  $testgroup,
            mode    =>  0644,
        }

        file { "$homepath/bin":
            ensure  =>  directory,
            mode    =>  0755,
            owner   =>  $testuser,
            group   =>  $testgroup,
        }
    }
}

