# Download and unzip QNX 6.5.0 SDP to $target.
class qnx650::linux
{
    $url = "$input/qnx"
    $filename = "qnxsdp-6.5.0SP1-with-screen.tar.gz"
    $target = "/opt/qnx650"

    file { "$target":
        ensure  =>  directory,
        owner   =>  qt,
        group   =>  users,
        mode    =>  0755,
    }

    define qnx650_install($filename,$options,$target,$url) {
        exec { "install $filename to $target":
            command => "/bin/bash -c 'wget $url/$filename -O - | tar -C $target -$options'",
            unless  => "/bin/bash -c '/usr/bin/test -f $target/qnx650-env.sh'",
            require => File["$target"],
            timeout => 1800,
        }
    }

    qnx650_install {
        "sdp650":
            filename => "$filename",
            options  => "xz --strip-components=2",
            target   => "$target",
            url      => "$url",
         ;
    }
}
