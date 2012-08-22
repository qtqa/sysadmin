class cmake::linux {
    # use cmake from package manager
    package { "cmake": ensure => installed; }
}
