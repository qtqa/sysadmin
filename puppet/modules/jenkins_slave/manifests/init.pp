class jenkins_slave ($user = $baselayout::testuser, $group = $baselayout::group, $server, $set_online = true) {
    include java
    case $::operatingsystem {
        Ubuntu:     { include jenkins_slave::linux }
        Linux:      { include jenkins_slave::linux }
        Darwin:     { include jenkins_slave::mac }
        windows:    { include jenkins_slave::windows }
    }
    if $set_online == true {
        $cli_log = "jenkins_cli_log.txt"
        case $::operatingsystem {
            Ubuntu:     { include jenkins_slave::register_online::linux }
            Linux:      { include jenkins_slave::register_online::linux }
            Darwin:     { include jenkins_slave::register_online::mac }
            windows:    { include jenkins_slave::register_online::windows }
        }
    }
    if $::operatingsystem == 'Ubuntu' or $::operatingsystem == 'Darwin' {
        file { "/etc/sudoers.d/$user-nopasswd-reboot":
            owner    =>  "root",
            mode     =>  0440,
            content  =>  template("jenkins_slave/testuser-nopasswd-reboot.erb"),
            require  =>  Exec["Ensure sudoers.d is enabled"]
        }
    }
}
