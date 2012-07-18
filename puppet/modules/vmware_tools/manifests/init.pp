class vmware_tools {
    case $operatingsystem {
        Ubuntu:     { include vmware_tools::linux }
        CentOS:     { include vmware_tools::linux }
    }
}

