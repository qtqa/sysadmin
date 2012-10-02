class crosscompilers::linux
{
    file { "/opt/codesourcery":
        ensure  =>  directory,
        owner   =>  root,
        group   =>  users,
        mode    =>  0755,
    }

    define crosscompiler($filename,$directory) {
        exec { "install $filename to /opt/codesourcery/$directory":
            command =>  "/bin/sh -c 'wget $input/codesourcery/$filename -O - | tar -C /opt/codesourcery -xvj'",
            creates =>  "/opt/codesourcery/$directory",
            require =>  File["/opt/codesourcery"],
        }
    }

    crosscompiler {
        "arm":
            filename    =>  "arm-2010q1-202-arm-none-linux-gnueabi-i686-pc-linux-gnu.tar.bz2",
            directory   =>  "arm-2010q1",
        ;
        "mips":
            filename    =>  "mips-4.4-203-mips-linux-gnu-i686-pc-linux-gnu.tar.bz2",
            directory   =>  "mips-4.4",
        ;
        "powerpc":
            filename    =>  "freescale-4.4-196-powerpc-eabi-i686-pc-linux-gnu.tar.bz2",
            directory   =>  "freescale-4.4",
        ;
    }
}

