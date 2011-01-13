import "*"

class pulseagent {
    case $operatingsystem {
        Darwin:     { include pulseagent::mac }
        Ubuntu:     { include pulseagent::ubuntu }
        Linux:      { include pulseagent::linux }
        Solaris:    { include pulseagent::solaris }
    }
}

