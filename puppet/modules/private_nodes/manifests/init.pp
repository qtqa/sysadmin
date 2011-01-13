# This file is for site-specific node declarations and globals
#
# If you want to add your own node declarations, without exposing your
# hostnames etc in the publicly visible nodes.pp, then create a
# private/modules/private_nodes module and put the node declarations in
# there.

# Default values for globals - may be overridden for specific nodes
$location = "unknown"
$qtgitreadonly = "git://qt.gitorious.org/"

# you can improve speed and reliability of git operations by operating
# a local mirror of the gitorious.org repos you use, and putting its
# URL into this variable
$qtgitreadonly_local = $qtgitreadonly

# `$input' is an http URL where large files are hosted (tarballs etc)
# These files are all publicly available but you'll have to host them
# on your own mirror
$input = "http://replace-me.example.com/input"

# set this to the address of your icecream scheduler, if you want to
# use icecream.  Note, you might not have to do this if icecream's
# automated finding of the scheduler works for you
$icecc_scheduler_host = ""
