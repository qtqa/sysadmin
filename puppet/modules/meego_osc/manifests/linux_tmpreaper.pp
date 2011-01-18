class meego_osc::linux::tmpreaper {

    # clean up /var/tmp/osbuild-packagecache sometimes.
    package { "tmpreaper":
        ensure  =>  present,
    }

    file {
        "/etc/cron.daily/osbuild-packagecache-cleanup":
            source  =>  "puppet:///modules/meego_osc/osbuild-packagecache-cleanup",
            require =>  Package["tmpreaper"],
            mode    =>  0755,
        ;
    }

}

