<?php
#############################################################################
##
## Copyright (C) 2013 Digia Plc and/or its subsidiary(-ies).
## Contact: http://www.qt-project.org/legal
##
## This file is part of the Qt Metrics web portal.
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
?>

<?php

/* Include the server specific definitions. Note: Typically only either one on a same server */
include('/home/qtintegration/connectionconfig.php');        // Read if available, skip otherwise
include('connectionconfig.php');                            // -,,-

/* Local server (local metrics pages are shown from here) */
if (!defined("LOCALSERVER"))
    define("LOCALSERVER", "");

/* Public server (public metrics pages are shown from here) */
if (!defined("PUBLICSERVER"))
    define("PUBLICSERVER", "testresults.qt.io");

/* CI log file path */
if (!defined("LOGFILEPATHCI"))
    define("LOGFILEPATHCI", "http://testresults.qt.io/ci/");

/* Select MySQL API. For more details, see http://php.net/manual/en/mysqlinfo.api.choosing.php */
$useMysqli = TRUE;                      // Set TRUE for mysqli extension or FALSE for old mysql extension

/* Select connection type. The 'normal connection will be closed as soon as the script ends */
$usePersistentConnection = TRUE;        // Set TRUE for persistent connection or FALSE for 'normal'

/* Disable MySQL and other error messages (to prevent e.g. MySQL connection settings to be displayed in case of a connection failure) */
$disableErrorMessages = TRUE;           // Set TRUE when using target live server or FALSE in development environment

?>
