class vmware_tools::linux {
    exec { "install vmware tools":
        command     =>  "/bin/sh -c 'wget $input/vmware/VMwareTools-4.0.0-261974.tar.gz -O - | tar -C /tmp -xvz && /tmp/vmware-tools-distrib/vmware-install.pl < /dev/null && rm -rf /tmp/vmware-tools-distrib'",
        creates     =>  "/usr/bin/vmware-uninstall-tools.pl",
    }
}

