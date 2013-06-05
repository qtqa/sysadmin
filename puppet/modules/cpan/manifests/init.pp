class cpan {
    require homedir_cpan
    case $::operatingsystem {
        CentOS:     { include cpan::centos }
        Ubuntu:     { include cpan::ubuntu }
        Windows:    { include cpan::windows }
        Darwin:     { include cpan::mac }
    }
}

