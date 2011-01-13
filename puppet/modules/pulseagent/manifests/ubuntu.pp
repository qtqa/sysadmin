class pulseagent::ubuntu inherits pulseagent::linux {
    package { "default-jre": ensure => installed; }
}

