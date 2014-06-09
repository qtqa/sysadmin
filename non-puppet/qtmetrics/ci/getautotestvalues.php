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

// (Note: session started in getfilters.php)

/* Get list of project values to session variable xxx (if not done already) */
if(!isset($_SESSION['arrayAutotestName'])) {

    /* Connect to the server */
    require(__DIR__.'/../connect.php');

    /* Read all Autotest values from database */
    $sql = "SELECT DISTINCT name FROM test ORDER BY name";                  // Read from the complete table to include values for any build
    if ($useMysqli) {
        $result = mysqli_query($conn, $sql);
        $numberOfRows = mysqli_num_rows($result);
    } else {
        $selectdb="USE $db";
        $result = mysql_query($selectdb) or die (mysql_error());
        $result = mysql_query($sql) or die (mysql_error());
        $numberOfRows = mysql_num_rows($result);
    }

    /* Store Autotest values to session variable (ref. http://www.phpriot.com/articles/intro-php-sessions/7) */
    $arrayAutotestName = array();
    for ($i=0; $i<$numberOfRows; $i++) {                                    // Loop the Autotests
        if ($useMysqli)
            $resultRow = mysqli_fetch_row($result);
        else
            $resultRow = mysql_fetch_row($result);
        $arrayAutotestName[] = $resultRow[0];
    }
    $_SESSION['arrayAutotestName'] = $arrayAutotestName;

    if ($useMysqli)
        mysqli_free_result($result);        // Free result set

    /* Close connection to the server */
    require(__DIR__.'/../connectionclose.php');

}

?>