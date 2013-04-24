class sevenzip {
    case $::kernel {
        windows: { require sevenzip::windows }
        Darwin:  { require sevenzip::mac }
        Linux:   { require sevenzip::linux }
    }
}