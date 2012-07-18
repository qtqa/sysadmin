class crosscompilers {
    case $operatingsystem {
        Ubuntu:     { include crosscompilers::linux }
    }
}

