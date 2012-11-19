# Utility scripts to ease deployment of OSX machines from VMWare template
class vmware_deployment::mac {
    File {
        owner   =>  "root",
        group   =>  "admin",
        mode    =>  0755,
    }

    file {
        "/opt/devtools":
            ensure  =>  "directory",
        ;
        "/opt/devtools/runonce.sh":
            source  =>  "puppet:///modules/vmware_deployment/runonce.sh",
            require =>  File["/opt/devtools"],
        ;
        "/opt/devtools/change_hostname_mac.sh":
            source  =>  "puppet:///modules/vmware_deployment/change_hostname_mac.sh",
            require =>  File["/opt/devtools"],
        ;
        "/opt/devtools/update_node_from_server.sh":
            ensure => present,
            content => template( "vmware_deployment/update_node_from_server.sh.erb" ),
            require =>  File["/opt/devtools"],
        ;
    }
}
