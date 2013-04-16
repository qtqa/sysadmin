# Downloads the 4.8.0 version of mingw from sourceforge.net and installs to the specified $path.

class mingw48::windows(
    $version = '4.8.0',
    $path = 'C:\mingw48',

    # Additional options for installed MinGW version, See also: http://qt-project.org/wiki/MinGW-64-bit
    # Note: Changing any of these won't trigger MinGW re-installation.
    #
    # either 'posix' or 'win32', we use 'posix' since it is more popular
    $threading = 'posix',

    # either 'sjlj' or 'dwarf', we use 'dwarf' while requested on releasing ML
    $exceptions = 'dwarf',

    # installed revision
    $revision = 'rev1'
){
    # Match revision, match with both 'r8' and 'rev8' style patterns.
    # Just for checking if correct revision is already installed.
    $match_revision = regsubst($revision, 'rev', 'r(ev)?')

    # installer file URL
    $url = "http://sourceforge.net/projects/mingwbuilds/files/host-windows/releases/${version}/32-bit/threads-${threading}/${exceptions}/x32-${version}-release-${threading}-${exceptions}-${revision}.7z"

    windows::zip_package { "mingw48":
        url => $url,
        version => $version,
        version_flags => "-print-search-dirs",
        version_expression => "x32-${version}(-release)?-${threading}-${exceptions}-${match_revision}",
        path => $path,
        binary => "$path\\mingw\\bin\\g++.exe"
    }
}
