# Downloads the 4.9.1 version of mingw from $input and installs to the specified $path.

class mingw491::windows(
    $version = '4.9.1',
    $altversion = '491',
    $path = 'C:\mingw491',

    # Additional options for installed MinGW version, See also: http://qt-project.org/wiki/MinGW-64-bit
    # Note: Changing any of these won't trigger MinGW re-installation.
    #
    # either 'posix' or 'win32', we use 'posix' since it is more popular
    $threading = 'posix',

    # installed revision
    $revision = 'rev1'
){
    # Match revision, match with both 'r8' and 'rev8' style patterns.
    # Just for checking if correct revision is already installed.
    $match_revision = regsubst($revision, 'rev', 'r(ev)?')

    $os_bit = $::architecture ? {
        x64     => "x86_64",
        default => "i686"
    }

    # 'seh' for x64 and 'dwarf' for x86
        $exceptions  = $::architecture ? {
        x64     => "seh",
        default => "dwarf"
    }

    # installer file URL
    $url = "${input}/windows/${os_bit}-${version}-release-${threading}-${exceptions}-rt_v3-${revision}.7z"

    $binary = $::architecture ? {
        x64     => "$path\\mingw64\\bin\\g++.exe",
        default => "$path\\mingw32\\bin\\g++.exe"
    }

    windows::zip_package { "mingw491":
        url => $url,
        version => $version,
        version_flags => "-print-search-dirs",
        version_expression => "${os_bit}-${altversion}-${threading}-${exceptions}-rt_v3-${match_revision}",
        path => $path,
        binary => "$binary"
    }
}
