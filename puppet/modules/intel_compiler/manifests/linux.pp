class intel_compiler::linux
{
    File {
        owner   =>  root,
        group   =>  users,
    }
    file {
        "/opt":
            ensure  =>  directory,
            mode    =>  0755,
        ;
        "/opt/intel":
            ensure  =>  directory,
            mode    =>  0755,
            require =>  File["/opt"],
        ;
        "/opt/intel/licenses":
            ensure  =>  directory,
            mode    =>  0755,
            require =>  File["/opt/intel"],
        ;
    }

    secret_file { "/opt/intel/licenses/linux.lic":
        source  =>  "intel/COM_L__XXXX-XXXXXXW8.lic",
        require =>  File["/opt/intel/licenses"],
    }

    exec { "install intel compiler":
        require =>  Secret_file["/opt/intel/licenses/linux.lic"],
        command =>  "/bin/sh -c '

    rm -rf /tmp/intel_compiler_install &&
    mkdir -p /tmp/intel_compiler_install &&
    cd /tmp/intel_compiler_install &&
    wget $input/intel/parallel_studio_xe_2011.tgz -O - | tar -xvz &&
    wget $input/intel/parallel_studio_xe_2011_linux_install.ini &&
    ./parallel_studio_xe_2011/install.sh --silent ./parallel_studio_xe_2011_linux_install.ini &&
    rm -rf /tmp/intel_compiler_install

        '",
        creates     =>  "/opt/intel/bin/icc",
        logoutput   =>  true,
    }
}

