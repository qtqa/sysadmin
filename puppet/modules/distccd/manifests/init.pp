class distccd {
    case $operatingsystem {
        Darwin:     { include distccd::mac }
    }
}

