baselayout::startup { "test startup item":
    path => "/bin/true",
    user => "fakeuser",
}

baselayout::startup { "item with args":
    path => "/bin/sh",
    arguments => ["-c", "echo do this; echo do that"],
    user => "fakeuser2",
}

if $::kernel == 'Linux' {
    file { "/home/fakeuser/.config/autostart": }
    file { "/home/fakeuser2/.config/autostart": }
    $item1_output = 'File\[/home/fakeuser/\.config/autostart/test startup item\.desktop\]'
    $item2_output = 'File\[/home/fakeuser2/\.config/autostart/item with args\.desktop\]'
}

if $::operatingsystem == 'Darwin' {
    $item1_output = [
        'File\[/Users/fakeuser/startup-test startup item\.command\]',
        'Exec\[test startup item login item\]'
    ]
    $item2_output = [
        'File\[/Users/fakeuser2/startup-item with args\.command\]',
        'Exec\[item with args login item\]'
    ]
}

if $::operatingsystem == 'windows' {
    file { "c:\\qtqa\\bin\\qtqa-manage-lnk.pl": }
    $item1_output = 'Exec\[enforce startup lnk test startup item\]'
    $item2_output = 'Exec\[enforce startup lnk item with args\]'
}

selftest::expect { "startup item is created":
    output => $item1_output
}

selftest::expect { "item with args is created":
    output => $item2_output
}
