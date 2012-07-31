class ccache::linux
{
    package { "ccache":
        ensure      =>  present,
    }

    if $ccache::user {
        exec { "/usr/bin/sudo -u $ccache::user -H -i /usr/bin/ccache -M 4G":
            require     =>  Package["ccache"],
        }

        ccache::link {
                "gcc":     command => "gcc";
                "g++":     command => "g++";
                "cc":      command => "cc";
                "c++":     command => "c++";
        }
    }
}

