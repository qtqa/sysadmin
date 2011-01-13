class puppet::centos inherits puppet::unix {
    service { "puppet":
        enable      =>  false,
    }
}
