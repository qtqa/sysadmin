class hosts {
    # hosts entries deployed on all test machines

    # Set up the hostname for the network test server.
    # If you want to run Qt network tests, you must set up a network test server
    # (using the network_test_server module in this repo), then roll out a hosts
    # entry like the below, with the IP address of your test server.
    #host { "qt-test-server":
    #    ip => "127.0.0.1",          
    #    name => "qt-test-server",
    #    host_aliases => [ "qt-test-server.qt-test-net" ],
    #}
}

