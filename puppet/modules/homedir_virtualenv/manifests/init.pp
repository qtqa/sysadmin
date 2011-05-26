import "*"

# This module sets up the necessary environment so that users
# can easily install python modules into a prefix under $HOME/pythonXY
# and have all python scripts find them automatically, without any
# root permissions required.
#
# This is set up using virtualenv:
#
#   http://pypi.python.org/pypi/virtualenv
#
# Within the virtualenv, easy_install and pip are both made available
# for installing packages.
class homedir_virtualenv {
    case $operatingsystem {
        Ubuntu:     { include homedir_virtualenv::ubuntu }
        Darwin:     { include homedir_virtualenv::mac }
        default:    { error("homedir_virtualenv is not yet implemented for $operatingsystem") }
    }
}

