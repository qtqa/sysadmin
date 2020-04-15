# vim: set expandtab ts=4:

class mac {
    #*
    # Enforce that the two separate concepts of "hostname" on mac have the same value
    #*
    if $::hostname != $sp_local_host_name {
        exec { "fix_hostname":
            command => "/usr/sbin/scutil --set HostName $sp_local_host_name",
        }
    }
}

case $::operatingsystem {
    Darwin:     { include mac }
}

