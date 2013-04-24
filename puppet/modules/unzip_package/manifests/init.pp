# This cross-platform module install (or uninstall, reinstall) given zip package.
#
# Major caveat: $path must be unique for installed package, because uninstallation is simply done as 'rd /S /Q $path' in windows
# or as 'rm -fr $path' in other platforms.
# For example: Even your archive contains folder 'perl', you still should install to $path such as "c:\perl".
define unzip_package(

    # URL of the .zip archive to be downloaded and installed;
    # leave undefined if $zip_archive should not be downloaded
    $url = undef,

    # Binary expected to be installed (e.g. c:\perl\bin\perl.exe)
    $binary,

    # Desired installation directory (e.g. c:\perl)
    $path,

    # Desired version (e.g. 5.14.2)
    $version,

    # Flags passed to $binary to make it output its version number
    $version_flags = '--version',

    # Regular expression matching the desired output when running
    # $binary with $version_flags; leave undefined for a reasonable
    # default.
    # Example: "\\b5\\.14\\.2\\b"
    $version_expression = undef,

    # Flags used for unattended extracting of zip archive with 7-zip;
    # Defaults:
    #   x           Extract files with full paths
    #   -o$path     Set output directory to $path
    $unzip_flags = "x -o$path",

    # Path to zip archive; leave undefined if $url is passed, in which
    # case the zip is downloaded
    $zip_archive = undef
) {
    # Sevenzip is used to unzip zip-files. It is required before executing install command
    include sevenzip

    # directory to temporarily hold the downloaded archive
    $tempdir = $baselayout::tempdir

    if $::operatingsystem == 'windows' {
        $msysbin = $::architecture ? {
            x64     => 'c:\Program Files (x86)\Git\bin',
            default => 'c:\Program Files\Git\bin'
        }
        $curl = "\"$msysbin\\curl.exe\""
        $grep = "\"$msysbin\\grep.exe\""
    } else {
        $curl = "curl"
        $grep = "grep"
    }

    if $url {
        $safename = regsubst($name, "[^a-zA-Z0-9]", "-", "G")
        $real_zip_archive = "$tempdir/$safename-archive.zip"
        $fetch_cmd = "$curl $url -L -o $real_zip_archive"
    } else {
        $real_zip_archive = $zip_archive
        $fetch_cmd = ""
    }

    $real_version_expression = $version_expression ? {
            undef => "\\b$version\\b",
            default => $version_expression
    }

    if $::operatingsystem == 'windows' {
        $cmd = 'C:\Windows\system32\cmd.exe /C'
        $remove = "(if exist \"$path\" rd /S /Q \"$path\")"
        $unzip = "start \"install\" /wait C:\\utils\\sevenzip\\7z.exe $unzip_flags \"$real_zip_archive\""
        $unless_cmd = "$cmd \"$binary $version_flags | $grep -E \"$real_version_expression\"\""
    }
    else {
        $cmd = "/bin/sh -c"
        $remove = "(if [ -e $path ]; then rm -fr $path; fi)"
        $unzip = "7z $unzip_flags $real_zip_archive"
        $unless_cmd = "$cmd '$binary $version_flags | $grep -E \"$real_version_expression\"'"
    }

    exec { "install $name $version to $path":
        command => "$cmd \"$fetch_cmd && $remove && $unzip\"",
        unless => "$unless_cmd",
        logoutput => true,
        timeout => 3600,
        require => Class['sevenzip']
        }
}
