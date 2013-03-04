# This cross-platform module downloads the given $version of Squish and installs it to the specified $path.
# If a different squish is already installed, it is uninstalled first.
# Also squish-package will be renamed, not_configured file will be removed and squish license is installed using secret file.

class squish(
    $version = '4.3',
    $base_url = 'http://download.froglogic.com/snapshots'
) {
    $path = $::operatingsystem ? {
        windows  =>  "c:\\utils\\squish",
        default   =>  "/opt/squish",
    }

    case $::operatingsystem {
        windows: {
            $pkg_name = $::architecture ? {
                x64 => "squish-4.3-20130123-1456-qt50x-win64-msvc10",
                default => "squish-4.3-20130114-1424-qt500-win32-msvc10"
            }
            $binary = "${path}\\squish\\bin\\squishserver.exe"
        }
        Ubuntu: {
            $pkg_name = $::architecture ? {
                i386 => "squish-4.3-20130114-1424-qt500-linux32",
                default => "squish-4.3-20130114-1424-qt500-linux64",
            }
            $binary = "${path}/squish/bin/squishserver"
        }
        Darwin: {
            $pkg_name = "squish-4.3-20130114-1508-qt500-macx86_64-gcc4.0"
            $binary = "${path}/squish/bin/squishserver"
        }
    }

    unzip_package { "squish":
        url => "$base_url/${pkg_name}.zip",
        version => $version,
        path => $path,
        binary => $binary,
    }

    # Remove not_configured file. Required for squish manual setup.
    file { "${path}/${pkg_name}/bin/.not_configured":
        ensure => absent,
        notify => Exec[ "rename $pkg_name to squish" ],
        require => Unzip_package["squish"],
    }

    # Rename squish package name as 'squish' so the path to squish won´t change after version update
    # Add squish_env.sh to set environment variables
    if $::operatingsystem == 'windows' {
        exec { "rename $pkg_name to squish":
            command => "C:\\Windows\\system32\\cmd.exe /C \"rename ${path}\\${pkg_name} squish\"",
            refreshonly => true
        }
    }
    else {
        exec { "rename $pkg_name to squish":
            command => "/bin/sh -c \"mv ${path}/${pkg_name} ${path}/squish\"",
            refreshonly => true
        }
        file { "/etc/profile.d/squish_env.sh":
            ensure  =>  present,
            content  => template("squish/squish_env.sh.erb"),
        }
    }

    # Download squish license
    secret_file { "${path}/.squish-3-license":
        source  => "squish/.squish-3-license",
        require => Unzip_package["squish"],
    }

    # Permissions to squish dir
    file { "${path}":
        ensure   => directory,
        recurse  => true,
        mode     => 755,
        require  => Unzip_package["squish"],
    }

}
