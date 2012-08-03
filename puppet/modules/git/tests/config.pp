selftest::expect_no_warnings { "no warnings from git::config": }

# =============== default git::config should manage $HOME/.gitconfig ==========
#
git::config { "user.name":
    content => "Fake Git User",
    user => "fakeuser_bob",
}

$default_homedir = $::operatingsystem ? {
    windows => "(?i:c:\\\\Users\\\\fakeuser_bob\\\\)",   # case-insensitive regex
    Darwin => "/Users/fakeuser_bob/",
    default => "/home/fakeuser_bob/",
}

selftest::expect { "default .gitconfig file":
    output => "Exec\\[git::config set user\\.name in ${default_homedir}.gitconfig\\]",
}

# =============== file can be set explicitly where needed =====================
#
git::config { "user.email":
    content => "fake-git-user@example.com",
    user => "fakeuser_bob",
    file => "/some/fake/file",
}

selftest::expect { "explicit file":
    output => 'Exec\[git::config set user\.email in /some/fake/file\]',
}

