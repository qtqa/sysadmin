class cpan::mac {

    $user = $::id

    if 'root' == $user {
        $home = "/var/root"
    } else {
        $home = "/Users/$user"
    }

    $cpanm = "/usr/local/bin/cpanm"


    exec { "install cpanm":
        command => "/usr/bin/su - $user -c 'curl -L http://cpanmin.us | perl - App::cpanminus' >~/cpanmlog 2>&1",
        creates => $cpanm,
        logoutput => true,
    }

    cpan_package {['Mail::Sender', 'YAML'] :}
}

define cpan_package() {
    exec { "install $name from cpan":
        command =>  "/usr/bin/su - $user -c '$cpan::mac::cpanm -l $home/perl5 $name'",
        onlyif  =>  "/usr/bin/su - $user -c '! perl -I$home/perl5/lib/perl5 -m$name -e1'",
        require =>  Exec["install cpanm"],
        # Can take quite a while to install a package with lots of deps...
        timeout => 3600,
    }
}
