# This module sets up the necessary environment so that users
# can easily install cpan modules into a prefix under $HOME/perl5
# and have all perl scripts find them automatically, without any
# root permissions required.
class homedir_cpan () {
    case $::operatingsystem {
        Ubuntu:     { require homedir_cpan::ubuntu }
        OpenSuSE:   { require homedir_cpan::opensuse }
        Darwin:     { require homedir_cpan::mac }
    }
}

