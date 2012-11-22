# Downloads the given $version of activepython from activestate.com and installs to the specified $path.
# If a different activepython version is already installed there, it is uninstalled first.
class activepython::windows(
    $version = '2.7.2.5',
    $path = 'C:\Python27'
) {
    $os_bits = $::architecture ? {
        x64 => "win64-x64",
        default => "win32-x86"
    }

    # installer file URL
    $url = "http://downloads.activestate.com/ActivePython/releases/${version}/ActivePython-${version}-${os_bits}.msi"

    # Version without build part
    $version_no_buildpart = regsubst($version, '^(\d+)\.(\d+)\.(\d+).(\d+)$', '\1.\2.\3')

    # Activepython outputs version without build part
    $version_expression = "\\b$version_no_buildpart\\b"

    windows::msi_package { "activepython":
        url => $url,
        version => $version,
        version_expression => $version_expression,
        path => $path,
        binary => "$path\\python.exe"
    }
}
