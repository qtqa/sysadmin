class cpan::ubuntu inherits cpan::linux {
    # Need gcc to build CPAN modules
    package {
        "gcc":      ensure  =>  installed;
        "g++":      ensure  =>  installed;
    }
}

