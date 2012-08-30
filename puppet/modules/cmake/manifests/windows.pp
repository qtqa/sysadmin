# Downloads the given $version of cmake from cmake.org and installs to the specified $path.
# If a different cmake version is already installed there, it is uninstalled first.
class cmake::windows(
    $version = '2.8.9',
    $path = 'C:\CMake'
) {
    # first two portions of version number (e.g. '2.8')
    $version_majmin = regsubst($version, '^(\d+)\.(\d+).*$', '\1.\2')

    # installer file URL
    $url = "http://www.cmake.org/files/v${version_majmin}/cmake-${version}-win32-x86.exe"

    $msysbin = $::architecture ? {
        x64     => 'c:\Program Files (x86)\Git\bin',
        default => 'c:\Program Files\Git\bin'
    }

    $curl = "\"$msysbin\\curl.exe\""
    $grep = "\"$msysbin\\grep.exe\""
    $cmake = "\"$path\\bin\\cmake.exe\""

    $cmd = 'C:\Windows\system32\cmd.exe'

    # directory to temporarily hold the downloaded installer
    $tempdir = $baselayout::tempdir

    # download cmake installer from above URL and install it to $path
    exec { "install cmake $version to $path":

        command => "$cmd /C \"\
$curl \"$url\" -o \"$tempdir\\cmake-installer.exe\" && \
( if exist \"$path\\uninstall.exe\" \
  start /D \"$path\" /wait uninstall.exe /S ) \
& ( if exist \"$path\" \
    rd /S /Q \"$path\" ) \
&& start \"cmake installer\" /wait \"$tempdir\\cmake-installer.exe\" /S /D=$path\
\"",

        unless => "$cmd /C \"\
$cmake --version | $grep -Fx \"cmake version $version\"\
\"",

        logoutput => true
    }
}
