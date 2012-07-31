class baselayout::windows inherits baselayout::base {
    if $baselayout::testuser {
        Git::Config {
            user => $baselayout::testuser,
        }

        git::config {
            "core.autocrlf": content => "true";
        }
    }
}

