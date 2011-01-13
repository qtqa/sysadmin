class cpan::linux {
    # Note: using the above method of cpanm installation goes to different
    # paths in different cases.  I'm not sure of the exact rules, these paths
    # were determined by experimentation.
    $cpanm = $operatingsystem ? {
        CentOS  =>  "/usr/bin/cpanm",
        default =>  "/usr/local/bin/cpanm",
    }

    exec { "install cpanm":
        command => "/bin/sh -c 'wget --no-check-certificate http://cpanmin.us -O - | perl - App::cpanminus'",
        creates => $cpanm,
    }
}

define cpan_package() {
    exec { "install $name from cpan":
        command =>  "$cpan::linux::cpanm $name",
        onlyif  =>  "/bin/sh -c '! perl -m$name -e1'",
        require =>  Exec["install cpanm"],
        # Can take quite a while to install a package with lots of deps...
        timeout => 3600,
    }
}
