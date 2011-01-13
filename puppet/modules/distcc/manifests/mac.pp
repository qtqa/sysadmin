class distcc::mac
{
    File {
        owner   =>  "root",
        group   =>  "wheel",
        mode    =>  0444,
        require =>  File["/etc/profile.d"],
    }
    file {
        "/etc/profile.d/10distcc.sh":
            source  =>  "puppet:///modules/distcc/mac/10distcc.sh",
        ;
        "/etc/profile.d/20distcc_hosts.sh":
            content =>  template("distcc/mac/20distcc_hosts.sh.erb"),
        ;
    }
}

