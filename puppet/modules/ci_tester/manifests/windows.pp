class ci_tester::windows inherits ci_tester::base {
    include mesa3d
    include activepython
    include mingw
    include mingw48
    include openssl
    include strawberryperl
    include strawberryperl_portable
    include jom
    include virtual_clone_drive
    include postgresql
    include mysql

    if ($kernelmajversion >= "6.1") {
        # WinCE build is performed only on Windows 7 with MSVC2008
        include wince_powervr_sdk
        include wince_sdk_config
    }
}
