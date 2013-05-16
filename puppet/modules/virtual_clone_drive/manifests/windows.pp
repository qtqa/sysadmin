# Downloads the given $version of virtual clone drive (VCD) and installs it. $Version is saved to
# installer folder as text file. Both, #url and #version, need to change whe updating VCD.
class virtual_clone_drive::windows(
    $url = 'http://static.slysoft.com/SetupVirtualCloneDrive.exe',
    $version = '5.4.5.0'
) {
    $msysbin = $::architecture ? {
        x64     => 'c:\Program Files (x86)\Git\bin',
        default => 'c:\Program Files\Git\bin'
    }

    $path = $::architecture ? {
        x64     => 'c:\Program Files (x86)\Elaborate Bytes\VirtualCloneDrive',
        default => 'c:\Program Files\Elaborate Bytes\VirtualCloneDrive'
    }

    # directory to temporarily hold the downloaded installer
    $tempdir = $baselayout::tempdir

    # fetch command
    $fetch_cmd = "\"${msysbin}\\curl.exe\" $url -L -o \"${tempdir}\\setupvirtualclonedrive.exe\""

    # Kill Daemon task before uninstalling
    $taskkill = "taskkill /IM VCDDaemon.exe"

    # These files need to be removed before uninstall. Deleting 'ExecuteWithUAC.exe' makes uninstall conflict
    $remove_files = "del \"${path}\\ExecuteWithUAC.exe\" && del \"${path}\\version.txt\""

    # This will uninstall VCD.
    $uninstall_cmd = "(if exist \"${path}\\VCDDaemon.exe\" $taskkill && $remove_files && \"${path}\\vcd-uninst.exe\" /S)"

    # This will install VCD. VCD will be automatically instaled to $path
    $install_cmd = "${tempdir}\\SetupVirtualCloneDrive.exe /S /A /A"

    # Save version number to $path
    $version_number = "echo $version > \"$path\\version.txt\""

    exec { "install $name to $path C:\\Windows\\system32\\cmd.exe /C \"\"${msysbin}\\grep.exe\" -nr \"$version\" \"${path}\\version.txt\"":

        command   => "C:\\Windows\\system32\\cmd.exe /C \"$uninstall_cmd && $fetch_cmd && $install_cmd && $version_number\" ",
        unless    => "C:\\Windows\\system32\\cmd.exe /C \"\"${msysbin}\\grep.exe\" -nr \"$version\" \"${path}\\version.txt\"\"",
        logoutput => true,
        timeout   => 3600
    }
}
