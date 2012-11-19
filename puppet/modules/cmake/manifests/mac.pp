class cmake::mac {
    # Use cmake from macports
    require macports

    package { "cmake":
        ensure => installed,
        provider => 'macports'
    }
}
