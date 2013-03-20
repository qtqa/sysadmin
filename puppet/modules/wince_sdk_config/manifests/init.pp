class wince_sdk_config {
    case $::kernel {
        windows: { include wince_sdk_config::windows }
    }
}
