class jenkins_slave::register_online::mac {
    baselayout::startup { "jenkins-slave-register-online":
        path    =>  "/Users/$jenkins_slave::user/jenkins/jenkins-cli.pl",
        arguments => "-- online-node $::hostname | tee /Users/$jenkins_slave::user/jenkins/$jenkins_slave::cli_log | logger -t jenkins",
        require =>  File["jenkins cli script"],
        user    =>  $jenkins_slave::user,
    }
}
