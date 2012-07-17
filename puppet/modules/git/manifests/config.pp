define git::config($key = '', $ensure = present, $content = '', $user) {

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
        default => "/usr/bin/git",
    }

    $gitconfig = $::operatingsystem ? {
        Darwin => "/Users/$user/.gitconfig",
        default => "/home/$user/.gitconfig",
    }

    if $ensure == absent {
        exec { "git::config unset $name":
            command => "$git config --file $gitconfig --unset \"$git_key\"",
            onlyif => "$git config --file $gitconfig \"$git_key\"",
            user => $user,
            logoutput => true,
        }
    }

    if $ensure == present {
        exec { "git::config set $name":
            command => "$git config --file $gitconfig \"$git_key\" \"$content\"",
            unless => "$git config --file $gitconfig --get \"$git_key\" \"$content\"",
            user => $user,
            logoutput => true,
        }
    }
}
