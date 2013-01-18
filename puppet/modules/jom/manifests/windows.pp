# Downloads the given $version of jom from qt-project.org and installs to the specified $path.
# If a different jom version is already installed there, it is uninstalled first.
class jom::windows(
    $version = '1.0.13',
    $path = 'C:\utils\jom'
) {
    # Jom zip package name has always two digits in build number portion(e.g. jom_1_0_06.zip)

    # First two portions of version number with underscore (e.g. '1_0')
    $version_majmin = regsubst($version, '^(\d+)\.(\d+)\.(\d+)$', '\1_\2')

    # Build (third) portion of version number (e.g. '6')
    $version_build = regsubst($version,  '^(\d+)\.(\d+)\.(\d+)$', '\3')

    # Build portion of version number with two digits (e.g. '06')
    $version_build2 = sprintf('%02d', $version_build)

    # Jom version suitable for download URL (e.g. '1_0_06')
    $downloadable_version = "${version_majmin}_${version_build2}"

    # installer file URL
    $url = "http://origin.releases.qt-project.org/jom/jom_${downloadable_version}.zip"

    windows::zip_package { "jom":
        url => $url,
        version => $version,
        path => $path,
        binary => "$path\\jom.exe",
        version_flags => "/VERSION"
    }
}
