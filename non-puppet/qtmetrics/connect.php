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

require "connectiondefinitions.php";

if ($disableErrorMessages)
    error_reporting(E_ERROR);                                                     // Hide error messages to prevent e.g. MySQL connection setting to be shown

if ($useMysqli) {
    if ($usePersistentConnection)
        $conn = mysqli_connect("p:".$host, $user, $passwd, $db);
    else
        $conn = mysqli_connect($host, $user, $passwd, $db);                       // (see http://www.php.net/manual/en/mysqli.construct.php)
    if (mysqli_connect_errno()) {                                                 // Check connection
        printf("Connect failed !");
        exit();
    }
} else {
    if ($usePersistentConnection)
        $conn = mysql_pconnect($host,$user,$passwd)
            or die ("Connect failed to host !");                                  // Use persistent connection  (see http://php.net/manual/en/function.mysql-pconnect.php)
    else
        $conn = mysql_connect($host,$user,$passwd,false,MYSQL_CLIENT_INTERACTIVE)
            or die ("Connect failed to host !");                                  // Use interactive connection (see http://php.net/manual/en/function.mysql-connect.php or http://notaapit.blogspot.fi/2010/10/handling-mysql-too-many-connections.html)
    mysql_select_db($db,$conn) or die ("Connect failed to database !");
}

?>