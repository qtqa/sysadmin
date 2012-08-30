class jenkins_slave::base {
    $jenkins_slave_name = $jenkins_slave::slave_name
    $jenkins_server = $jenkins_slave::server
    $jenkins_workdir = $::operatingsystem ? {
        windows  =>  "c:\\work",
        default  =>  "/work",
    }

    $user = $jenkins_slave::user
    $group = $jenkins_slave::group

    $homedir = $::operatingsystem ? {
        windows  =>  "c:\\Users\\$user",
        Darwin   =>  "/Users/$user",
        default  =>  "/home/$user",
    }
    $jenkins_slave_dir = "$homedir/jenkins"

    if $::operatingsystem != "windows" {
        File {
            owner    =>  $user,
            group    =>  $group,
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
        owner    =>  $user,
        group    =>  $group,
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
        owner    =>  $user,
        group    =>  $group,
        content  =>  template("jenkins_slave/jenkins-cli.pl.erb"),
        mode     =>  0755,
        require  =>  File["jenkins workspace"],
    }
}
