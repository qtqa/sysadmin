class smallfs {
    case $::operatingsystem {
        Ubuntu:     { include smallfs::linux }
        Linux:      { include smallfs::linux }
    }
}

