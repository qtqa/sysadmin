# Downloads the given $version of postgresql and installs to the specified $path.
class postgresql::windows(
    $version = '9.1.9-1',
    $path = 'C:\utils\postgresql'
) {

    $os_bits = $::architecture ? {
        x64     => "-x64",
        default => ""
    }

    # version number in postgres.exe is first three portion of $version
    $regexp = '^(\d+)\.(\d+)\.(\d+)-(\d+)$'
    $version_number = regsubst($version, $regexp, '\1.\2.\3')

    # installer file URL
    $url = "http://get.enterprisedb.com/postgresql/postgresql-${version}-windows${os_bits}-binaries.zip"

    unzip_package { "$name":
        url     => $url,
        version => $version_number,
        path    => $path,
        binary  => "$path\\pgsql\\bin\\postgres.exe"
    }
}