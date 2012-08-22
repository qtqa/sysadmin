class distcc(
    $hosts = ['localhost']
) {
    case $::operatingsystem {
        Darwin:     { include distcc::mac }
    }
}

