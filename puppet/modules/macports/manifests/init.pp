class macports {
   
    #*
    # Enforce macports configuration
    #*
    file { "macports.conf":
        name    =>  "/opt/local/etc/macports/macports.conf",
        source  =>  "puppet:///modules/macports/macports.conf",
    }
    file { "sources.conf":
        name    =>  "/opt/local/etc/macports/sources.conf",
        source  =>  "puppet:///modules/macports/sources.conf",
    }
    
    #*
    # Put macports apps into PATH by default
    #*
    file { "/etc/paths.d/macports":
        ensure  =>  present,
        content =>  "/opt/local/bin",
    }
}

