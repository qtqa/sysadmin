class baselayout::opensuse inherits baselayout::linux {

        if $baselayout::testuser {
            # This kdmrc forces autologin of $baselayout::testuser
            exec {"Set autologin for $baselayout::testuser":
                command   =>  "/usr/bin/sed -i 's/^\\(DISPLAYMANAGER_AUTOLOGIN\\)=.*/\\1=\"$baselayout::testuser\"/' /etc/sysconfig/displaymanager",
                unless => "/usr/bin/grep -e \'^DISPLAYMANAGER_AUTOLOGIN=\"$baselayout::testuser\"\' /etc/sysconfig/displaymanager",
            }

            # This kscreensaverrc disables the screensaver for $baselayout::testuser
            file {
                [ "/home/$baselayout::testuser/.kde4",
                  "/home/$baselayout::testuser/.kde4/share",
                  "/home/$baselayout::testuser/.kde4/share/config" ]:
                    ensure => directory,
                    group => users,
                    owner => $baselayout::testuser,
            ;
                "/home/$baselayout::testuser/.kde4/share/config/kscreensaverrc":
                    owner   => $baselayout::testuser,
                    group => users,
                    mode   => 0600,
                    source => "puppet:///modules/baselayout/opensuse/kscreensaverrc",
            }
        }

    file {
        "/etc/profile.d/99homepath.sh":
            owner   =>  "root",
            mode    =>  0444,
            source  =>  "puppet:///modules/baselayout/opensuse/profile.d/99homepath.sh",
        ;
    }

    package {
        # Nano editor:
        "nano": ensure => installed;
    }
}
