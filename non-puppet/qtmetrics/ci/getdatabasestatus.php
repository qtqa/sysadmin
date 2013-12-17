<?php
session_start();
?>

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
$initial = $_GET["initial"];                         // 'true' = initial load of the page, 'false' = normal use of the page
$timeOffset = $_GET["timeoffset"];                   // Use client local time offset taking daylight saving time into account, e.g. "GMT-0600"
$timeOffset = rawurldecode($timeOffset);             // Decode the encoded parameter (encoding in ajaxrequest.js)

include "functions.php";

/* Connect to the server */
require(__DIR__.'/../connect.php');

/* Read all Autotest values from database */
$sql = "SELECT rebuild, date, current, total FROM generic";
$dbColumnGenericRebuild = 0;
$dbColumnGenericDate = 1;
$dbColumnGenericCurrent = 2;
$dbColumnGenericTotal = 3;
if ($useMysqli) {
    $result = mysqli_query($conn, $sql);
    $numberOfRows = mysqli_num_rows($result);
} else {
    $selectdb="USE $db";
    $result = mysql_query($selectdb) or die (mysql_error());
    $result = mysql_query($sql) or die (mysql_error());
    $numberOfRows = mysql_num_rows($result);
}

/* Store rebuild values to session variables */
$_SESSION['rebuildStatus'] = 99;
$_SESSION['rebuildDate'] = "";
$_SESSION['rebuildCurrent'] = 0;
$_SESSION['rebuildTotal'] = 0;
if ($numberOfRows > 0) {                                                     // Should be only one match
    if ($useMysqli)
        $resultRow = mysqli_fetch_row($result);
    else
        $resultRow = mysql_fetch_row($result);
    $_SESSION['rebuildStatus'] = $resultRow[$dbColumnGenericRebuild];
    $_SESSION['rebuildDate'] = $resultRow[$dbColumnGenericDate];             // UTC time
    $_SESSION['rebuildCurrent'] = $resultRow[$dbColumnGenericCurrent];
    $_SESSION['rebuildTotal'] = $resultRow[$dbColumnGenericTotal];
}

if ($useMysqli)
    mysqli_free_result($result);        // Free result set

/* Store session start time to compare with rebuild time */
if (!isset($_SESSION['sessionDate']))
    $_SESSION['sessionDate'] = gmdate("Y-m-d H:i:s");                        // UTC time

/* Close connection to the server */
require(__DIR__.'/../connectionclose.php');

/* Print status */
echo '<div id="databaseRebuildStatus">';
if ($timeOffset == "GMT+0000")
    $timeOffsetFormatted = "GMT";
else
    $timeOffsetFormatted = substr($timeOffset, 0, 6) . ':' . substr($timeOffset, 6, 2);         // Add minute separator ':'
$sessionTime = getLocalTime($_SESSION['sessionDate'], $timeOffset);                             // Change UTC to local time
$rebuildTime = getLocalTime($_SESSION['rebuildDate'], $timeOffset);                             // Change UTC to local time
if ($initial == 1 AND $_SESSION['rebuildStatus'] == 0) {                                        // Initial loading of the page
    echo '<b>Welcome</b><br/><br/>';
    echo 'Loading data for your session.<br/><br/>';
    echo 'If not ready in one minute, please <a href="javascript:void(0);" onclick="reloadFilters()">reload</a>...';
}
if ($initial == 0 AND $_SESSION['rebuildStatus'] == 0) {                                        // Normal case (show update time)
    echo 'Session started:<br/>' . $sessionTime . ' (' . $timeOffsetFormatted . ')<br/><br/>';
    echo 'Database updated:<br/>' . $rebuildTime . ' (' . $timeOffsetFormatted . ')<br/>';
    if ($rebuildTime > $sessionTime) {                                                          // Special case 1: Database updated after the user session started
        echo '<div class="fontColorGreen">';
        echo '<b>New data available</b><br/>';
        echo '</div>';
        echo 'please <a href="javascript:void(0);" onclick="reloadFilters()">reload</a>...';
    }
}
if ($_SESSION['rebuildStatus'] == 1) {                                                          // Special case 2: Database rebuild in progress (shown also with initial load of the page)
    echo 'Session started:<br/>' . $sessionTime . ' (' . $timeOffsetFormatted . ')<br/><br/>';
    echo '<div class="fontColorRed">';
    echo 'Database rebuild started:<br/>';
    echo '</div>';
    echo $rebuildTime . ' (' . $timeOffsetFormatted . ')<br/>';
    $ratio = round(100 * $_SESSION['rebuildCurrent'] / $_SESSION['rebuildTotal'], 0);
    if ($ratio == 100 AND $_SESSION['rebuildCurrent'] < $_SESSION['rebuildTotal'])
        $ratio = 99;                                                                            // Don't show 100% until all done
    echo 'progress: ' . $_SESSION['rebuildCurrent'] . '/' . $_SESSION['rebuildTotal'] . ' (' . $ratio . '%)' . '<br/>';
    echo '<a href="javascript:void(0);" onclick="loadDatabaseStatus()">refresh</a>';
}
echo '</div>';

?>