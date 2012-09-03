class qt_prereqs(
    $network_test_server_ip = undef
) {
    # cmake is a qt5 prereq
    include cmake

    # IP address of the qt-test-server used by QtNetwork and other autotests
    if $network_test_server_ip {
        host { "qt-test-server":
            ip => $network_test_server_ip,
            name => "qt-test-server",
            host_aliases => [ "qt-test-server.qt-test-net" ],
        }
    }

    case $::operatingsystem {
        Darwin:     { include qt_prereqs::mac }
        Ubuntu:     { include qt_prereqs::linux }
        Linux:      { include qt_prereqs::linux }
        Solaris:    { include qt_prereqs::solaris }
    }
}

