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
        # elf2e32, or any other .exe files from the SDK, may crash.
        # By default this would pop up an interactive crash dialog, which is
        # unacceptable for us.  This will disable that dialog.
        #
        # Gleaned from: http://wiki.winehq.org/FAQ#head-c857c433cf9fc1dcd90b8369ef75c325483c91d6
        #
        # Note that we are not attempting to accurately parse the registry
        # (e.g. using regedit).  In theory, this could cause problems if
        # the registry contains some unusual content.
        # In practice, I feel that this is a better option than running
        # regedit at each puppet run, as that would instantiate the entire
        # wine environment (e.g. will launch wineserver) even if the test
        # machine is not intending to run any wine apps.  It's hard to predict
        # what unusual problems that may cause.
        #
        $user_reg = "/home/$testuser/.wine/user.reg"
        exec { "disable wine crash dialog":

            # correct backslash counts were determined by experimentation
            command =>  "/bin/sh -c '
                {
                    echo;
                    /bin/echo -E [Software\\\\\\\\Wine\\\\\\\\WineDbg];
                    /bin/echo -E \\\"ShowCrashDialog\\\"=dword:00000000;
                } >> $user_reg
            '",

            onlyif  =>  "/bin/sh -c '
                test -f $user_reg && ! grep -q \\\"ShowCrashDialog\\\"=dword:00000000 $user_reg
            '",
        }

        # This wrapper allows armcc and ccache to work nicely together.
        file { "/home/$testuser/bin/armcc":
            source  =>  "puppet:///modules/symbian_linux/armcc_ccache",
            mode    =>  0755,
        }
    }

}

