# Downloads the given $version of cmake from cmake.org and installs to the specified $path.
# If a different cmake version is already installed there, it is uninstalled first.
class cmake::windows(
    $version = '2.8.11',
    $path = 'C:\CMake'
) {
    # first two portions of version number (e.g. '2.8')
    $version_majmin = regsubst($version, '^(\d+)\.(\d+).*$', '\1.\2')

    # installer file URL
    $url = "http://www.cmake.org/files/v${version_majmin}/cmake-${version}-win32-x86.exe"

    windows::exe_package { "cmake":
        url => $url,
        version => $version,
        path => $path,
        type => 'nsis',
        binary => "$path\\bin\\cmake.exe"
    }
}
