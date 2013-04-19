class virtual_clone_drive {
    case $::kernel {
        windows: { include virtual_clone_drive::windows }
    }
}