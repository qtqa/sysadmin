class jenkins_slave::base {
    $jenkins_slave_name = $hostname
    $jenkins_server = $jenkins_slave::server
    $jenkins_workdir = $operatingsystem ? {
        windows  =>  "c:\\work",
        default  =>  "/work",
    }

    $homedir = $operatingsystem ? {
        windows  =>  "c:\\Users\\$testuser",
        Darwin   =>  "/Users/$testuser",
        default  =>  "/home/$testuser",
    }
    $jenkins_slave_dir = "$homedir/jenkins"

    file { "jenkins slave directory":
        name     =>  $jenkins_slave_dir,
        ensure   =>  directory,
        owner    =>  "$testuser",
        group    =>  "$testgroup",
    }

    file { "jenkins slave script":
        name     =>  "$jenkins_slave_dir/jenkins-slave.pl",
        ensure   =>  present,
        owner    =>  "$testuser",
        group    =>  "$testgroup",
        content  =>  template("jenkins_slave/jenkins-slave.pl.erb"),
        mode     =>  0755,
        require  =>  File["jenkins workspace"],
    }

    file { "jenkins workspace":
        name     =>  $jenkins_workdir,
        ensure   =>  directory,
        owner    =>  "$testuser",
        group    =>  "$testgroup",
    }
}

