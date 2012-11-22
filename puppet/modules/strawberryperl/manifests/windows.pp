# Downloads the given $version of strawberryperl from http://strawberryperl.com and installs to the specified $path.
# If a different strawberryperl version is already installed there, it is uninstalled first.
class strawberryperl::windows(
    $version = '5.14.2.1',
    $path = 'C:\strawberry'
) {
    $bits = $::architecture ? {
        x64 => "64bit",
        default => "32bit"
    }

    # installer file URL
    $url = "http://strawberry-perl.googlecode.com/files/strawberry-perl-${version}-${$bits}.msi"

    # perl versions without build part
    $version_no_buildpart = regsubst($version, '^(\d+)\.(\d+)\.(\d+).(\d+)$', '\1.\2.\3')

    # perl outputs version without build part
    $version_expression = "\\b$version_no_buildpart\\b"

    windows::msi_package { "strawberryperl":
        url => $url,
        version => $version,
        version_expression => $version_expression,
        install_flags => "/QB",
        path => $path,
        binary => "$path\\bin\\perl.exe"
    }
}
