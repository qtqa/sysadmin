define git::config($key = '', $ensure = present, $content = '', $user, $file = '') {

    # use $name as the key by default, but allow overriding.
    # note: puppet 2.7 allows '$key = $name' in the git::config parameter
    # list, but earlier versions don't, so we have this inelegant workaround
    $git_key = $key ? {
        '' => $name,
        default => $key
    }

    $git = $::operatingsystem ? {
        # on mac, git may be at /opt/local/bin or at /usr/bin
        Darwin => "/usr/bin/env PATH=/opt/local/bin:/usr/bin git",
        windows => $::architecture ? {
            x64 => "\"C:\\Program Files (x86)\\Git\\cmd\\git.cmd\"",
            default => "\"C:\\Program Files\\Git\\cmd\\git.cmd\"",
        },
        default => "/usr/bin/git",
    }

    # Use $file as the gitconfig file if set
    $gitconfig = $file ? {
        '' => $::operatingsystem ? {
            Darwin => "/Users/$user/.gitconfig",
            windows => "C:\\Users\\$user\\.gitconfig",
            default => "/home/$user/.gitconfig",
        },
        default => $file
    }

    if $::operatingsystem != 'windows' {
        # on Windows, we can't actually run the command as the user,
        # puppet doesn't support it. It doesn't matter since we explicitly
        # set the path to the gitconfig file above.
        Exec { user => $user }
    }

    if $ensure == absent {
        exec { "git::config unset $name in $gitconfig":
            command => "$git config --file $gitconfig --unset \"$git_key\"",
            onlyif => "$git config --file $gitconfig \"$git_key\"",
            logoutput => true,
        }
    }

    if $ensure == present {
        exec { "git::config set $name in $gitconfig":
            command => "$git config --file $gitconfig \"$git_key\" \"$content\"",
            unless => "$git config --file $gitconfig --get \"$git_key\" \"$content\"",
            logoutput => true,
        }
    }
}
