import '*'

class puppet {

    case $operatingsystem {
        Darwin:     { include puppet::mac }
        Ubuntu:     { include puppet::ubuntu }
        CentOS:     { include puppet::centos }
        SuSE:       { include puppet::unix }
        Solaris:    { include puppet::solaris }
    }
}

