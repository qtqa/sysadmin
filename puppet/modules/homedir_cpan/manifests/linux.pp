class homedir_cpan::linux {
    # If this machine has a defined testuser, let's install cpanminus
    # automatically.
    if $testuser {
        exec { "install cpanm for $testuser":
            command => "/bin/su - -c '

    wget --no-check-certificate http://cpanmin.us -O - \
        | perl - -l /home/$testuser/perl5 App::cpanminus

            ' $testuser",
            creates => "/home/$testuser/perl5/bin/cpanm",
            logoutput => true,
        }
    }
}
