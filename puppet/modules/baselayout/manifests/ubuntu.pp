class baselayout::ubuntu inherits baselayout::linux {

    #========================== Ubuntu 10.04 stuff ============================
    # This logic only applies to Ubuntu 10.04, where we use KDE.
    #
    if $lsbmajdistrelease < 11 {
        file {
            "/etc/X11/default-display-manager":
                owner   =>  "root",
                mode    =>  0444,
                content =>  "/usr/bin/kdm",
            ;
        }

        # Deployment of these files forces the boot process to stall until the correct hostname
        # has been set.  Various services don't like it if the hostname changes after they have
        # been launched.
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
                require =>  File["/etc/default/maybe_wait_for_real_hostname"],
            ;
        }

        if $testuser {
            # This kdmrc forces autologin of $testuser
            file {
                "/etc/kde4/kdm/kdmrc":
                    owner   =>  "root",
                    mode    =>  0444,
                    content =>  template("baselayout/kdmrc.ubuntu.erb")
                ;
            }
        }
    }

    #========================== Ubuntu 11.10 stuff ============================
    # This logic only applies to Ubuntu 11.10, where we use Gnome.
    #
    if $lsbmajdistrelease >= 11 {
        package {
            "ubuntu-desktop":   ensure => installed;
            "dconf-tools":      ensure => installed;
        }

        # Ensure we set the hostname to whatever is returned by DHCP.
        # Scriptlets in the dhclient-enter-hooks.d directory are sourced by dhclient-script
        # whenever the connection comes up.  We deploy a scriptlet which explicitly sets the
        # hostname according to the value returned by the DHCP response.
        file {
            "/etc/dhcp/dhclient-enter-hooks.d/qtqa-set-hostname":
                owner   =>  "root",
                mode    =>  0444,
                source  =>  "puppet:///modules/baselayout/ubuntu/dhclient-enter-hooks.d/qtqa-set-hostname",
            ;
        }

        if $testuser {
            file {
                # This enables autologin as $testuser
                "/etc/lightdm/lightdm.conf":
                    owner   =>  "root",
                    mode    =>  0444,
                    content =>  template("baselayout/lightdmconf.ubuntu.erb"),
                    require =>  Package["ubuntu-desktop"],
                ;
                # This file controls the automatic check for package updates.
                # The version deployed disables this check
                "/etc/apt/apt.conf.d/10periodic":
                    owner   =>  "root",
                    mode    =>  0444,
                    source  =>  "puppet:///modules/baselayout/ubuntu/10periodic",
                ;
            }
            exec {
                "disable screensaver lock":
                    command => "/bin/sh -c 'DISPLAY=:0 dconf write /org/gnome/desktop/screensaver/lock-enabled false'",
                    onlyif  => "/bin/sh -c 'test x$(DISPLAY=:0 dconf read /org/gnome/desktop/screensaver/lock-enabled) != xfalse'",
                    user    => $testuser,
                    require => Package["dconf-tools", "ubuntu-desktop"],
                ;
                "disable screen off":
                    command => "/bin/sh -c 'DISPLAY=:0 dconf write /org/gnome/desktop/session/idle-delay \"uint32 0\"'",
                    onlyif  => "/bin/sh -c 'test x$(DISPLAY=:0 dconf read /org/gnome/desktop/session/idle-delay) != \"xuint32 0\"'",
                    user    => $testuser,
                    require => Package["dconf-tools", "ubuntu-desktop"],
                ;
                "disable remind-reload query":
                    command => "/bin/sh -c 'DISPLAY=:0 dconf write /apps/update-manager/remind-reload false'",
                    onlyif  => "/bin/sh -c 'test x$(DISPLAY=:0 dconf read /apps/update-manager/remind-reload) != xfalse'",
                    user    => $testuser,
                    require => Package["dconf-tools", "ubuntu-desktop"],
                ;
            }
        }
    }

    #========================= hostname fix ===================================
    # This workaround seems required on Ubuntu 10.04 and 12.04, but not 11.10
    if $lsbmajdistrelease != 11 {
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
    }


    #========================== common stuff ==================================
    # Applies to all supported Ubuntu versions
    #
    file {
        "/etc/profile.d/99homepath.sh":
            owner   =>  "root",
            mode    =>  0444,
            source  =>  "puppet:///modules/baselayout/ubuntu/profile.d/99homepath.sh",
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

}
