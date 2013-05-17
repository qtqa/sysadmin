# Downloads the given $version of Java and installs to the specified $path.
# If a different Java version is already installed there, it is uninstalled first.
class java::windows(
    # url version is needed because url path will be changed after every java release (e.g 7u17-b02 or 6u43-b01). When not using $input in $url!
    # $url_version = '7u7',
    # $jre_cookie = "gpw_e24=http%3A%2F%2Fwww.oracle.com%2Ftechnetwork%2Fjava%2Fjavase%2Fdownloads%2Fjre7-downloads-1880261.html"
    $version = '1.7.0_7',
    $path = "C:\\Program Files\\Java"
) {
    $os_bits = $::architecture ? {
        x64 => "x64",
        default => "i586"
    }

    # Build (second) portion of version number
    $regexp = '^(\d+)\.(\d+)\.(\d+)\_(\d+)$'
    $version_min = regsubst($version, $regexp, '\2')

    # Build (fourth) portion of version number (e.g. '34')
    $version_update = regsubst($version, $regexp, '\4')

    # First three digits of java outputs version number
    $version_number = regsubst($version, $regexp, '\1.\2.\3')

    # Java version suitable for download URL (e.g. '6u34')
    $package_version = "${version_min}u${version_update}"

    # installer file URL
    # $url = "http://download.oracle.com/otn-pub/java/jdk/${url_version}/jre-${package_version}-windows-${os_bits}.exe"

    # Input url
    $url = "$input/windows/jre-$package_version-windows-$os_bits.exe"

    # Build portion of version number with two digits (e.g. '07')
    $version_update2 = sprintf('%02d', $version_update)

    # Java outputs version number
    $version_expression = "${version_number}_${version_update2}"

    $msysbin = $::architecture ? {
        x64     => 'c:\Program Files (x86)\Git\bin',
        default => 'c:\Program Files\Git\bin'
    }

    # directory to temporarily hold the downloaded installer
    $tempdir = $baselayout::tempdir

    $install_package = "${tempdir}/$version_expression.msi"

    $install_flags = "/s /v\"/passive /norestart AUTOUPDATECHECK=0 IEXPLORER=1 JAVAUPDATE=0 JU=0 EULA=1\""

    # "--cookie $jre_cookie" this need to be add to the fetch command when not using $input in URL
    $fetch_cmd = "\"${msysbin}\\curl.exe\" \"$url\" -L -o \"${install_package}\""

    # Set product code to environment variables.
    $set_pcode_env_cmd = "set /p pcode=<\"$path\\ProductCode.txt\""

    # Java need to be killed before uninstalling
    $kill_java = "taskkill /f /im java.exe"

    # Uninstall command will uninstall the java using product code which was saved after installation.
    $uninstall_cmd = "( if exist \"$path\\ProductCode.txt\" $set_pcode_env_cmd) && $kill_java && start \"uninstall\" /wait msiexec /QB /x !pcode!"

    # Java folder will be removed which in this case includes old ProductCode.txt.
    $remove_ProductCode = "(if exist \"$path\\ProductCode.txt\" del \"$path\\ProductCode.txt\")"

    # Directory where java will be installed
    $install_path = "${path}\\jre${version_min}"

    # Install command install's java using install package from $tempdir
    $install_cmd = "start \"install\" /wait \"$install_package\" $install_flags INSTALLDIR=\\\"$install_path\\\" REBOOT=REALLYSUPPRESS"

    # Save installed java´s product code to $path after installation. Product code is used when uninstalling java.
    $product_code = "reg QUERY HKLM\\SOFTWARE\\Microsoft\\Windows\\CurrentVersion\\Uninstall  /f \"$path\" /s | \"${msysbin}\\sed\" -n 's/.*Uninstall\\(.*\\)/\\1/p'| \"${msysbin}\\cut\" -c2-39 > \"$path\\ProductCode.txt\""

    $binary = "${install_path}\\bin\\java.exe"

    exec { "install $name $version to $path $url C:\\Windows\\system32\\cmd.exe /V:ON /C \"$fetch_cmd && $uninstall_cmd & $remove_ProductCode && $install_cmd & $product_code \"":

        command   => "C:\\Windows\\system32\\cmd.exe /V:ON /C \"$fetch_cmd && $uninstall_cmd & $remove_ProductCode && $install_cmd & $product_code \"",
        unless    => "C:\\Windows\\system32\\cmd.exe /C \"\"$binary\" -version 2>&1 | \"${msysbin}\\grep.exe\" -E \"$version_expression\"\"",
        logoutput => true,
        timeout   => 3600

    }
}
