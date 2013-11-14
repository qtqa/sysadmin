class baselayout::base {
    if $baselayout::testuser {
        Git::Config {
            user => $baselayout::testuser,
        }

        git::config {
            "url.$qtgitreadonly.insteadof": content => "qtgitreadonly:";
            "qtqa.hardgit.location": content => $location;
            "qtqa.hardgit.server.qtgitreadonly.primary": content => $qtgitreadonly;
            "qtqa.hardgit.server.qtgitreadonly.mirror-$location": content => $qtgitreadonly_local;
            "user.name": content => "Qt Continuous Integration System";
            "user.email": content => "ci-noreply@qt-project.org";
            "http.postBuffer": content => "524288000";
        }

        if $baselayout::qt_gerrit_mirror {
            git::config { "url.$baselayout::qt_gerrit_mirror.insteadof": content => "qt-gerrit-mirror:" }
        }
    }
}
