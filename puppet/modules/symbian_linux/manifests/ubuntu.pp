class symbian_linux::ubuntu
{
    exec { "install RVCT under /opt":
        command     =>  "/bin/sh -c 'wget $input/symbian-linux/rvct-2.2.686.tar.gz -O - | tar -C /opt -xvz'",
        creates     =>  "/opt/rvct-2.2.686/bin",
        logoutput   =>  true,
    }

    exec { "install gcce":
        command     =>  "/bin/sh -c '

        wget $input/symbian-linux/gcce-4.4.172-r1.deb -O /tmp/gcce.deb  \
            && dpkg --install /tmp/gcce.deb                             \
            && rm -f /tmp/gcce.deb

        '",
        creates     =>  "/usr/bin/arm-none-symbianelf-gcc-4.4.1",
        logoutput   =>  true,
    }

    # wine is used to run the .exe files from the S60 SDKs
    package { "wine1.2":
        ensure  =>  present,
    }

    if $testuser {
        # This wrapper allows armcc and ccache to work nicely together.
        file { "/home/$testuser/bin/armcc":
            source  =>  "puppet:///modules/symbian_linux/armcc_ccache",
            mode    =>  0755,
        }
    }

}

