class baselayout::windows inherits baselayout::base {
    if $baselayout::testuser {
        # clean testuser's temp periodically; if we don't, then nothing will clean
        # up temporary files/directories from crashing/hanging tests
        tidy { "C:\\Users\\$baselayout::testuser\\AppData\\Local\\Temp":
            age => "1w",
            recurse => true,
            rmdirs => true,
        }

        Git::Config {
            user => $baselayout::testuser,
        }

        git::config {
            "core.autocrlf": content => "true";
        }

        # avoid screensaver interrupting UI tests
        registry::value { "screensaver off":
            key => "HKU\\$baselayout::testuser\\Control Panel\\Desktop",
            value => "SCRNSAVE.EXE",
            ensure => absent,
        }
    }
}

