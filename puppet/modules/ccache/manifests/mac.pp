class ccache::mac
{
    include macports

    package { "ccache":
        provider    =>  $macports_provider,
        ensure      =>  present,
    }

    if $ccache::user {
        exec { "/usr/bin/sudo -u $ccache::user -H -i /opt/local/bin/ccache -M 4G":
            require     =>  Package["ccache"],
        }

        ccache::link {
                "gcc":     command => "gcc";
                "gcc-4.0": command => "gcc-4.0";
                "gcc-4.2": command => "gcc-4.2";
                "g++":     command => "g++";
                "g++-4.0": command => "g++-4.0";
                "g++-4.2": command => "g++-4.2";
                "cc":      command => "cc";
                "cc-4.0":  command => "cc-4.0";
                "cc-4.2":  command => "cc-4.2";
                "c++":     command => "c++";
                "c++-4.0": command => "c++-4.0";
                "c++-4.2": command => "c++-4.2";
                "clang":   command => "clang";
                "clang++": command => "clang++";
        }
    }
}

