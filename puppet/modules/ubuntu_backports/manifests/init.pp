class ubuntu_backports {
    file {
        "/etc/apt/sources.list.d/$::lsbdistcodename-backports.list":
            content => template("ubuntu_backports/backports.list.erb"),
            require => File["/etc/apt/preferences.d/$::lsbdistcodename-backports.conf"],
            notify => Exec["apt-get update for ubuntu_backports"];
        "/etc/apt/preferences.d/$::lsbdistcodename-backports.conf":
            content => template("ubuntu_backports/backports.conf.erb");
    }

    exec {
        "apt-get update for ubuntu_backports":
            command => "/usr/bin/apt-get update",
            refreshonly => true;
    }
}
