class jenkins_slave::register_online::windows {
    baselayout::startup { "jenkins-slave-register-online":
        path => "c:\\Windows\\System32\\cmd.exe",
        arguments => "/c c:\\strawberry\\perl\\bin\\perl.exe \"c:\\Users\\$testuser\\jenkins\\jenkins-cli.pl\" -- online-node $::hostname > \"c:\\Users\\$testuser\\jenkins\\$jenkins_slave::cli_log\" 2>&1",
        require => File["jenkins cli script"],
        user => $testuser,
    }
}
