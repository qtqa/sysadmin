import "*"

# This module sets up the necessary environment so that users
# can easily install cpan modules into a prefix under $HOME/perl5
# and have all perl scripts find them automatically, without any
# root permissions required.
class homedir_cpan {
    case $operatingsystem {
        Ubuntu:     { include homedir_cpan::ubuntu }
        Darwin:     { include homedir_cpan::mac }
        default:    { error("homedir_cpan is not yet implemented for $operatingsystem") }
    }
}

