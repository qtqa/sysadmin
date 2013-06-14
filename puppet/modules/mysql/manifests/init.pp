class mysql {
    case $::kernel {
        windows: { require mysql::windows }
    }
}