class ccache ($user = $baselayout::testuser) {
    case $::operatingsystem {
        Darwin:     { include ccache::mac }
        Solaris:    { include ccache::solaris }
        Ubuntu:     { include ccache::linux }
        Linux:      { include ccache::linux }
    }
}
