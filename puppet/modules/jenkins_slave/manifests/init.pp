class jenkins_slave ($server, $set_online = true) {
    include java
    case $::operatingsystem {
        Ubuntu:     { include jenkins_slave::linux }
        Linux:      { include jenkins_slave::linux }
        Darwin:     { include jenkins_slave::mac }
        windows:    { include jenkins_slave::windows }
    }
    if $set_online == true {
        $cli_log = "jenkins_cli_log.txt"
        case $::operatingsystem {
            Ubuntu:     { include jenkins_slave::register_online::linux }
            Linux:      { include jenkins_slave::register_online::linux }
            Darwin:     { include jenkins_slave::register_online::mac }
            windows:    { include jenkins_slave::register_online::windows }
        }
    }
}
