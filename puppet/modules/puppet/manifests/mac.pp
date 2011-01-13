class puppet::mac inherits puppet::unix {
    service { "com.reductivelabs.puppet":
        ensure      =>  stopped,
        enable      =>  false,
    }
}

