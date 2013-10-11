class jenkins_slave::linux inherits jenkins_slave::base {
    file { "/etc/sudoers.d/${user}-nopasswd-reboot":
        owner    =>  "root",
        group    =>  "root",
        mode     =>  0440,
        content  =>  template("jenkins_slave/testuser-nopasswd-reboot.erb"),
        require  =>  Exec["Ensure sudoers.d is enabled"]
    }
}

