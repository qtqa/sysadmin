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

            # make sure we have at least some simple way to download
            # files from command line
            "curl":                              ensure => installed;

            # these are used by some CPAN modules we want to install
            # for test scripts
            "libmysqlclient-dev":                ensure => installed;
            "libgd2-xpm-dev":                    ensure => installed;

            # this is used by the CI system python classes, and needed
            # to run the CI selftests correctly
            "sqlite3":                           ensure => installed;
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


