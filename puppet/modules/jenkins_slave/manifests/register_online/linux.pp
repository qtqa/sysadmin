class jenkins_slave::register_online::linux {
    baselayout::startup { "jenkins-slave-register-online":
        path    =>  "/bin/sh",
        arguments => [
            "-c",
            "/home/$jenkins_slave::user/jenkins/jenkins-cli.pl -- online-node $jenkins_slave::slave_name 2>&1 | tee /home/$jenkins_slave::user/jenkins/$jenkins_slave::cli_log | logger -t jenkins"
        ],
        require =>  File["jenkins cli script"],
        user    =>  $jenkins_slave::user,
    }
}
