class jenkins_slave::register_online::linux {
    baselayout::startup { "jenkins-slave-register-online":
        path    =>  "/home/$testuser/jenkins/jenkins-cli.pl",
        arguments => "-- online-node $::hostname | tee /home/$testuser/jenkins/$jenkins_slave::cli_log | logger -t jenkins",
        require =>  File["jenkins cli script"],
        user    =>  $testuser,
    }
}
