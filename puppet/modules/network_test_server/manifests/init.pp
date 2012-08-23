class network_test_server {
    include puppet
    case $::operatingsystem {
        # Add others if you need them
        Ubuntu:     { include network_test_server::ubuntu }
    }
}

