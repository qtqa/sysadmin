class mingw48 {
    case $::kernel {
        windows: { include mingw48::windows }
    }
}
