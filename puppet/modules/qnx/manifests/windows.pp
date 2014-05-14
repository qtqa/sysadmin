# Download and install QNX SDP.
class qnx::windows
{
    $filename = "qnx-sdp-6.6-201402230339.exe"
    $filename2 = "resp.conf"
    $url = "$input/qnx/$filename"
    $url2 = "$input/qnx/$filename2"
    $target = "c:\\qnx660"

    $msysbin = $::architecture ? {
        x64     => 'c:\Program Files (x86)\Git\bin',
        default => 'c:\Program Files\Git\bin'
    }

    # directory to temporarily hold the downloaded installer
    $tempdir = $baselayout::tempdir

    $install_package = "${tempdir}/$filename"
    $resp_file = "${tempdir}/$filename2"

    $fetch_cmd = "\"${msysbin}\\curl.exe\" \"$url\" -L -o \"${install_package}\""
    $fetch_cmd2 = "\"${msysbin}\\curl.exe\" \"$url2\" -L -o \"${resp_file}\""

    # Install command install's java using install package from $tempdir
    $install_cmd = "start \"install\" /wait \"$install_package\" -i silent -f \"$resp_file\""

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
         ;
    }
}
