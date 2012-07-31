class jenkins_slave::mac inherits jenkins_slave::base {
    $user = $jenkins_slave::user
    baselayout::startup { "jenkins-slave":
        path    =>  "/Users/$user/jenkins/jenkins-slave.pl",
        require =>  File["/Users/$user/jenkins/jenkins-slave.pl"],
        user    =>  $user,
    }
}
