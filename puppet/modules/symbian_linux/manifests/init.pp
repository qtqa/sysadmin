import "*"

# This module sets up some elements of the environment for
# compiling Qt using the symbian-gcce and symbian-armcc mkspecs
# on Linux (i.e., makefile build system instead of sbs).
#
# Installation of the S60 SDKs themselves is deliberately omitted
# from this module for the time being.

class symbian_linux {
    case $operatingsystem {
        Ubuntu:     { include symbian_linux::ubuntu }
    }
}

