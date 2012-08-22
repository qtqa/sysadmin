class cmake {
    case $::kernel {
        Linux:   { include cmake::linux }
        Darwin:  { include cmake::mac }
        windows: { include cmake::windows }
    }
}
