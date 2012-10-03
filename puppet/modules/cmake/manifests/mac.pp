class cmake::mac {
    # Use cmake from macports
    include macports

    package { "cmake":
        ensure => installed,
        provider => 'macports'
    }
}
