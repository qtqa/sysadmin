class ccache::linux
{
    package { "ccache":
        ensure      =>  present,
    }

    if $testuser {
        exec { "/usr/bin/sudo -u $testuser -i /usr/bin/ccache -M 4G":
            require     =>  Package["ccache"],
        }

        define ccache_link($command) {
            file {
                "/home/$testuser/bin/$command":
                    ensure  =>  "/usr/bin/ccache",
                    require =>  File["/home/$testuser/bin"],
            }
        }

        ccache_link {
                "gcc":     command => "gcc";
                "g++":     command => "g++";
                "cc":      command => "cc";
                "c++":     command => "c++";
        }
    }
}

