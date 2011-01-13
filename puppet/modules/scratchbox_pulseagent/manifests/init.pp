import "*"

class scratchbox_pulseagent {
    case $operatingsystem {
        Ubuntu:     { include scratchbox_pulseagent::linux }
    }
}

