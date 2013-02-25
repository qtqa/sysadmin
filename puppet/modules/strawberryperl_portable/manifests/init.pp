class strawberryperl_portable {
    case $::kernel {
        windows: { include strawberryperl_portable::windows }
    }
}
