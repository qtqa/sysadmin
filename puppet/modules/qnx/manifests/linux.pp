# Download and unzip QNX SDP to $target. Update will be done and
# old version will be removed if $filename is changed to point to new or older version.
class qnx::linux
{
    $url = "$input/qnx"
    $filename = "qnx660.tar.gz"
    $target = "/opt/qnx660"

    file { "/opt":
        ensure  =>  directory,
        owner   =>  root,
        group   =>  users,
        mode    =>  0755,
    }

    file { "$target":
        ensure  =>  directory,
        owner   =>  qt,
        group   =>  users,
        mode    =>  0755,
    }

    define qnx_install($filename,$options,$target,$url) {
        exec { "install $filename to $target":
            command => "/bin/bash -c 'wget $url/$filename -O - | tar -C $target -$options'",
            unless  => "/bin/bash -c '/usr/bin/test -d $target'",
            require => File["/opt","$target"],
            timeout => 1800,
        }
    }

    if $::lsbmajdistrelease == 12 {
        if $::architecture == amd64 {
            qnx_install {
                "sdp":
                    filename => "$filename",
                    options  => "xz --strip-components=1",
                    target   => "$target",
                    url      => "$url",
                 ;
            }
        }
    }
}
