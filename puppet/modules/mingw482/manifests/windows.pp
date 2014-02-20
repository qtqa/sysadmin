# Downloads the 4.8.2 version of mingw from $input and installs to the specified $path.

class mingw482::windows(
    $version = '4.8.2',
    $altversion = '482',
    $path = 'C:\mingw482',

    # Additional options for installed MinGW version, See also: http://qt-project.org/wiki/MinGW-64-bit
    # Note: Changing any of these won't trigger MinGW re-installation.
    #
    # either 'posix' or 'win32', we use 'posix' since it is more popular
    $threading = 'posix',

    # either 'sjlj' or 'dwarf', we use 'dwarf' while requested on releasing ML
    $exceptions = 'dwarf',

    # installed revision
    $revision = 'rev3'
){
    # Match revision, match with both 'r8' and 'rev8' style patterns.
    # Just for checking if correct revision is already installed.
    $match_revision = regsubst($revision, 'rev', 'r(ev)?')

    # installer file URL
    $url = "$input/windows/i686-${version}-release-${threading}-${exceptions}-rt_v3-${revision}.7z"

    windows::zip_package { "mingw482":
        url => $url,
        version => $version,
        version_flags => "-print-search-dirs",
        version_expression => "i686-${altversion}-${threading}-${exceptions}-rt_v3-${match_revision}",
        path => $path,
        binary => "$path\\mingw32\\bin\\g++.exe"
    }
}
