# Downloads the given $version of mysql and installs to the specified $path.
class mysql::windows(
    $version = '5.6.11',
    $path = 'C:\utils\mysql'
) {

    $os_bits = $::architecture ? {
        x64     => "x64",
        default => "32"
    }

    # First two digits of the $version are in the url (e.g. .../MySQL-5.6/...)
    $regexp = '^(\d+)\.(\d+)\.(\d+)$'
    $url_version_number = regsubst($version, $regexp, '\1.\2')

    # Package name after unzipping
    $pkg_name = "mysql-${version}-win${os_bits}"

    # installer file URL
    $url = "http://dev.mysql.com/get/Downloads/MySQL-${url_version_number}/${pkg_name}.zip/from/http://cdn.mysql.com/"

    unzip_package { "mysql":
        url     => $url,
        version => $version,
        path    => $path,
        binary  => "$path\\mysql\\bin\\mysql.exe",
        notify  => Exec["rename $pkg_name as mysql"],
    }

    exec { "rename $pkg_name as mysql":
        command     => "C:\\Windows\\system32\\cmd.exe /C \"rename ${path}\\${pkg_name} mysql\"",
        creates     => "${path}\\mysql",
        subscribe   => Unzip_package["mysql"],
        refreshonly => true
    }
}
