# For fetching of files too sensitive for any source control
include secret_file

class baselayout {
    case $::operatingsystem {
        Darwin:     { include baselayout::mac }
        Ubuntu:     { include baselayout::ubuntu }
        CentOS:     { include baselayout::centos }
        Linux:      { include baselayout::linux }
        Solaris:    { include baselayout::solaris }
        windows:    { include baselayout::windows }
    }
}

