# This cross-platform module downloads the given $version of Squish and installs it to the specified $path.

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
    $msvc12_pkg_name = $::architecture ? {
        x64     => "squish-5.1-20140320-1457-qt52x-win64-msvc12",
        default => "squish-5.1-20140320-1454-qt52x-win32-msvc12",
    }

    $msvc11_pkg_name = $::architecture ? {
        x64     => "squish-5.0.2-qt51x-win64-msvc11",
        default => "squish-5.1-20140321-1117-qt52x-win32-msvc11",
    }

    $msvc11_version = $::architecture ? {
        x64     => "5.0.2",
        default => "5.1.0",
    }

    $mingw_pkg_name = "squish-5.1-20140321-0826-qt52x-win32-mingw_gcc48_posix_dwarf"

    $msvc10_pkg_name = $::architecture ? {
        x64     => "squish-5.0.1-qt51x-win64-msvc10",
        default => "squish-5.1-20140320-1449-qt52x-win32-msvc10",
    }

    $msvc10_version = $::architecture ? {
        x64     => "5.0.1",
        default => "5.1.0",
    }

    $linux_pkg_name = $::architecture ? {
        i386    => "squish-5.1-20140320-1446-qt52x-linux32",
        default => "squish-5.1-20140320-1154-qt52x-linux64",
    }

    $darwin_pkg_name = "squish-5.1-20140320-1447-qt52x-macx86_64"

    case $::operatingsystem {
        windows: {
            if ($kernelmajversion >= "6.2") {
                squish_install {
                    "msvc11":
                        pkg_name => "$msvc11_pkg_name",
                        path     => "$path",
                        version  => "$msvc11_version",
                    ;
                    "msvc12":
                        pkg_name => "$msvc12_pkg_name",
                        path     => "$path",
                        version  => "5.1.0",
                    }
            }
            else {
                squish_install {
                    "mingw":
                        pkg_name => "$mingw_pkg_name",
                        path     => "$path",
                        version  => "5.1.0",
                    ;
                    "msvc10":
                        pkg_name => "$msvc10_pkg_name",
                        path     => "$path",
                        version  => "$msvc10_version",
                    ;
                    "msvc11":
                        pkg_name => "$msvc11_pkg_name",
                        path     => "$path",
                        version  => "$msvc11_version",
                    ;
                    "msvc12":
                        pkg_name => "$msvc12_pkg_name",
                        path     => "$path",
                        version  => "5.1.0",
                    }
                }
        }
        Ubuntu: {
            squish_install {
                "package":
                    pkg_name => "$linux_pkg_name",
                    path     => "$path",
                    version  => "5.1.0",
            }
        }
        OpenSuSE: {
            squish_install {
                "package":
                    pkg_name => "$linux_pkg_name",
                    path     => "$path",
                    version  => "5.1.0",
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
                    version  => "5.1.0",
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

        # Rename squish package name as '$name' so the path to squish won't change after version update.
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
