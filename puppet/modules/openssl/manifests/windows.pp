# Downloads the given $version of openssl from http://slproweb.com and installs to the specified $path.
# If a different openssl version is already installed there, it is uninstalled first.
#
# Note installing OpenSSL has dependency to VCRedist package which is not currently managed by puppet
class openssl::windows(
    $version = '1.0.1e',
    $path = 'C:\openssl'
) {
    # Version number with underscores, for example '0_9_8x'
    $version_underscore = regsubst($version, '\.', '_', "G")

    $bits = $::architecture ? {
        x64 => "64",
        default => "32"
    }

    # installer file URL
    $url = "http://slproweb.com/download/Win${bits}OpenSSL-${version_underscore}.exe"

    windows::exe_package { "openssl":
        url => $url,
        version => $version,
        version_flags => 'version',
        path => $path,
        type => 'inno',
        binary => "$path\\bin\\openssl.exe"
    }
}
