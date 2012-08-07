# Manage a git object cache.
#
# The object cache will daily be updated with the content of various git directories;
# these are garbage collected to reduce disk usage. The cache directory itself is
# garbage collected weekly.
#
define git::object_cache(
    $cache_path = $name,    # The path to the cache directory (will be created).
    $git_path,              # The path(s) or patterns matching the git repositories to
                            # have their objects cached; may be a string or an array.
                            # The path(s) will be expanded by the shell; wildcards are
                            # permitted.
    $owner,                 # Owner of the object cache (a username)
    $group                  # Owning group of the object cache
) {

    File {
        owner => $owner,
        group => $group,
    }

    Cron {
        user => $owner,
    }

    Exec {
        user => $owner,
    }

    file { $cache_path:
        ensure => directory,
    }

    exec { "git init for $cache_path":
        command => "/usr/bin/git init --bare \"$cache_path\"",
        require => File[ $cache_path ],
        creates => "$cache_path/config",
    }

    # do periodic aggressive gc rather than auto
    git::config { "git::object_cache $cache_path no auto gc":
        key => "gc.auto",
        content => "0",
        file => "$cache_path/config",
        user => $owner,
        require => Exec[ "git init for $cache_path" ],
    }

    cron { "periodic git gc on $cache_path":
        command => "( cd \"$cache_path\" && pwd && du -shx . && git gc --aggressive && du -shx . ) 2>&1 | tee \"$cache_path/gc.log\" | logger -t git::object_cache",
        # arbitrarily picked time on Sunday (when server is not too busy)
        weekday => 0,
        hour => 12,
        minute => 30,
        require => Git::Config[ "git::object_cache $cache_path no auto gc" ],
    }

    # deploy the script to link repositories to the cache
    $daily_update_cmd = "$cache_path/make-git-dirs-use-cache 2>&1 | tee \"$cache_path/daily-update.log\" | logger -t git::object_cache"

    file { "$cache_path/make-git-dirs-use-cache":
        content => template( "git/make-git-dirs-use-cache.erb" ),
        mode => 0755,
        require => Git::Config[ "git::object_cache $cache_path no auto gc" ],

        # run the update command once after script is installed (or updated)
        notify => Exec[ $daily_update_cmd ],
    }

    exec { $daily_update_cmd:
        refreshonly => true,
    }

    cron { "daily update of $cache_path":
        command => $daily_update_cmd,
        hour => 13,
        minute => 30,
        require => File[ "$cache_path/make-git-dirs-use-cache" ],
    }

}
