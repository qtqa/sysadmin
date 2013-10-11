class jenkins_slave::opensuse inherits jenkins_slave::linux {
    package { "perl-libwww-perl": ensure => installed; }
    $user = $jenkins_slave::user
    baselayout::startup { "jenkins-slave":
        path    =>  "/bin/sh",
        arguments => [
            "-c",
            "exec /home/$user/jenkins/jenkins-slave.pl 2>&1 | tee /home/$user/jenkins/log.txt | logger -t jenkins"
        ],
        require =>  File["/home/$user/jenkins/jenkins-slave.pl"],
        user    =>  $user,
    }
}

