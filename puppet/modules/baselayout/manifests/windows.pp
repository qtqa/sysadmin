class baselayout::windows inherits baselayout::base {
    if $testuser {
        Git::Config {
            user => $testuser,
        }

        git::config {
            "core.autocrlf": content => "true";
        }
    }
}

