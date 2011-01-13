#!/bin/sh
DIR=`dirname $0`
# FIXME: is there a standard shell way to do this??
DIR=`perl -mCwd -e "print Cwd::abs_path('$DIR')"`
set -e

set -x
exec puppet --confdir $DIR --verbose --noop manifests/site.pp

