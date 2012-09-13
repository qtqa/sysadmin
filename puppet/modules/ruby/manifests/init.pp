class ruby {
    case $::kernel {
        Linux:   { include ruby::linux }
        Darwin:  { include ruby::mac }
        windows: { include ruby::windows }
    }
}
