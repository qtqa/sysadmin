class jenkins_slave::windows inherits jenkins_slave::base {
    $user = $jenkins_slave::user
    baselayout::startup { "jenkins-slave":
        path => "c:\\Windows\\System32\\cmd.exe",
        arguments => "/c c:\\utils\\strawberryperl_portable\\perl\\bin\\perl.exe \"c:\\Users\\$user\\jenkins\\jenkins-slave.pl\" > \"c:\\Users\\$user\\jenkins\\log.txt\" 2>&1",
        require => File["jenkins slave script"],
        user => $user,
    }
}

