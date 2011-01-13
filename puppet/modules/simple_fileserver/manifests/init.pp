import '*'

class simple_fileserver {
    case $operatingsystem {
        CentOS:     { include simple_fileserver::centos }
    }
}

