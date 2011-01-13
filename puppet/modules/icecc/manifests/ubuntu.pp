class icecc::ubuntu inherits icecc::linux {
    file { "/etc/default/icecc":
        ensure  =>  present,
        owner   =>  "root",
        group   =>  "users",
        mode    =>  0444,
        source  =>  "puppet:///modules/icecc/ubuntu/etc/default/icecc",
    }
}

