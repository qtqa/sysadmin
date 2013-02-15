class qt_prereqs::linux inherits qt_prereqs::unix {

    $gstreamer = $::operatingsystem ? {
        Ubuntu      =>  "libgstreamer0.10-dev",
        default     =>  "gstreamer-devel",
    }

    $git = $::operatingsystem ? {
        Ubuntu      =>  "git-core",
        default     =>  "git",
    }

    $sevenzip = $::operatingsystem ? {
        Ubuntu      =>  "p7zip-full",
        default     =>  "p7zip",
    }

    package {
        "$git":             ensure => installed;
        "$gstreamer":       ensure => installed;
        "chrpath":          ensure => installed;
        "lsb":              ensure => installed;
        "$sevenzip":        ensure => installed;
        "libasound2-dev":   ensure => installed;
        "libbluetooth-dev": ensure => installed;

        # for QtWidgets
        "libxext-dev":      ensure => installed;

        # for some input drivers
        "libudev-dev":      ensure => installed;

        # for testlib's valgrind QBENCHMARK backend
        "valgrind":         ensure => installed;

        # for QtPrintSupport
        "libcups2-dev":     ensure => installed;

        # for krazy:
        "libxml-writer-perl": ensure => installed;
        "libtie-ixhash-perl": ensure => installed;

        # for android:
        "openjdk-6-jdk": ensure => installed;
    }

    if $::operatingsystem == "Ubuntu" {

        $libxcb_icccm_dev = $::lsbmajdistrelease ? {
            10          =>  "libxcb-icccm1-dev",
            default     =>  "libxcb-icccm4-dev",
        }

        if $::lsbmajdistrelease >= 11 {
            package {
                # for accessibility
                "libatspi2.0-dev":                   ensure => installed;

                "libegl1-mesa-dev":                  ensure => installed;
                "libgles1-mesa-dev":                 ensure => installed;
                "libgles2-mesa-dev":                 ensure => installed;

                # for qtwayland
                "libwayland-dev":                    ensure => installed;
            }
        }

        package {
            "libgl1-mesa-dev":                   ensure => installed;
            "libxrender-dev":                    ensure => installed;
            "libxcomposite-dev":                 ensure => installed;
            "libffi-dev":                        ensure => installed;

            "libgstreamer-plugins-base0.10-dev": ensure => installed;
            "libdbus-1-dev":                     ensure => installed;
            "libssl-dev":                        ensure => installed;
            "libpulse-dev":                      ensure => installed;
            "pulseaudio":                        ensure => installed;

            # for QML visual tests
            "ttf-mscorefonts-installer":         ensure => installed;

            # for xcb qpa backend:
            "libx11-xcb-dev":                    ensure => installed;
            "libxcb-glx0-dev":                   ensure => installed;
            "$libxcb_icccm_dev":                 ensure => installed;
            "libxcb-image0-dev":                 ensure => installed;
            "libxcb-keysyms1-dev":               ensure => installed;
            "libxcb-shm0-dev":                   ensure => installed;
            "libxcb-sync0-dev":                  ensure => installed;
            "libxcb-xfixes0-dev":                ensure => installed;
            "libxcb-randr0-dev":                 ensure => installed;

            # enable phonon to play more media formats
            "gstreamer0.10-plugins-bad":         ensure => installed;

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

            # this is used by the QtSystem module
            "libgconf2-dev":                     ensure => installed;

            # this is used by the QtMultimediaKit module
            "libopenal-dev":                     ensure => installed;

            # needed by some client tool
            "libbz2-dev":                        ensure => installed;
            "libedit-dev":                       ensure => installed;

            # optional dependency for QLocale
            "libicu-dev":                        ensure => installed;
        }
    }

}


