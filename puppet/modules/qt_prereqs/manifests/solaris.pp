class qt_prereqs::solaris inherits qt_prereqs::unix {

    if $zone == false {
        csw_package {
            "vim":          ensure => installed;
            "gmake":        ensure => installed;
        }
    }
}


