class qnx {
    case $::operatingsystem {
        Ubuntu:     { include qnx::linux }
        Windows:    { include qnx::windows }
    }
}

