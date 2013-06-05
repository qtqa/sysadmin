class cpan::windows {
    # Note: using the above method of cpanm installation goes to different
    # paths in different cases.  I'm not sure of the exact rules, these paths
    # were determined by experimentation.
    $cpanm = $::operatingsystem ? {
        default =>  "c:\\utils\\strawberryperl_portable\\perl\\bin\\cpanm.bat",
    }

    cpan_package {['Mail::Sender', 'Win32::Shortcut'] :}
}

define cpan_package {

    $cmd = 'c:\Windows\system32\cmd.exe /c'

    exec { "install $name from cpan":
        command =>  "$cmd $cpan::windows::cpanm -f $name",
        unless  =>  "$cmd perl -m$name -e1",
        # Can take quite a while to install a package with lots of deps...
        timeout => 3600,
    }
}
