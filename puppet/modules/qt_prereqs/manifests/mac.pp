class qt_prereqs::mac inherits qt_prereqs::unix {
    include macports

    # Ensure some packages via macports.
    Package { provider => 'macports' }

    # Only these older macs need git from macports;
    # newer macs get it from xcode
    if ( $macosx_productversion_major == "10.5" ) or ( $macosx_productversion_major == "10.6" ) {
        package {
            "git-core":     ensure => present;
        }
    }

    # 10.6: Re-enable LCD font smoothing for some monitors
    # http://hints.macworld.com/article.php?story=20090828224632809
    exec { "font smoothing option":
        name => "/bin/sh -c \"defaults -currentHost write -globalDomain AppleFontSmoothing -int 2\""
    }
}

