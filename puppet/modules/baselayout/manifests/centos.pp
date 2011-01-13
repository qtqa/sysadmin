class baselayout::centos inherits baselayout::linux {
    file {
        "/etc/profile.d/99homepath.sh":
            owner   =>  "root",
            mode    =>  0444,
            source  =>  "puppet:///modules/baselayout/centos/profile.d/99homepath.sh",
        ;
    }
}
