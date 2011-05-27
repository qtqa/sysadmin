# deployed by puppet - do not modify, your changes will be discarded.

# `perl -Mlocal::lib' prints out shell script suitable for use with eval,
# to set up the user's environment to use $HOME/perl5 as a prefix for
# perl modules.  It is not harmful if $HOME/perl5 doesn't exist yet.
#
# Note that we support local::lib itself being installed either as a system
# library, or into $HOME/perl5.
#
# See: perldoc local::lib
#
eval $(perl -I$HOME/perl5/lib/perl5 -Mlocal::lib)
