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

include(__DIR__.'/../commonfunctions.php');

/* Print status */
echo '<div id="sessionStatus">';
if ($timeOffset == "GMT+0000")
    $timeOffsetFormatted = "GMT";
else
    $timeOffsetFormatted = substr($timeOffset, 0, 6) . ':' . substr($timeOffset, 6, 2);         // Add minute separator ':'
$sessionTime = getLocalTime($_SESSION['sessionDate'], $timeOffset);                             // Change UTC to local time
if ($initial == 1) {                                 // Initial loading of the page
    echo '<b>Welcome</b><br/><br/>';
    echo 'Loading data for your session.<br/><br/>';
    echo 'If not ready in one minute, please <a href="javascript:void(0);" onclick="reloadFilters()">reload</a>...';
}
if ($initial == 0) {                                 // Normal case (show session time)
    echo 'Session started:<br/>' . $sessionTime . ' (' . $timeOffsetFormatted . ')<br/><br/>';
}
echo '</div>';

?>