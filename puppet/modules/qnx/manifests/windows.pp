# Download and install QNX SDP.
class qnx::windows
{
    $filename = "qnx-sdp-6.6-201402230339.exe"
    $filename2 = "resp.conf"
    $url = "$input/qnx/$filename"
    $url2 = "$input/qnx/$filename2"
    $target = "c:\\qnx660"

    $filename3 = "qnx-sdk-for-apps-and-media-1.0-201402230339.exe"
    $filename4 = "resp_sdk.conf"
    $url3 = "$input/qnx/$filename3"
    $url4 = "$input/qnx/$filename4"
    $target2 = "c:\\qnx660\\install\\qnx-sdk"

    $filename5 = "patch_660-3865.7z"
    $url5 = "$input/qnx/$filename5"
    $target3 = "c:\\qnx660"

    $msysbin = $::architecture ? {
        x64     => 'c:\Program Files (x86)\Git\bin',
        default => 'c:\Program Files\Git\bin'
    }

    # directory to temporarily hold the downloaded installer
    $tempdir = $baselayout::tempdir

    $install_package = "${tempdir}/$filename"
    $resp_file = "${tempdir}/$filename2"
    $sdk_file = "${tempdir}/$filename3"
    $sdk_resp_file = "${tempdir}/$filename4"
    $patch_file = "${tempdir}/$filename5"

    $fetch_cmd = "\"${msysbin}\\curl.exe\" \"$url\" -L -o \"${install_package}\""
    $fetch_cmd2 = "\"${msysbin}\\curl.exe\" \"$url2\" -L -o \"${resp_file}\""
    $fetch_cmd3 = "\"${msysbin}\\curl.exe\" \"$url3\" -L -o \"${sdk_file}\""
    $fetch_cmd4 = "\"${msysbin}\\curl.exe\" \"$url4\" -L -o \"${sdk_resp_file}\""
    $fetch_cmd5 = "\"${msysbin}\\curl.exe\" \"$url5\" -L -o \"${patch_file}\""

    # Install command install's java using install package from $tempdir
    $install_cmd = "start \"install\" /wait \"$install_package\" -i silent -f \"$resp_file\""
    $install_cmd2 = "start \"install\" /wait \"$sdk_file\" -i silent -f \"$sdk_resp_file\""
    $install_cmd3 = "start \"install\" /wait C:\\utils\\sevenzip\\7z.exe -y x \"$patch_file\" -o\"$target3\""

    define qnx_install($filename,$target,$fetch_cmd,$fetch_cmd2,$install_cmd) {
        exec { "install $filename to $target":
            command => "C:\\Windows\\system32\\cmd.exe /V:ON /C \"$fetch_cmd && $fetch_cmd2 && $install_cmd \"",
            creates  => "$target",
            timeout => 1800,
        }
    }

    qnx_install {
        "sdp":
            filename => "$filename",
            target   => "$target",
            fetch_cmd  => "$fetch_cmd",
            fetch_cmd2 => "$fetch_cmd2",
            install_cmd => "$install_cmd",
            before => Qnx_install["sdk"],
        ;
    }

    qnx_install {
        "sdk":
            filename => "$filename2",
            target   => "$target2",
            fetch_cmd  => "$fetch_cmd3",
            fetch_cmd2 => "$fetch_cmd4",
            install_cmd => "$install_cmd2",
            before => Exec["unzip_patch"],
        ;
    }
    exec { "unzip_patch":
        command => "C:\\Windows\\system32\\cmd.exe /V:ON /C \"$fetch_cmd5 && $install_cmd3\"",
        creates => "$target\\target\\qnx6\\x86\\usr\\lib\\libfontconfig.so",
        timeout => 1800,
    }
}

