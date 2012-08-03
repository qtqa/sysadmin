class apt_backports(
    $base_url = $::operatingsystem ? {
        Ubuntu => "http://archive.ubuntu.com/ubuntu",
        Debian => "http://backports.debian.org/debian-backports",
    },
    $sections = $::operatingsystem ? {
        Ubuntu => 'main restricted universe multiverse',
        Debian => 'main contrib non-free',
    }
) {
    file {
        "/etc/apt/sources.list.d/$::lsbdistcodename-backports.list":
            content => template("apt_backports/backports.list.erb"),
            require => File["/etc/apt/preferences.d/$::lsbdistcodename-backports.pref"],
            notify => Exec["apt-get update for apt_backports"];
        "/etc/apt/preferences.d/$::lsbdistcodename-backports.pref":
            content => template("apt_backports/backports.pref.erb");
    }

    exec {
        "apt-get update for apt_backports":
            command => "/usr/bin/apt-get update",
            refreshonly => true;
    }
}
