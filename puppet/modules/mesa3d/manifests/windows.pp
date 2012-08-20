class mesa3d::windows {
    # FIXME: this module is incomplete for 64-bit Windows.
    # It only installs a 32-bit version of the mesa DLL.

    $tempdir = "c:\\temp"
    $gitdir = $::architecture ? {
        x64 => "C:\\Program Files (x86)\\Git",
        default => "C:\\Program Files\\Git"
    }
    $opengl_library = "opengl32.dll"
    $windows_system32 = "c:\\Windows\\system32"
    $windows_dlldir32 = $::architecture ? {
        x64 => "C:\\Windows\\SysWOW64",
        default => $windows_system32
    }
    $cmd = "$windows_system32\\cmd.exe"
    $mesa3ddll = "mesa3d_opengl.dll"

    file { $tempdir:
        ensure => directory,
    }

    exec { prepare_for_opengl32_replace:
        command  =>  "$cmd /C \
            \
            takeown /f $windows_dlldir32\\$opengl_library & \
            icacls $windows_dlldir32\\$opengl_library /grant Administrators:F & \
            \"$gitdir\\bin\\curl.exe\" -o $tempdir\\$mesa3ddll $input/mesa3d/windows/$opengl_library\
            \
        ",
        creates => "$tempdir\\$mesa3ddll",
        require => File[$tempdir],
        logoutput => true,
    }

    file { "$windows_dlldir32\\opengl32.dll":
        ensure => present,
        backup => main,
        owner => Administrators,
        mode => 0775,
        replace => true,
        source => "$tempdir\\$mesa3ddll",
        require => Exec["prepare_for_opengl32_replace"],
    }
}