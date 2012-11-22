class mingw {
    case $::kernel {
        windows: { include mingw::windows }
    }
}
