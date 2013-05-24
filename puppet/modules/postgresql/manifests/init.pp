class postgresql {
    case $::kernel {
        windows: { require postgresql::windows }
    }
}