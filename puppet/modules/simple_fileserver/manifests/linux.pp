include sshkeys

class simple_fileserver::linux {
    user { "qt":
        ensure      =>  present,
        uid         =>  1100,
        gid         =>  "users",
        home        =>  "/home/qt",
        managehome  =>  true,
    }

    file { "/home/qt":
        ensure  =>  directory,
        owner   =>  "qt",
        group   =>  "users",
        mode    =>  0755,
        require =>  User["qt"],
    }

    file { "/home/qt/htdocs":
        ensure  =>  directory,
        owner   =>  "qt",
        group   =>  "users",
        mode    =>  0755,
        require =>  User["qt"],
    }

    dropbox_authorized_keys { "ssh keys for qt":
        user    =>  "qt",
    }
}

