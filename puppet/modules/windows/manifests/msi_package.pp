# Install (or uninstall, reinstall) a Windows MSI package with msiexec.
#
define windows::msi_package(

    # URL of the .msi to be downloaded and installed;
    # leave undefined if $install_binary should not be downloaded
    $url = undef,

    # Binary expected to be installed (e.g. c:\perl\bin\perl.exe)
    $binary,

    # Desired installation directory (e.g. c:\perl)
    $path,

    # Desired version (e.g. 5.14.2)
    $version,

    # Flags passed to $binary to make it output its version number
    $version_flags = '--version',

    # Regular expression matching the desired output when running
    # $binary with $version_flags; leave undefined for a reasonable
    # default.
    # Example: "\\b5\\.14\\.2\\b"
    $version_expression = undef,

    # Flags used for unattended uninstallation;
    $uninstall_flags = "/QB",

    # Path to installer; leave undefined if $url is passed, in which
    # case the installer is downloaded
    $install_package = undef,

    # Flags used for unattended installation;
    $install_flags = "/QB /L*v $tempdir\\$name.log",

    # Properties to be passed for MSI installer;
    $install_properties = "INSTALLDIR=$path REBOOT=REALLYSUPPRESS"
) {
    $msysbin = $::architecture ? {
        x64     => 'c:\Program Files (x86)\Git\bin',
        default => 'c:\Program Files\Git\bin'
    }

    $curl = "\"$msysbin\\curl.exe\""
    $grep = "\"$msysbin\\grep.exe\""

    $cmd = 'C:\Windows\system32\cmd.exe'


    # directory to temporarily hold the downloaded installer
    $tempdir = $baselayout::tempdir

    if $url {
        $safename = regsubst($name, "[^a-zA-Z0-9]", "-", "G")
        $real_install_package = "$tempdir\\$safename-installer.msi"
        $fetch_cmd = "$curl \"$url\" -L -o \"$real_install_package\" &&"
    } else {
        $real_install_package = $install_package
        $fetch_cmd = ""
    }

    $real_version_expression = $version_expression ? {
        undef => "\\b$version\\b",
        default => $version_expression
    }

    exec { "install $name $version to $path":

        command => "$cmd /C \"\
$fetch_cmd \
( if exist \"$path\\uninstaller_for_puppet.msi\" \
  start \"uninstall\" /wait msiexec /x \"$path\\uninstaller_for_puppet.msi\" $uninstall_flags ) \
& ( if exist \"$path\" \
    rd /S /Q \"$path\" ) \
 && start \"install\" /wait msiexec /i \"$real_install_package\" $install_flags $install_properties \
 && copy \"$real_install_package\" \"$path\\uninstaller_for_puppet.msi\" \
 \"",

        unless => "$cmd /C \"\
\"$binary\" $version_flags 2>&1 | $grep -E \"$real_version_expression\"\
\"",

        logoutput => true,
        timeout => 3600

    }
}
