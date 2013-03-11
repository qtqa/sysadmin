# Download and install Android ndk and sdk to $target. Update will be done and
# old version will be removed if $filename is changed to point to new or older version.
class android::linux
{
    $url = "http://dl.google.com/android"
    $target = "/opt/android"

    file { "$target":
        ensure  =>  directory,
        owner   =>  root,
        group   =>  users,
        mode    =>  0755,
    }

    define android_install($filename,$directory,$options,$generic_dir,$target,$url) {
        exec { "install $filename to ${target}/${directory}":
            command =>  "/bin/bash -c '\
                (if [ -e ${generic_dir} ]; then rm -fr ${generic_dir}; fi) \
                && wget $url/${filename} -O - | tar -C $target -$options \
                && mv ${target}/${directory} ${generic_dir} \
                && echo $filename > ${generic_dir}/version.txt \
                && chown -R $testuser: $generic_dir \
                && (if [ $generic_dir == $target/sdk ]; then ${generic_dir}/tools/android update sdk --no-ui; fi)'",
            unless => "/bin/bash -c '\
                grep \"$filename\" ${generic_dir}/version.txt'",
            require =>  File["$target"],
            timeout =>  1800,
        }
    }

    android_install {
        "ndk":
            filename    =>  "ndk/android-ndk-r8d-linux-x86.tar.bz2",
            directory   =>  "android-ndk-r8d",
            options     =>  "xj",
            target      =>  "$target",
            url         =>  "$url",
            generic_dir =>  "${target}/ndk",
        ;
        "sdk":
            filename    =>  "android-sdk_r21.0.1-linux.tgz",
            directory   =>  "android-sdk-linux",
            options     =>  "zx",
            target      =>  "$target",
            url         =>  "$url",
            generic_dir =>  "${target}/sdk",
        ;
    }
}
