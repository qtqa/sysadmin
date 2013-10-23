class openssl_android {
    case $::kernel {
        windows: { include openssl_android::windows }
    }
}
