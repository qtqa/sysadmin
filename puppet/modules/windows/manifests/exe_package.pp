# Install (or uninstall, reinstall) a Windows package with an .exe installer.
#
# http://unattended.sourceforge.net/installers.php is recommended reading;
# it has valuable tips on unattended installation of Windows packages,
# including hints on how to figure out the $type of an installer.
#
# Major caveat: even if an installer is known to be one of the types supported
# by this module, there is no guarantee it is automatable at all. Just try it
# and see.
#
define windows::exe_package(

    # URL of the .exe to be downloaded and installed;
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

    # Type of the installer:
    #
    #  nsis:    nullsoft installer
    #  inno:    innosetup
    #
    # All other types are not yet supported
    $type,

    # Expected path to uninstaller (after package is installed);
    # default is determined from $type
    $uninstall_binary = undef,

    # Flags used for unattended uninstallation;
    # default is determined from $type
    $uninstall_flags = undef,

    # Path to installer; leave undefined if $url is passed, in which
    # case the installer is downloaded
    $install_binary = undef,

    # Flags used for unattended installation;
    # default is determined from $type
    $install_flags = undef
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
        $real_install_binary = "$tempdir\\$safename-installer.exe"
        $fetch_cmd = "$curl \"$url\" -L -o \"$real_install_binary\" &&"
    } else {
        $real_install_binary = $install_binary
        $fetch_cmd = ""
    }

    case $type {
        'inno': {
            $type_install_flags = "/SP- /SILENT /LOG /SUPPRESSMSGBOXES /NORESTART /DIR=\"$path\""
            $type_uninstall_binary = "$path\\unins000.exe"
            $type_uninstall_flags = "/SP- /SILENT /SUPPRESSMSGBOXES /NORESTART"
        }
        'nsis': {
            # note: deliberately omit quotes around $path in /D=$path,
            # using quotes there will break it.  I'm not sure how/if this
            # works if $path contains spaces...
            $type_install_flags = "/S /D=$path"
            $type_uninstall_binary = "$path\\uninstall.exe"
            $type_uninstall_flags = "/S"
        }
        # add more as needed
        default: {
            fail("installer type $type is not supported")
        }
    }

    $real_install_flags = $install_flags ? {
        undef => $type_install_flags,
        default => $install_flags
    }
    $real_uninstall_flags = $uninstall_flags ? {
        undef => $type_uninstall_flags,
        default => $uninstall_flags
    }
    $real_uninstall_binary = $uninstall_binary ? {
        undef => $type_uninstall_binary,
        default => $uninstall_binary
    }
    $real_version_expression = $version_expression ? {
        undef => "\\b$version\\b",
        default => $version_expression
    }

    exec { "install $name $version to $path":

        command => "$cmd /C \"\
$fetch_cmd \
( if exist \"$real_uninstall_binary\" \
  start \"uninstall\" /wait \"$real_uninstall_binary\" $real_uninstall_flags ) \
& ( if exist \"$path\" \
    rd /S /Q \"$path\" ) \
&& start \"install\" /wait \"$real_install_binary\" $real_install_flags \
\"",

        unless => "$cmd /C \"\
\"$binary\" $version_flags | $grep -E \"$real_version_expression\"\
\"",

        logoutput => true,
        timeout => 3600
    }
}
