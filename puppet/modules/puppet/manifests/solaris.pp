class puppet::solaris inherits puppet::unix {
    service { "puppetd":
        ensure      =>  stopped,
        enable      =>  false,
    }
}
