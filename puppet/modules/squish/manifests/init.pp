# This cross-platform module downloads the given $version of Squish and installs it to the specified $path.
# If a different squish is already installed, it is uninstalled first.
# Also squish-package will be renamed, not_configured file will be removed and squish license is installed using secret file.

class squish {

    $path = $::operatingsystem ? {
        windows => "c:\\utils\\squish",
        default => "/opt/squish",
    }

    file { "${path}":
        ensure => directory,
    }

    # Download squish license
    secret_file { "${path}/.squish-3-license":
        source    => "squish/.squish-3-license",
        subscribe => File["${path}"],
    }

    # Squish have separate packages for x86 and x64 architectures in Windows 7, Windows 8 and Ubuntu
    $msvc11_pkg_name = $::architecture ? {
        x64     => "squish-5.0.0-qt50x-win64-msvc11",
        default => "squish-5.0.0-qt50x-win32-msvc11",
    }

    $mingw_pkg_name = "squish-5.0.0-qt50x-win32-mingw"

    $msvc10_pkg_name = $::architecture ? {
        x64     => "squish-5.0-20130612-1216-qt50x-win64-msvc10",
        default => "squish-5.0-20130612-1026-qt50x-win32-msvc10",
    }

    $ubuntu_pkg_name = $::architecture ? {
        i386    => "squish-5.0.1-qt51x-linux32",
        default => "squish-5.0.1-qt51x-linux64",
    }

    $darwin_pkg_name = "squish-5.0.0-qt50x-macx86_64"

    case $::operatingsystem {
        windows: {
            if ($kernelmajversion == "6.2") {
                squish_install {
                    "msvc11":
                        pkg_name => "$msvc11_pkg_name",
                        path     => "$path",
                        version  =>"5.0.0",
                    }
            }
            else {
                squish_install {
                    "mingw":
                        pkg_name => "$mingw_pkg_name",
                        path     => "$path",
                        version  =>"5.0.0",
                    ;
                    "msvc10":
                        pkg_name => "$msvc10_pkg_name",
                        path     => "$path",
                        version  =>"5.0.0",
                }
            }
        }
        Ubuntu: {
            squish_install {
                "package":
                    pkg_name => "$ubuntu_pkg_name",
                    path     => "$path",
                    version  =>"5.0.1",
            }
        }
        Darwin: {
            exec { "Enable Accessibility API":
                command => "/bin/sh -c \"touch /private/var/db/.AccessibilityAPIEnabled\"",
            }

            squish_install {
                "package":
                    pkg_name => "$darwin_pkg_name",
                    path     => "$path",
                    version  =>"5.0.0",
            }
        }
    }

    define squish_install ($path,$pkg_name,$version) {

        $binary      = "\"${path}/${name}/bin/squishserver\""
        $unzip_flags = "x -o$path"
        $base_url    = "$input/squish"

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
            file { "/etc/profile.d/squish_env.sh":
                ensure   =>  present,
                content  => template("squish/squish_env.sh.erb"),
            }
        }
    }
}
