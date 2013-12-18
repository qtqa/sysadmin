class bb_tester::ubuntu inherits bb_tester::base {
    # Allow test machines to install modules from cpan under $HOME/perl5
    include homedir_cpan
    # Install CPAN modules needed outside building
    include cpan

    # Allow test machines to install python modules with pip or easy_install
    # to $HOME/python26
    include homedir_virtualenv

    # In order to run the BlackBerry installer and the IDE on Ubuntu 64-bit,
    # you need to install the 32-bit libraries
    if $::lsbmajdistrelease == 11 or $::lsbmajdistrelease == 12  {
        if $::architecture == x86_64 or $::architecture == amd64 {
            package {
                "ia32-libs":    ensure => installed;
            }
        }
    }
}
