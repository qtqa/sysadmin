class baselayout::base {
    if $testuser {
        Git::Config {
            user => $testuser,
        }

        git::config {
            "url.$qtgitreadonly.insteadof": content => "qtgitreadonly:";
            "qtqa.hardgit.location": content => $location;
            "qtqa.hardgit.server.qtgitreadonly.primary": content => $qtgitreadonly;
            "qtqa.hardgit.server.qtgitreadonly.mirror-$location": content => $qtgitreadonly_local;
            "user.name": content => "Qt Continuous Integration System";
            "user.email": content => "qt-info@nokia.com";
        }
    }
}
