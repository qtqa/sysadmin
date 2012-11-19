class homedir_virtualenv::mac {
    require macports

    file { "/etc/profile.d/virtualenv-python.sh":
        ensure  =>  present,
        source  =>  "puppet:///modules/homedir_virtualenv/profile.d/virtualenv-python.sh",
    }

    package { "py26-virtualenv":
        ensure   => installed,
        provider => 'macports',
    }

    # Make virtualenv-2.6 the default virtualenv by symlinking virtualenv
    # to virtualenv-2.6.
    #
    # Note that macports has python_select, and the `port select' command,
    # but selecting python 2.6 (making /opt/local/bin/python run python 2.6)
    # does not appear to select virtualenv-2.6.
    file { "/opt/local/bin/virtualenv":
        ensure  =>  "/opt/local/bin/virtualenv-2.6",
        require =>  Package[ "py26-virtualenv" ],
    }
}

