class sevenzip::mac
{
    require macports

    package { "p7zip":
        ensure   => installed,
        provider => 'macports',
    }
}
