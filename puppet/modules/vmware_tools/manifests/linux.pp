class vmware_tools::linux {
    $version = $location ? {
        "Digia" => "8.6.5-621624",
        default => "4.0.0-261974"
    }

    exec { "install vmware tools":
        command     =>  "/bin/sh -c 'wget $input/vmware/VMwareTools-$version.tar.gz -O - | tar -C /tmp -xvz && /tmp/vmware-tools-distrib/vmware-install.pl < /dev/null && rm -rf /tmp/vmware-tools-distrib'",
        creates     =>  "/usr/bin/vmware-uninstall-tools.pl",
    }
}
