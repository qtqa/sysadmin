class testusers ($user = $baselayout::testuser) {
    case $::operatingsystem {
        Ubuntu:     { include testusers::ubuntu }
    }
}

