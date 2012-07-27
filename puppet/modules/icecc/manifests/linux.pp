class icecc::linux
{
    $icecc_package = $::operatingsystem ? {
        default     =>  "icecc",
    }

    $icecc_service = $::operatingsystem ? {
        default     =>  "icecc",
    }

    $icecc_binary = $::operatingsystem ? {
        default     =>  "/usr/bin/icecc",
    }

    package { $icecc_package:
        ensure      =>  present,
    }

    service { $icecc_service:
        ensure      =>  running,
        enable      =>  true,
    }

    file { "/etc/icecc/icecc.conf":
        ensure      =>  present,
        content     =>  template("icecc/icecc.conf.erb"),
        require     =>  Package[$icecc_package],
    }

    # Create symlinks to icecc in a specific directory.
    # This allows adding this directory to PATH to use icecc,
    # or setting CCACHE_PATH to use icecc+ccache
    file {
        "/opt/icecream":
            ensure  =>  directory,
            owner   =>  root,
            mode    =>  0755,
        ;
        "/opt/icecream/bin":
            ensure  =>  directory,
            owner   =>  root,
            mode    =>  0755,
            require =>  File["/opt/icecream"],
        ;
        "/opt/icecream/bin/g++": ensure  =>  $icecc_binary;
        "/opt/icecream/bin/gcc": ensure  =>  $icecc_binary;
        "/opt/icecream/bin/cc":  ensure  =>  $icecc_binary;
        "/opt/icecream/bin/c++": ensure  =>  $icecc_binary;

        "/etc/profile.d/icecc_with_ccache.sh":
            ensure  =>  present,
            content =>  "CCACHE_PATH=/opt/icecream/bin\nexport CCACHE_PATH",
        ;
        # Ensure teambuilder is not present - we can't mix them
        "/etc/profile.d/teambuilder.sh":
            ensure  =>  absent,
        ;
    }
}

