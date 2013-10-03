class qt_prereqs::mac inherits qt_prereqs::unix {
    require macports

    # Ensure some packages via macports.
    Package { provider => 'macports' }

    # Only these older macs need git from macports;
    # newer macs get it from xcode
    if ( $macosx_productversion_major == "10.5" ) or ( $macosx_productversion_major == "10.6" ) {
        package {
            "git-core":          ensure => present;
        }
    }

    if ( $macosx_productversion_major == "10.7" ) or ( $macosx_productversion_major == "10.8" ) {
        package {
            "mysql55":           ensure => present;
        }
         file { "/etc/profile.d/mysql_env.sh":
             ensure   =>  present,
             content  => template("qt_prereqs/mysql_env.sh.erb"),
            }
    }

    package {
        "perl5":             ensure => present;
        "p5-libwww-perl":    ensure => present;
    }

    # 10.6: Re-enable LCD font smoothing for some monitors
    # http://hints.macworld.com/article.php?story=20090828224632809
    exec { "font smoothing option":
        name => "/bin/sh -c \"defaults -currentHost write -globalDomain AppleFontSmoothing -int 2\""
    }
}

