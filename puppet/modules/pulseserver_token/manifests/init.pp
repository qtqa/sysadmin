class pulseserver_token {
    case $::operatingsystem {
        CentOS:     { include pulseserver_token::unix }
    }
}

