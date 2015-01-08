# Downloads the given $version of openssl-android-master from $input and installs it to the specified $path.
# If a different openssl-android version is already installed there, it is uninstalled first.
class openssl_android::windows(
    $version = '1.0.1j',
    $path = 'C:\utils'
) {
    # Currently only 32-bit version can be found for openssl-android!
    if ($::architecture == "x86") {

        # installer file URL
        $url = "${input}/windows/platform_external_openssl-master.zip"

        unzip_package { "openssl_android":
            url => $url,
            version => $version,
            version_flags => "${path}\\platform_external_openssl-master\\openssl.version",
            path => "${path}\\platform_external_openssl-master",
            unzip_flags => "x -o$path",
            binary => "\"C:\\Program Files\\Git\\bin\\cat.exe\""
        }
    }
}
