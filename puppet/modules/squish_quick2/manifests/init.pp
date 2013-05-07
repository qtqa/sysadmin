# This cross-platform module downloads the given $version of Squish and installs it to the specified $path.
# If a different squish is already installed, it is uninstalled first.
# Also squish-package will be renamed and not_configured file will be removed
class squish_quick2 {

    $path = $::operatingsystem ? {
        windows => "c:\\utils\\squish_quick2",
        default => "/opt/squish_quick2",
    }

    file { "${path}":
        ensure => directory,
    }

    # Squish have separate packages for x86 and x64 architectures in Windows 7 and Ubuntu
    $msvc10_pkg_name = $::architecture ? {
        x64     => "squish-qtquick-5.0-20130510-1225-qt50x-linux64",
        default => "squish-qtquick-5.0-20130510-1225-qt50x-win32-msvc10",
    }

    $ubuntu_pkg_name = $::architecture ? {
        i386    => "squish-qtquick-5.0-20130503-1701-qt50x-linux32",
        default => "squish-qtquick-5.0-20130503-1701-qt50x-linux64",
    }

    case $::operatingsystem {
        windows: {
            if ($kernelmajversion == "6.2") {
                squish_install {
                    "msvc11_qml2":
                        pkg_name => "squish-qtquick-5.0-20130510-1225-qt50x-win64-msvc11",
                        path     => "$path",
                    }
            }
            else {
                squish_install {
                    "mingw_qml2":
                        pkg_name => "squish-qtquick-5.0-20130510-1225-qt50x-win32-mingw",
                        path     => "$path",
                    ;
                    "msvc10_qml2":
                        pkg_name => "$msvc10_pkg_name",
                        path     => "$path",
                }
            }
        }
        Ubuntu: {
            squish_install {
                "package_qml2":
                    pkg_name => "$ubuntu_pkg_name",
                    path     => "$path",
            }
        }
    }

    define squish_install ($path,$pkg_name) {

        $binary      = "\"${path}/${name}/bin/squishserver\""
        $base_url    = 'http://download.froglogic.com/snapshots'
        $version     = '5.0'
        $unzip_flags = "x -o$path"

        unzip_package { "$name":
            url         => "${base_url}/${pkg_name}.zip",
            version     => $version,
            path        => "${path}/${name}",
            unzip_flags => $unzip_flags,
            binary      => $binary,
        }

        # Remove not_configured file. Required for squish manual setup.
        file { "${path}/${pkg_name}/bin/.not_configured":
            ensure  => absent,
            notify  => Exec[ "rename $pkg_name as $name" ],
            require => Unzip_package["$name"],
        }

        # Rename squish package name as '$name' so the path to squish won´t change after version update. In Windows nodes the package is named after
        # compiler and in other platforms it is named as 'squish' so the environment variables specified in 'squish_env.sh.erb' won´t change along
        # with platform. 'squish_env.sh' will set environment variables for all other platforms except for Winodws.
        if $::operatingsystem == 'windows' {
            exec { "rename $pkg_name as $name":
                command     => "C:\\Windows\\system32\\cmd.exe /C \"rename ${path}\\${pkg_name} $name\"",
                refreshonly => true
            }
        }
        else {
            exec { "rename $pkg_name as $name":
                command     => "/bin/sh -c \"mv ${path}/${pkg_name} ${path}/${name} && chown -R $testuser: $path\"",
                refreshonly => true,
            }
        }
    }
}
