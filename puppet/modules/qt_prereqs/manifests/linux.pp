class qt_prereqs::linux inherits qt_prereqs::unix {

    $gstreamer = $operatingsystem ? {
        Ubuntu      =>  "libgstreamer0.10-dev",
        default     =>  "gstreamer-devel",
    }

    $git = $operatingsystem ? {
        Ubuntu      =>  "git-core",
        default     =>  "git",
    }

    $sevenzip = $operatingsystem ? {
        Ubuntu      =>  "p7zip-full",
        default     =>  "p7zip",
    }

    $lsb = $operatingsystem ? {
        MeeGo       =>  "meego-lsb",
        default     =>  "lsb",
    }

    package {
        "$git":             ensure => installed;
        "$gstreamer":       ensure => installed;
        "chrpath":          ensure => installed;
        "$lsb":             ensure => installed;

        # for krazy:
        "libxml-writer-perl": ensure => installed;
        "libtie-ixhash-perl": ensure => installed;
    }

    if $operatingsystem == "Ubuntu" {
        package {
            "libgstreamer-plugins-base0.10-dev": ensure => installed;
            "libdbus-1-dev":                     ensure => installed;
            "libssl-dev":                        ensure => installed;
            "libpulse-dev":                      ensure => installed;
            "pulseaudio":                        ensure => installed;

            # for QML visual tests
            "ttf-mscorefonts-installer":         ensure => installed;

            # for webkit
            "flex":                              ensure => installed;
            "bison":                             ensure => installed;
            "gperf":                             ensure => installed;
        }
    }

    # These packages should not be installed inside the meego SDK
    if $operatingsystem != "MeeGo" {
        package {
            "$sevenzip":        ensure => installed;
            "libasound2-dev":   ensure => installed;
            "libbluetooth-dev": ensure => installed;
        }    
    }

}


