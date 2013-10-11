class homedir_virtualenv::linux {
    file { "/etc/profile.d/virtualenv-python.sh":
        ensure  =>  present,
        source  =>  "puppet:///modules/homedir_virtualenv/profile.d/virtualenv-python.sh",
    }

    package { "python-virtualenv":
        ensure  =>  installed,
    }
}

