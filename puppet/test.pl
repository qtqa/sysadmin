#!/usr/bin/env perl
use strict;
use warnings;

use FindBin;

if ($^O ne "MSWin32") {
    die "This script is only intended for use on Windows";
}

# Add puppet to PATH.
# Note, just editing PATH in the current process doesn't work,
# we have to start a new child process for some reason.
if (! ($ENV{PATH} =~ /puppet\\Ruby187/i)) {
    $ENV{PATH} = "c:\\puppet\\Ruby187\\bin;".$ENV{PATH};
    exec "perl", $0, @ARGV;
}

exec "puppet",
    # Note, leading `/' is important here ...
    "--confdir=/$FindBin::Bin",
    # ... and absence of leading `/' is important here :-)
    "--vardir=c:/puppet/var",
    "--debug",
    "--verbose",
    "--color=false",
    "/$FindBin::Bin/manifests/site.pp",
;
