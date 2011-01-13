class cpan::centos inherits cpan::linux {
    # Need gcc to build CPAN modules
    package {
        "gcc":      ensure  =>  installed;
        "gcc-c++":  ensure  =>  installed;
    }
}

