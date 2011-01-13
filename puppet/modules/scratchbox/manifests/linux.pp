class scratchbox::linux {
    if $testuser {
        exec { "add sbox user":
            command => "/scratchbox/sbin/sbox_adduser $testuser yes",
            creates => "/scratchbox/users/$testuser",
        }
    }

    # sbox_sync keeps some files (e.g. resolv.conf) in sync
    # inside and outside of scratchbox
    cron { "sbox_sync":
        command => "/scratchbox/sbin/sbox_sync",
        user    => "root",
        minute  => [ "*/5" ],
    }
}

