import "*"

class scratchbox {
    case $operatingsystem {
        Ubuntu:     { include scratchbox::debian }
    }
}

