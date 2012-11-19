class ruby::mac {
    # Use ruby from macports
    require macports

    package { "ruby":
        ensure => installed,
        provider => 'macports'
    }
}
