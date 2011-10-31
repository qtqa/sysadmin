class ccache::linux
{
    package { "ccache":
        ensure      =>  present,
    }

    if $testuser {
        exec { "/usr/bin/sudo -u $testuser -H -i /usr/bin/ccache -M 4G":
            require     =>  Package["ccache"],
        }

        ccache_link {
                "gcc":     command => "gcc";
                "g++":     command => "g++";
                "cc":      command => "cc";
                "c++":     command => "c++";
        }
    }
}

