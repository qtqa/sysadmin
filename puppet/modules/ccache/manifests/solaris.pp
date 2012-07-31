class ccache::solaris
{
    if $zone == false {
        csw_package { "ccache":
            ensure      =>  present,
        }
    }

    if $ccache::user {
        exec { "/bin/sh -c 'echo ''/opt/csw/bin/ccache -M 2G'' | /usr/bin/su - $ccache::user'":
        }

        ccache::link {
                "gcc":     command => "gcc";
                "g++":     command => "g++";
                "cc":      command => "cc";
                "cc-5.0":  command => "cc-5.0";
                "CC":      command => "CC";
        }
    }
}

