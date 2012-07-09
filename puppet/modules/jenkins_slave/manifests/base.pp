class jenkins_slave::base {
    $jenkins_slave_name = $::hostname
    $jenkins_server = $jenkins_slave::server
    $jenkins_workdir = $::operatingsystem ? {
        windows  =>  "c:\\work",
        default  =>  "/work",
    }

    $homedir = $::operatingsystem ? {
        windows  =>  "c:\\Users\\$testuser",
        Darwin   =>  "/Users/$testuser",
        default  =>  "/home/$testuser",
    }
    $jenkins_slave_dir = "$homedir/jenkins"

    if $::operatingsystem != "windows" {
        File {
            owner    =>  "$testuser",
            group    =>  "$testgroup",
            mode     =>  0755,
        }
    }

    file { "jenkins slave directory":
        name     =>  $jenkins_slave_dir,
        ensure   =>  directory,
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
    }

    file { "jenkins cli script":
        name     =>  "$jenkins_slave_dir/jenkins-cli.pl",
        ensure   =>  present,
        owner    =>  "$testuser",
        group    =>  "$testgroup",
        content  =>  template("jenkins_slave/jenkins-cli.pl.erb"),
        mode     =>  0755,
        require  =>  File["jenkins workspace"],
    }
}
