class qnx650 {
    case $::operatingsystem {
        Ubuntu:     { include qnx650::linux }
        Windows:    { include qnx650::windows }
    }
}

