#############################################################################
##
## Copyright (C) 2012 Digia Plc and/or its subsidiary(-ies).
## Contact: http://www.qt-project.org/legal
##
## This file is part of the Qt Toolkit.
##
## $QT_BEGIN_LICENSE:LGPL$
## Commercial License Usage
## Licensees holding valid commercial Qt licenses may use this file in
## accordance with the commercial license agreement provided with the
## Software or, alternatively, in accordance with the terms contained in
## a written agreement between you and Digia.  For licensing terms and
## conditions see http://qt.digia.com/licensing.  For further information
## use the contact form at http://qt.digia.com/contact-us.
##
## GNU Lesser General Public License Usage
## Alternatively, this file may be used under the terms of the GNU Lesser
## General Public License version 2.1 as published by the Free Software
## Foundation and appearing in the file LICENSE.LGPL included in the
## packaging of this file.  Please review the following information to
## ensure the GNU Lesser General Public License version 2.1 requirements
## will be met: http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
##
## In addition, as a special exception, Digia gives you certain additional
## rights.  These rights are described in the Digia Qt LGPL Exception
## version 1.1, included in the file LGPL_EXCEPTION.txt in this package.
##
## GNU General Public License Usage
## Alternatively, this file may be used under the terms of the GNU
## General Public License version 3.0 as published by the Free Software
## Foundation and appearing in the file LICENSE.GPL included in the
## packaging of this file.  Please review the following information to
## ensure the GNU General Public License version 3.0 requirements will be
## met: http://www.gnu.org/copyleft/gpl.html.
##
##
## $QT_END_LICENSE$
##
#############################################################################

# deployed by puppet - do not modify, your changes will be discarded.

# This env file, if sourced into the environment, will set up a
# $HOME/python<version> directory which is suitable for installing
# python modules into using easy_install or pip, without requiring root access.
# The virtualenv command must be installed first (e.g. python-virtualenv
# on Ubuntu, py26-virtualenv from macports on Mac).
#

# Returns python<major_version><minor_version>, e.g. `python26',
# with appropriate values for the first python in PATH
_qtqa_python_handle() {
    # PYTHON_MAJV = <major_version>, e.g. 2 for python 2.6.5
    # PYTHON_MINV = <minor_version>, e.g. 6 for python 2.6.5
    PYTHON_MAJV=$(python -c 'import sys; print(sys.version_info[0])' 2>/dev/null)
    PYTHON_MINV=$(python -c 'import sys; print(sys.version_info[1])' 2>/dev/null)

    # be silent if there is no python in PATH, or if it's so different
    # that the above code doesn't work.
    if ! test -z $PYTHON_MAJV && ! test -z $PYTHON_MINV; then
        echo "python${PYTHON_MAJV}${PYTHON_MINV}"
    fi
}

# Run some command while holding an exclusive lock on a file.
# The file must not yet exist, and locking directories is not supported.
#
# Parameters:
#   $1    the file to lock
#   rest  the command and arguments to run
#
_qtqa_lock() {
    LOCKFILE="$1"
    shift

    # on Linux, flock seems widely available.  lockfile is also often available.
    # on OSX 10.6, flock seems never available and lockfile is always available.

    if test -f /usr/bin/flock; then
        /usr/bin/flock --exclusive "$LOCKFILE" "$@"
    elif test -f /usr/bin/lockfile; then
        /usr/bin/lockfile "$LOCKFILE"
        "$@"
        status=$?
        rm -f "$LOCKFILE"
        ( exit $status; )
    else
        echo "Internal error: neither flock nor lockfile is available." 1>&2
        false
    fi
}

# Creates a virtualenv at the given prefix, if it doesn't already exist.
# If it looks like a virtualenv already exists there, do nothing.
#
# Parameters:
#   $1    the prefix at which the virtualenv should be created
#
_qtqa_create_virtualenv() {
    LOCAL_PYTHONPREFIX="$1"

    # Create the directory if it doesn't exist, so we at least can lock it.
    mkdir -p "$LOCAL_PYTHONPREFIX" >/dev/null 2>&1

    # If the prefix doesn't exist yet, run virtualenv to set it up.
    # This is locked so that, if we're in the process of installing, we'll
    # wait for it to complete before invoking `test'.
    #
    # Note that there is a small but non-zero chance that some shells in parallel
    # will manage to pass this check.  This means that the virtualenv will be set
    # up multiple times, which wastes a bit of time but otherwise has no ill effect,
    # as there is another lock to serialize the virtualenv setup.
    #
    if ! _qtqa_lock "$LOCAL_PYTHONPREFIX/lock" test -f "$LOCAL_PYTHONPREFIX/bin/python"; then

        # Tell the user what we're doing, because this will slow down the
        # first login a little bit.
        echo -n "Creating a local python setup at $LOCAL_PYTHONPREFIX ... " 1>&2

        # This is locked to prevent multiple installs in parallel.
        _qtqa_lock "$LOCAL_PYTHONPREFIX/lock" virtualenv --quiet "$LOCAL_PYTHONPREFIX" 1>&2

        if test -f "$LOCAL_PYTHONPREFIX/bin/python"; then
            echo "OK." 1>&2
        else
            echo "failed." 1>&2
        fi
    fi
}

# Sources a virtualenv at the given prefix,
# or do nothing silently if the virtualenv doesn't exist.
#
# Parameters:
#   $1    the prefix from which the virtualenv should be sourced
#
_qtqa_source_virtualenv() {
    LOCAL_PYTHONPREFIX="$1"

    # virtualenv will have created a $PREFIX/bin/activate which
    # we can source.
    #
    # Example: /home/qt/python26/bin/activate
    #
    ACTIVATE="$LOCAL_PYTHONPREFIX/bin/activate"

    if test -f "$ACTIVATE"; then
        # The activate script will hack PS1, which is ugly.
        # Avoid that "feature".
        OLD_PS1="$PS1"

        # This sourcing will set PATH appropriately to make the
        # virtualenv python the default used python.
        . "$ACTIVATE"

        PS1="$OLD_PS1"
        export PS1
    fi
}

_qtqa_virtualenv_main() {
    # If we are not a normal user, silently do nothing.
    # It's pointless and dangerous to do this for system accounts.
    #
    # Note that we have no guarantee that all system accounts really
    # have a uid of less than this number, as the sysadmin can set
    # the minimum uid to anything she wants, but this is expected
    # to be sufficient for all of our test machines and anyone else
    # using this script.
    if [ $(id -u) -lt 500 ]; then
        return
    fi

    PYTHONHANDLE=$(_qtqa_python_handle)

    # If python is missing, broken or weird, silently do nothing
    if test -z $PYTHONHANDLE; then
        return
    fi

    # Prefix will be like $HOME/pythonXY,
    # e.g. $HOME/python26 for python 2.6
    LOCAL_PYTHONPREFIX="$HOME/$PYTHONHANDLE"

    # Create the virtualenv if necessary, then source it.
    _qtqa_create_virtualenv "$LOCAL_PYTHONPREFIX"
    _qtqa_source_virtualenv "$LOCAL_PYTHONPREFIX"

    # Avoid unnecessary pollution
    unset -f _qtqa_python_handle
    unset -f _qtqa_lock
    unset -f _qtqa_create_virtualenv
    unset -f _qtqa_source_virtualenv
    unset -f _qtqa_virtualenv_main
}

_qtqa_virtualenv_main

