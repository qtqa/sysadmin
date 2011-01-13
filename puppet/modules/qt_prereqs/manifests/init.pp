import "*"

include hosts

class qt_prereqs {
    case $operatingsystem {
        Darwin:     { include qt_prereqs::mac }
        Ubuntu:     { include qt_prereqs::linux }
        Linux:      { include qt_prereqs::linux }
        Solaris:    { include qt_prereqs::solaris }
    }
}

