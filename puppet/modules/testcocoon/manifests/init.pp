class testcocoon {
    case $::operatingsystem {
        Ubuntu:     { include testcocoon::ubuntu }
    }
}

