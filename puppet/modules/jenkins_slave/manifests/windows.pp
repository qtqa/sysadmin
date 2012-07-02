class jenkins_slave::windows inherits jenkins_slave::base {
    baselayout::startup { "jenkins-slave":
        path => "c:\\strawberry\\perl\\bin\\perl.exe",
        arguments => "c:\\Users\\$testuser\\jenkins\\jenkins-slave.pl",
        require => File["jenkins slave script"],
        user => $testuser,
    }
}

