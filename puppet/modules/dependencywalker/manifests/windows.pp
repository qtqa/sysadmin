# Downloads the given $version of dependencywalker and installs to the specified $path.
# If a different dependencywalker version is already installed there, it is uninstalled first.
class dependencywalker::windows(
    $version = '2.2.6',
    $path = 'C:\utils\dependencywalker'
) {
    $os_bits = $::architecture ? {
        x64 => "x64",
        default => "x86"
    }

    # Version without build part
    $version_no_buildpart = regsubst($version, '^(\d+)\.(\d+)\.(\d+)$', '\1\2')

    # installer file URL
    $url = "http://www.dependencywalker.com/depends${version_no_buildpart}_${os_bits}.zip"

    $version_number = "${path}\\${version}.txt"

    unzip_package { "dependencywalker":
        url => $url,
        path => $path,
        version => $version,
        binary => "more",
        version_flags => "$version_number",
    }

    # Store version number to $path
    file { "${version_number}":
        ensure => present,
        content => "$version",
        require => Unzip_package["dependencywalker"],
    }
}
