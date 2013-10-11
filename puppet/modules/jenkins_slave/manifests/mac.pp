class jenkins_slave::mac inherits jenkins_slave::base {
    $user = $jenkins_slave::user
    baselayout::startup { "jenkins-slave":
        path    =>  "/bin/sh",
        arguments => [
            "-c",
            "/Users/$user/jenkins/jenkins-slave.pl 2>&1 | tee /Users/$user/jenkins/log.txt | logger -t jenkins"
        ],
        require =>  File["/Users/$user/jenkins/jenkins-slave.pl"],
        user    =>  $user,
    }
    file { "/etc/sudoers.d/${user}-nopasswd-reboot":
        owner    =>  "root",
        group    =>  "wheel",
        mode     =>  0440,
        content  =>  template("jenkins_slave/testuser-nopasswd-reboot.erb"),
        require  =>  Exec["Ensure sudoers.d is enabled"]
    }
}
