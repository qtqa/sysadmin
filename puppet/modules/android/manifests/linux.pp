# Download and install Android ndk and sdk to $target. Update will be done and
# old version will be removed if $filename is changed to point to new or older version.
class android::linux
{
    $target = "/opt/android"
    $ndk_filename = "android-ndk-r10c-linux-x86.bin"
    $sdk_filename = "android-sdk_r23.0.2-linux.tar.gz"

    file { "$target":
        ensure  =>  directory,
        owner   =>  root,
        group   =>  users,
        mode    =>  0755,
    }

    # Environment variable ANDROID_NDK_HOST depend on nodes architecture
    $ndk_host = $::architecture ? {
        i386    => "linux-x86",
        default => "linux-x86_64",
        }

    file { "/etc/profile.d/android_env.sh":
        ensure  => present,
        content => template("android/android_env.sh.erb"),
        }

    define android_install($filename,$directory,$generic_dir,$target,$fetch) {
        exec { "install $filename to ${target}/${directory}":
            command =>  "/bin/bash -c '\
                (if [ -e ${generic_dir} ]; then rm -fr ${generic_dir}; fi) \
                && $fetch \
                && mv ${target}/${directory} ${generic_dir} \
                && echo $filename > ${generic_dir}/version.txt \
                && chown -R $testuser: $generic_dir'",
            unless => "/bin/bash -c '\
                grep \"$filename\" ${generic_dir}/version.txt'",
            require =>  File["$target"],
            timeout =>  1800,
        }
    }

    android_install {
        "ndk":
            filename    =>  "$ndk_filename",
            directory   =>  "android-ndk-r10c",
            target      =>  "$target",
            generic_dir =>  "${target}/ndk",
            fetch       =>  "wget http://dl.google.com/android/ndk/android-ndk-r10c-linux-x86.bin -O ${target}/${ndk_filename} && chown $testuser: ${target}/${ndk_filename} && chmod 755 ${target}/${ndk_filename} && cd ${target} && ./${ndk_filename}",
        ;
        "sdk":
            filename    =>  "$sdk_filename",
            directory   =>  "android-sdk-r2302",
            target      =>  "$target",
            generic_dir =>  "${target}/sdk",
            fetch       =>  "wget ${input}/ubuntu/${sdk_filename} -O - | tar -C $target -zx",
        ;
    }
}
