# Download and install QNX SDP.
class qnx650::windows
{
    $filename = "qnx650SP1_patched.7z"
    $url = "$input/qnx/$filename"
    $target = "c:\\qnx650"


    $msysbin = $::architecture ? {
        x64     => 'c:\Program Files (x86)\Git\bin',
        default => 'c:\Program Files\Git\bin'
    }

    # directory to temporarily hold the downloaded installer
    $tempdir = $baselayout::tempdir

    $sdp = "${tempdir}/$filename"

    $fetch_cmd = "\"${msysbin}\\curl.exe\" \"$url\" -L -o \"${sdp}\""

    # Install command install's java using install package from $tempdir
    $install_cmd = "start \"install\" /wait C:\\utils\\sevenzip\\7z.exe -y x \"$sdp\" -o\"c:\\\""

    exec { "unzip_patch_650":
        command => "C:\\Windows\\system32\\cmd.exe /V:ON /C \"$fetch_cmd && $install_cmd\"",
        creates => "$target",
        timeout => 1800,
    }
}

