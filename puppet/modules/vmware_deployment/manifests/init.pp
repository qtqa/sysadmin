class vmware_deployment {
    case $::operatingsystem {
        Darwin:     { include vmware_deployment::mac }
    }
}

