class distccd::mac
{
    service { "com.apple.distccd":
        ensure  =>  running,
        enable  =>  true,
    }
}

