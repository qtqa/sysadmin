class distcc {
    case $::operatingsystem {
        Darwin:     { include distcc::mac }
    }
}

