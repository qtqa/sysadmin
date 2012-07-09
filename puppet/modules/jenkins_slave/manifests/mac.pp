class jenkins_slave::mac inherits jenkins_slave::base {
    baselayout::startup { "jenkins-slave":
        path    =>  "/Users/$testuser/jenkins/jenkins-slave.pl",
        require =>  File["/Users/$testuser/jenkins/jenkins-slave.pl"],
        user    =>  $testuser,
    }
}
