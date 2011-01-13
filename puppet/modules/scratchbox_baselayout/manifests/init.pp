import "*"

class scratchbox_baselayout {
    case $operatingsystem {
        Ubuntu:     { include scratchbox_baselayout::linux }
    }
}

