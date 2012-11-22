class openssl {
    case $::kernel {
        windows: { include openssl::windows }
    }
}
