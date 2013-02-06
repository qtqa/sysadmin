class sevenzip {
    case $::kernel {
        windows: { include sevenzip::windows }
        Darwin:  { include sevenzip::mac }
    }
}
