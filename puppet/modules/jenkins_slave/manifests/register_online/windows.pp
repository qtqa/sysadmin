class jenkins_slave::register_online::windows {
    baselayout::startup { "jenkins-slave-register-online":
        path => "c:\\Windows\\System32\\cmd.exe",
        arguments => "/c c:\\strawberry\\perl\\bin\\perl.exe \"c:\\Users\\$jenkins_slave::user\\jenkins\\jenkins-cli.pl\" --retry -- online-node $::hostname > \"c:\\Users\\$jenkins_slave::user\\jenkins\\$jenkins_slave::cli_log\" 2>&1",
        require => File["jenkins cli script"],
        user => $jenkins_slave::user,
    }
}
