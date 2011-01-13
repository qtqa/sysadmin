include pulseserver_token
include vmware_esx_hosts_hack

class pulseserver {
    case $operatingsystem {
        CentOS:     { include pulseserver::linux }
    }
}

