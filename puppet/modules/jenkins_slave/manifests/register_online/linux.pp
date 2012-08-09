class jenkins_slave::register_online::linux {
    baselayout::startup { "jenkins-slave-register-online":
        path    =>  "/bin/sh",
        arguments => [
            "-c",
            "/home/$jenkins_slave::user/jenkins/jenkins-cli.pl -- online-node $::hostname 2>&1 | tee /home/$jenkins_slave::user/jenkins/$jenkins_slave::cli_log | logger -t jenkins"
        ],
        require =>  File["jenkins cli script"],
        user    =>  $jenkins_slave::user,
    }
}
