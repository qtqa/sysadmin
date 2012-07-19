baselayout::startup { "test startup item":
    path => "/bin/true",
    user => "fakeuser",
}

if $::kernel == 'Linux' {
    file { "/home/fakeuser/.config/autostart": }
    $output = 'File\[/home/fakeuser/\.config/autostart/test startup item\.desktop\]'
}

if $::operatingsystem == 'Darwin' {
    $output = [
        'File\[/Users/fakeuser/startup-test startup item\.command\]',
        'Exec\[test startup item login item\]'
    ]
}

if $::operatingsystem == 'windows' {
    file { "c:\\qtqa\\bin\\qtqa-manage-lnk.pl": }
    $output = 'Exec\[enforce startup lnk test startup item\]'
}

selftest::expect { "startup item is created":
    output => $output
}
