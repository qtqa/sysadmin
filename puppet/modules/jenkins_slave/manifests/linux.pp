class jenkins_slave::linux inherits jenkins_slave::base {
    baselayout::startup { "jenkins-slave":
        path    =>  "/home/$testuser/jenkins/jenkins-slave.pl",
        require =>  File["/home/$testuser/jenkins/jenkins-slave.pl"],
        user    =>  $testuser,
    }
}

