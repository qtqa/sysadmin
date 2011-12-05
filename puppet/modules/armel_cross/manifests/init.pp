import "*"

class armel_cross {
    case $operatingsystem {
        Ubuntu:     { include armel_cross::ubuntu }
    }
}

