# Downloads the wince_powervr_sdk from $input and installs to the specified $path.
class wince_powervr_sdk::windows(
    $version = '02_00_00',
    $path = 'C:\utils\wince_powervr_sdk'
) {
    # installer file URL
    $url = "${input}/wince/wince_gfx_sgx_${version}.zip"

    # Store version to version.txt for update purposes
    $versiontxt = "${path}\\version.txt"

    unzip_package { "wince_powervr_sdk":
        url => $url,
        version => $version,
        version_flags => $versiontxt,
        path => $path,
        binary => "more"
    }

    file { "${versiontxt}":
        ensure => present,
        content => "$version",
        require => Unzip_package["wince_powervr_sdk"],
    }
}
