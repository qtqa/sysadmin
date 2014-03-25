# Download and unzip I.MX6's toolchain to $target. Update will be done and
# old version will be removed if $filename is changed to point to new or older version.
class imx6::linux
{
    $url = "$input/imx6"
    $filename = "b2qt-eglibc-x86_64-arm-toolchain-iMX6.sh"
    $target = "/opt/imx6"
    $timestamp = "20140325"

    define imx6_install($filename,$workdir,$target,$url) {
        exec { "install $filename to $target":
            command =>  "/bin/bash -c '\
                (if [ -e $target ]; then rm -fr $target; fi) \
                && wget $url/$filename -O $workdir/$filename \
                && chmod +x $workdir/$filename \
                && $workdir/$filename -y -d $target \
                && echo $timestamp > $target/version.txt'",
            unless => "/bin/bash -c 'grep \"$timestamp\" $target/version.txt'",
            timeout =>  1800,
        }
    }

    if $::lsbmajdistrelease == 12 {
        if $::architecture == amd64 {
            imx6_install {
                "toolchain":
                    filename    =>  "$filename",
                    workdir     =>  "/tmp",
                    target      =>  "$target",
                    url         =>  "$url",
                ;
            }
        }
    }
}
