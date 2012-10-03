class ruby::mac {
    # Use ruby from macports
    include macports

    package { "ruby":
        ensure => installed,
        provider => 'macports'
    }
}
