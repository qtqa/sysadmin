# Downloads the given $version of strawberryperl from $input
# Extracts it to c:\utils\strawberryperl_portable


# Major caveat: Changing $version alone does not work because uninstalling $path when it is used fails
class strawberryperl_portable::windows(
    $version = '5.16.2.2',
    $path = 'C:\utils\strawberryperl_portable'
) {

    $url = "http://strawberryperl.com/download/$version/strawberry-perl-${version}-32bit-portable.zip"

    # perl versions without build part
    $version_no_buildpart = regsubst($version, '^(\d+)\.(\d+)\.(\d+).(\d+)$', '\1.\2.\3')

    unzip_package { "strawberryperl_portable":
        url => $url,
        path => $path,
        version => $version,
        version_expression => $version_no_buildpart,
        version_flags => '--version',
        binary => "$path\\perl\\bin\\perl.exe",
    }

    $envsetcmd = "set PERL5LIB=&&set PERL_LOCAL_LIB_ROOT=&&\
                  set PERL_MB_OPT=&&set PERL_MM_OPT=&&\
                  set path=c:\\utils\\strawberryperl_portable\\c\\bin;\
                  c:\\utils\\strawberryperl_portable\\perl\\site\\bin;\
                  c:\\utils\\strawberryperl_portable\\perl\\bin;%PATH%"

    $cmd = 'c:\Windows\system32\cmd.exe /c'
    $cpanbin = 'c:\utils\strawberryperl_portable\perl\bin\cpan.bat'

    exec { "CPAN install Win32::Shortcut":
        command => "$cmd $envsetcmd&&$cpanbin Win32::Shortcut",
        refreshonly => true,
        subscribe => Unzip_package["strawberryperl_portable"],
        }
}
