class smallfs::linux {

    $qtqadir = "/var/qtqa"
    $imgfile = "$qtqadir/smallfs-ext2-2MB.img"
    $mountpoint = "/smallfs-ext2-2MB"

    # Create the $imgfile with an ext2 filesystem.
    exec { "$imgfile":
        cwd => $qtqadir,
        creates => $imgfile,
        path => [ "/bin", "/usr/bin", "/sbin" ],
        provider => shell,
        command => "

    rm -f $imgfile.tmp &&
    dd if=/dev/zero of=$imgfile.tmp bs=1MB count=2 &&
    yes | mkfs -t ext2 $imgfile.tmp &&
    mv -v $imgfile.tmp $imgfile

        ",
        logoutput => true,
    }

    # We need to ensure that the mount point exists before attempting to mount...
    file { $mountpoint:
        ensure => directory,
    }

    # ... and, _after_ mounting, we need to make it world-writable.
    file { "mounted $mountpoint":
        path => $mountpoint,
        mode => 0777,
        require => Mount[ $mountpoint ],
    }

    # Mount $imgfile as a loopback device on the $mountpoint.
    mount { $mountpoint:
        atboot => true,
        device => $imgfile,
        ensure => mounted,
        fstype => "ext2",
        options => "loop",
        require => [ File[ $mountpoint ], Exec[ $imgfile ] ],
    }

    # Put QT_TEST_SMALL_FS into the test environment.
    file { "/etc/profile.d/smallfs-qtqa.sh":
        ensure => present,
        content => template( "smallfs/smallfs-qtqa.sh.erb" ),
        require => File[ "mounted $mountpoint" ],
    }

}
