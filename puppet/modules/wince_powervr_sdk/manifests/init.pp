class wince_powervr_sdk {
    case $::kernel {
        windows: { include wince_powervr_sdk::windows }
    }
}
