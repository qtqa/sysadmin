class scratchbox_baselayout::linux {
    $homepath = "/scratchbox/users/$testuser/home/$testuser"

    File {
        owner   =>  $testuser,
        group   =>  $testgroup,
        mode    =>  0644,
    }

    file {
        "$homepath/.gitconfig":
            content =>  template("baselayout/gitconfig.erb"),
        ;
        "$homepath/.profile":
            source  =>  "puppet:///modules/scratchbox_baselayout/dot_profile",
        ;
    }
}

