# Downloads the given $version of openssl-android-master from $input and installs it to the specified $path.
# If a different openssl-android version is already installed there, it is uninstalled first.
class openssl_android::windows(
    $version = '1.0.0a',
    $path = 'C:\utils'
) {
    # Currently only 32-bit version can be found for openssl-android!
    if ($::architecture == "x86") {

        # installer file URL
        $url = "${input}/windows/openssl-android-master.zip"

        unzip_package { "openssl_android":
            url => $url,
            version => $version,
            version_flags => "${path}\\openssl-android-master\\openssl.version",
            path => "${path}\\openssl-android-master",
            unzip_flags => "x -o$path",
            binary => "\"C:\\Program Files\\Git\\bin\\cat.exe\""
        }
    }
}
