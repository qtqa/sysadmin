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

/* Select MySQL API. For more details, see http://php.net/manual/en/mysqlinfo.api.choosing.php */
$useMysqli = TRUE;                      // Set TRUE for mysqli extension or FALSE for old mysql extension

/* Select connection type. The 'normal connection will be closed as soon as the script ends */
$usePersistentConnection = TRUE;        // Set TRUE for persistent connection or FALSE for 'normal'

/* Database server definitions
   Define as empty "" if not accessible in server environment where this file is used */
$host = "localhost";
$user = "phpreader";
$passwd = "r-kl_DsS";
$db = "qt";

/* Disable MySQL and other error messages (to prevent e.g. MySQL connection settings to be displayed in case of a connection failure) */
$disableErrorMessages = TRUE;           // Set TRUE when using target live server or FALSE in development environment

/* The base directory for RTA test result XML files under which all the files are available in a certain directory structure
   Define as empty "" if not accessible in server environment where this file is used */
define("RTAXMLBASEDIRECTORY", "");

/* RTA directories in Packaging Jenkins
   Define as empty "" if not accessible in server environment where this file is used */
define("PACKAGINGJENKINSENTERPRISE", "");
define("PACKAGINGJENKINSOPENSOURCE", "");

?>