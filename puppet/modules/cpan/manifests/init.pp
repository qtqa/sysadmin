import "*"

class cpan {
    case $operatingsystem {
        CentOS:     { include cpan::centos }
        Ubuntu:     { include cpan::ubuntu }
    }
}

