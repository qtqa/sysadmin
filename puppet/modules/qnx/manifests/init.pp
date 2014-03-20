class qnx {
    case $::operatingsystem {
        Ubuntu:     { include qnx::linux }
    }
}

