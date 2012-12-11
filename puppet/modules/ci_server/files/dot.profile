# put reliable versions of git, scp, ssh etc first in PATH
PATH=$HOME/reliable-bin:$PATH
export PATH

# use own perl prefix
eval $(perl -Mlocal::lib)
