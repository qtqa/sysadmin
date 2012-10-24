class sevenzip {
    case $::kernel {
        windows: { include sevenzip::windows }
    }
}
