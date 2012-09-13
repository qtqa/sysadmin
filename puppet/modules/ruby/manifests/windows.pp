class ruby::windows(
    $url = "http://rubyforge.org/frs/download.php/76277/rubyinstaller-1.8.7-p370.exe/noredirect",
    $version = '1.8.7',
    $path = 'C:\ruby'
) {
    windows::exe_package { "ruby":
        url => $url,
        version => $version,
        path => $path,
        type => 'inno',
        binary => "$path\\bin\\ruby.exe"
    }
}
