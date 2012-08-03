class jenkins_slave::linux inherits jenkins_slave::base {
    $user = $jenkins_slave::user
    baselayout::startup { "jenkins-slave":
        path    =>  "/home/$user/jenkins/jenkins-slave.pl",
        arguments => " 2>&1 | tee /home/$user/jenkins/log.txt | logger -t jenkins",
        require =>  File["/home/$user/jenkins/jenkins-slave.pl"],
        user    =>  $user,
    }
}

