class jenkins_slave::linux inherits jenkins_slave::base {
    $user = $jenkins_slave::user
    baselayout::startup { "jenkins-slave":
        path    =>  "/home/$user/jenkins/jenkins-slave.pl",
        require =>  File["/home/$user/jenkins/jenkins-slave.pl"],
        user    =>  $user,
    }
}

