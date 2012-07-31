class jenkins_slave::windows inherits jenkins_slave::base {
    $user = $jenkins_slave::user
    baselayout::startup { "jenkins-slave":
        path => "c:\\strawberry\\perl\\bin\\perl.exe",
        arguments => "c:\\Users\\$user\\jenkins\\jenkins-slave.pl",
        require => File["jenkins slave script"],
        user => $user,
    }
}

