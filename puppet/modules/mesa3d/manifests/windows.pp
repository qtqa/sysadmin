class mesa3d::windows {

    $tempdir = "c:\\temp"
    $opengl_library = "opengl32.dll"
    $windows_system32 = "c:\\Windows\\system32"
    $cmd = "$windows_system32\\cmd.exe"
    $mesa3ddll = "mesa3d_opengl.dll"

    file { $tempdir:
        ensure => directory,
    }

    exec { prepare_for_opengl32_replace:
        command  =>  "$cmd /C \
            \
            takeown /f $windows_system32\\$opengl_library & \
            icacls $windows_system32\\$opengl_library /grant Administrators:F & \
            \"C:\\Program Files\\Git\\bin\\curl.exe\" -o $tempdir\\$mesa3ddll $input/mesa3d/windows/$opengl_library\
            \
        ",
        creates => "$tempdir\\$mesa3ddll",
        require => File[$tempdir],
        logoutput => true,
    }

    file { "$windows_system32\\opengl32.dll":
        ensure => present,
        backup => main,
        owner => Administrators,
        mode => 0775,
        replace => true,
        source => "$tempdir\\$mesa3ddll",
        require => Exec["prepare_for_opengl32_replace"],
    }
}