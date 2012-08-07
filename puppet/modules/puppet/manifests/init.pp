class puppet {
    case $::operatingsystem {
        Darwin:     { include puppet::mac }
        Ubuntu:     { include puppet::debian }
        CentOS:     { include puppet::centos }
        SuSE:       { include puppet::unix }
        OpenSuSE:   { include puppet::unix }
        Debian:     { include puppet::debian }
        Solaris:    { include puppet::solaris }
        Windows:    { include puppet::windows }
    }
}

