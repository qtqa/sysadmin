# mock git::config to avoid "Invalid user: fakeuser" from certain puppet versions
define git::config(
    $file = '<default>',
    $user = '<default>',
    $content = '<default>',
    $key = '<default>'
) {
    notice("would set git config $key = $content in $file (for $user)")
}

# Linux is only supported OS for now
if $::kernel == 'Linux' {

    git::object_cache { "/some/git/cache":
        owner => "fakeuser",
        group => "fakegroup",
        git_path => [
            "/some/git/dir1",
            "/some/git/dir2",
            "/some/git/subdirs/*/*/something",
        ],
    }

    selftest::expect_no_warnings { "no warnings from git::object_cache": }

    selftest::expect { "config and script deployed in right order":
        output => [
            'File\[/some/git/cache\]',
            'Exec\[git init for /some/git/cache\]',
            'File\[/some/git/cache/make-git-dirs-use-cache\]',
            'Exec\[/some/git/cache/make-git-dirs-use-cache.*',
        ],
    }

    # not testable in above, because the order is not guaranteed (notice() occurs at
    # parse time)
    selftest::expect { "gc.auto set as expected":
        output => 'would set git config gc\.auto = 0 in /some/git/cache/config \(for fakeuser\)'
    }

    # cron jobs are tested separately because their order can't be guaranteed
    selftest::expect { "update cron set up":
        output => 'Cron\[daily update of /some/git/cache\]',
    }

    selftest::expect { "gc cron set up":
        output => 'Cron\[periodic git gc on /some/git/cache\]',
    }
}
