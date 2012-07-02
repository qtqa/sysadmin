class jenkins_slave ($server) {
    include java
    case $operatingsystem {
        Ubuntu:     { include jenkins_slave::linux }
        Linux:      { include jenkins_slave::linux }
        windows:    { include jenkins_slave::windows }
    }
}
