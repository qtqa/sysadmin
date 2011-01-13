class integrator_www {
    case $operatingsystem {
        SuSE:   { include integrator_www::linux }
        CentOS: { include integrator_www::linux }
    }
}

