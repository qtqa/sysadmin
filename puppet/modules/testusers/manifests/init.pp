class testusers {
    case $::operatingsystem {
        Ubuntu:     { include testusers::ubuntu }
    }
}

