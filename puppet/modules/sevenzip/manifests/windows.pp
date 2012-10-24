# Downloads the given $version of sevenzip from 7-zip.org and installs to the specified $path.
# If a different 7-zip version is already installed there, it is uninstalled first.
class sevenzip::windows(
    $version = '9.20',
    $version_flags = '',
    $path = 'C:\utils\sevenzip'
) {
    # Version without dots, for example '920'
    $version_no_dots = regsubst($version, '\.', '', "G")

    # installer file URL
    $url = "http://downloads.sourceforge.net/sevenzip/7z${version_no_dots}.exe"

    windows::exe_package { "sevenzip":
        url => $url,
        version => $version,
        version_flags => $version_flags,
        path => $path,
        type => 'nsis',
        binary => "$path\\7z.exe"
    }
}
