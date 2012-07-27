class ccache {
    case $::operatingsystem {
        Darwin:     { include ccache::mac }
        Solaris:    { include ccache::solaris }
        Ubuntu:     { include ccache::linux }
        Linux:      { include ccache::linux }
    }
}

define ccache_link($command) {
    case $::operatingsystem {
        Darwin:  {
            file {
                "/Users/$testuser/bin/$command":
                    ensure  =>  "/opt/local/bin/ccache",
                    require =>  File["/Users/$testuser/bin"],
            }
        }
        Solaris: {
            file {
                "/export/home/$testuser/bin/$command":
                    ensure  =>  "/opt/csw/bin/ccache",
                    require =>  File["/export/home/$testuser/bin"],
            }
        }
        default: {
            file {
                "/home/$testuser/bin/$command":
                    ensure  =>  "/usr/bin/ccache",
                    require =>  File["/home/$testuser/bin"],
            }
        }
    }
}
