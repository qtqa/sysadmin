class baselayout::ubuntu inherits baselayout::linux {
    file {
        "/etc/profile.d/99homepath.sh":
            owner   =>  "root",
            mode    =>  0444,
            source  =>  "puppet:///modules/baselayout/ubuntu/profile.d/99homepath.sh",
        ;
    }

    file {
        "/etc/default/maybe_wait_for_real_hostname":
            owner   =>  "root",
            mode    =>  0444,
            source  =>  "puppet:///modules/baselayout/ubuntu/maybe_wait_for_real_hostname",
        ;
        "/etc/default/locale":
            owner   =>  "root",
            mode    =>  0444,
            source  =>  "puppet:///modules/baselayout/ubuntu/locale",
        ;
        "/etc/sysctl.conf":
            owner   =>  "root",
            mode    =>  0444,
            source  =>  "puppet:///modules/baselayout/ubuntu/sysctl.conf",
            notify  =>  Exec["sysctl update"],
        ;
    }

    exec { "sysctl update":
        command     => "/sbin/sysctl -p",
        refreshonly => true,
    }

    file {
        "/etc/X11/default-display-manager":
            owner   =>  "root",
            mode    =>  0444,
            content =>  "/usr/bin/kdm",
        ;
    }

    # Hostname will only be set via DHCP if /etc/hostname is absent.
    # Gleaned from https://bugs.launchpad.net/ubuntu/+source/dhcp3/+bug/90388
    # Therefore, we remove /etc/hostname iff resolving our fqdn does not
    # result in the correct IP address - we should then get the correct fqdn on
    # next boot.
    # Note: we used to remove this file at every boot, but that seems to make the
    # boot sequence a bit racy, as some services don't like it when the hostname
    # changes after they've started.
    exec { "/bin/rm -f /etc/hostname":
        onlyif => "/bin/sh -c \"[ x$(host $fqdn | sed -r -n -e 's/^.*has address //p') != x$ipaddress ]\""
    }

    if $testuser {
        file {
            "/etc/kde4/kdm/kdmrc":
                owner   =>  "root",
                mode    =>  0444,
                content =>  template("baselayout/kdmrc.ubuntu.erb")
            ;
        }
    }
}
