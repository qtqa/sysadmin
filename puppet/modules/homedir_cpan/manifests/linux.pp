class homedir_cpan::linux {
    # If this machine has a defined testuser, let's install cpanminus
    # automatically.
    if $homedir_cpan::user {
        exec { "install cpanm for $homedir_cpan::user":
            command => "/bin/su - -c '

    wget --no-check-certificate http://cpanmin.us -O - \
        | perl - -l /home/$homedir_cpan::user/perl5 App::cpanminus

            ' $homedir_cpan::user",
            creates => "/home/$homedir_cpan::user/perl5/bin/cpanm",
            logoutput => true,
        }
    }
}
