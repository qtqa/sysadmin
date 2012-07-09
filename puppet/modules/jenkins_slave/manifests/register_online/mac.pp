class jenkins_slave::register_online::mac {
    baselayout::startup { "jenkins-slave-register-online":
        path    =>  "/Users/$testuser/jenkins/jenkins-cli.pl",
        arguments => "-- online-node $::hostname | tee /Users/$testuser/jenkins/$jenkins_slave::cli_log | logger -t jenkins",
        require =>  File["jenkins cli script"],
        user    =>  $testuser,
    }
}
