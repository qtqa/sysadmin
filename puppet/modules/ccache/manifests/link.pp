define ccache::link($command) {
    case $::operatingsystem {
        Darwin:  {
            file {
                "/Users/$ccache::user/bin/$command":
                    ensure  =>  "/opt/local/bin/ccache",
                    require =>  File["/Users/$ccache::user/bin"],
            }
        }
        Solaris: {
            file {
                "/export/home/$ccache::user/bin/$command":
                    ensure  =>  "/opt/csw/bin/ccache",
                    require =>  File["/export/home/$ccache::user/bin"],
            }
        }
        default: {
            file {
                "/home/$ccache::user/bin/$command":
                    ensure  =>  "/usr/bin/ccache",
                    require =>  File["/home/$ccache::user/bin"],
            }
        }
    }
}
