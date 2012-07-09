class jenkins_slave ($server) {
    include java
    case $operatingsystem {
        Ubuntu:     { include jenkins_slave::linux }
        Linux:      { include jenkins_slave::linux }
        Darwin:     { include jenkins_slave::mac }
        windows:    { include jenkins_slave::windows }
    }
}
