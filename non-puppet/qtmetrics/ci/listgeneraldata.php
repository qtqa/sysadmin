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

/* Following 'input' variables must be set prior to including this file */
    // $_SESSION['arrayProjectName']
    // $_SESSION['arrayProjectBuildLatest']
    // $_SESSION['arrayProjectBuildLatestResult']
    // $ciPlatform
    // $ciPlatformName
    // $project
    // $conf
    // $build
    // $timescaleType
    // $timescaleValue
    // $projectFilter
    // $confFilter

/* Read the Build data */
foreach ($_SESSION['arrayProjectName'] as $key=>$value) {
    if ($value == $project) {
        $latestBuildNumber = $_SESSION['arrayProjectBuildLatest'][$key];
        $buildNumber = $_SESSION['arrayProjectBuildLatest'][$key];
        $buildResult = $_SESSION['arrayProjectBuildLatestResult'][$key];
        $buildTimestamp = $_SESSION['arrayProjectBuildLatestTimestamp'][$key];
        $buildDuration = $_SESSION['arrayProjectBuildLatestDuration'][$key];
    }
}
$buildNumber = $latestBuildNumber - $build;     // Selected build
$buildNumberString = createBuildNumberString($buildNumber);
$projectConfValid = FALSE;                      // Can be used to identify if Configuration is available for the latest Project Build (later in other listxxx.php files)

/* Project data */
if ($conf == "All") {
    if ($build == 0) {                          // Show the latest build ...
        // Data read above
    } else {                                    // ... or the selected build (read from database)
        $sql = cleanSqlString(
               "SELECT result, timestamp, duration
                FROM ci
                WHERE $projectFilter AND build_number=$buildNumber");
        $dbColumnCiResult = 0;
        $dbColumnCiTimestamp = 1;
        $dbColumnCiDuration = 2;
        if ($useMysqli) {
            $result = mysqli_query($conn, $sql);
            $numberOfRows = mysqli_num_rows($result);
        } else {
            $result = mysql_query($sql) or die (mysql_error());
            $numberOfRows = mysql_num_rows($result);
        }
        if ($numberOfRows > 0) {                                        // Should be only one match
            if ($useMysqli)
                $resultRow = mysqli_fetch_row($result);
            else
                $resultRow = mysql_fetch_row($result);
            $buildResult = $resultRow[$dbColumnCiResult];
            $buildTimestamp = $resultRow[$dbColumnCiTimestamp];
            $buildDuration = $resultRow[$dbColumnCiDuration];
        }
    }
    echo "<table>";
    echo "<tr><td>Project: </td><td class=\"tableCellBackgroundTitle\">$project</td></tr>";
    if ($ciPlatform <> 0) {
        echo '<tr><td>Platform:</td><td class="tableCellBackgroundTitle">' . $ciPlatformName . '</td></tr>';
        echo '<tr><td>Configuration:</td><td class="tableCellBackgroundTitle fontColorGrey">' . $ciPlatformFilter . '</td></tr>';
    }
    if ($timescaleType == "Since")
        echo '<tr><td>Since:</td><td class="timescaleSince">' . $timescaleValue . '</td></tr>';
    echo "<tr><td>Build: </td><td>$buildNumber</td></tr>";
    $fontColorClass = "fontColorBlack";
    if ($buildResult == "SUCCESS")
        $fontColorClass = "fontColorGreen";
    if ($buildResult == "FAILURE")
        $fontColorClass = "fontColorRed";
    echo '<tr><td>Build Result: </td><td class="' . $fontColorClass . '">' . $buildResult . '</td></tr>';
    echo '<tr><td>Build Time: </td><td>' . $buildTimestamp . ' (GMT)</td></tr>';
    echo '<tr><td>Build Duration: </td><td>' . $buildDuration . '</td></tr>';
    echo '<tr><td>Build Log File: </td><td><a href="' . LOGFILEPATHCI . $project
        . '/build_' . $buildNumberString . '/log.txt.gz" target="_blank">log.txt.gz</a></td></tr>';
        // Example: http://testresults.qt.io/ci/Qt3D_master_Integration/build_00412/log.txt.gz
    echo "</table>";
}

/* Configuration data */
else {
    if ($build == 0)                        // Show the latest build ...
        $sql = cleanSqlString(
               "SELECT result, forcesuccess, insignificant, timestamp, duration
                FROM cfg_latest
                WHERE $confFilter AND $projectFilter");
    else                                    // ... or the selected build
        $sql = cleanSqlString(
               "SELECT result, forcesuccess, insignificant, timestamp, duration
                FROM cfg
                WHERE $confFilter AND $projectFilter AND build_number=$buildNumber");
    $dbColumnCfgResult = 0;
    $dbColumnCfgForceSuccess = 1;
    $dbColumnCfgInsignificant = 2;
    $dbColumnCfgTimestamp = 3;
    $dbColumnCfgDuration = 4;
    if ($useMysqli) {
        $result = mysqli_query($conn, $sql);
        $numberOfRows = mysqli_num_rows($result);
    } else {
        $result = mysql_query($sql) or die (mysql_error());
        $numberOfRows = mysql_num_rows($result);
    }
    if ($numberOfRows > 0) {                                        // Should be only one match
        if ($useMysqli)
            $resultRow = mysqli_fetch_row($result);
        else
            $resultRow = mysql_fetch_row($result);
        $buildResult = $resultRow[$dbColumnCfgResult];
        $buildTimestamp = $resultRow[$dbColumnCfgTimestamp];
        $buildDuration = $resultRow[$dbColumnCfgDuration];
        $buildForceSuccess = $resultRow[$dbColumnCfgForceSuccess];
        $buildInsignificant = $resultRow[$dbColumnCfgInsignificant];
        $projectConfValid = TRUE;
        echo "<table>";
        echo "<tr><td>Project: </td><td class=\"tableCellBackgroundTitle\">$project</td></tr>";
        echo "<tr><td>Configuration: </td><td class=\"tableCellBackgroundTitle\">$conf</td></tr>";
        // Note: Timescale filter not shown here because it does not affect this view
        echo "<tr><td>Build: </td><td>$buildNumber</td></tr>";
        $fontColorClass = "fontColorBlack";
        if ($buildResult == "SUCCESS")
            $fontColorClass = "fontColorGreen";
        if ($buildResult == "FAILURE")
            $fontColorClass = "fontColorRed";
        echo '<tr><td>Build Result: </td><td class="' . $fontColorClass . '">' . $buildResult . '</td></tr>';
        echo '<tr><td>Build Time: </td><td>' . $buildTimestamp . ' (GMT)</td></tr>';
        echo '<tr><td>Build Duration: </td><td>' . $buildDuration . '</td></tr>';
        if ($buildForceSuccess == 1)
            echo '<tr><td>Force Success: </td><td>' . FLAGON . '</td></tr>';
        else
            echo '<tr><td>Force Success: </td><td>' . FLAGOFF . '</td></tr>';
        if ($buildInsignificant == 1)
            echo '<tr><td>Insignificant: </td><td>' . FLAGON . '</td></tr>';
        else
            echo '<tr><td>Insignificant: </td><td>' . FLAGOFF . '</td></tr>';
        echo '<tr><td>Build Log File: </td><td><a href="' . LOGFILEPATHCI . $project
            . '/build_' . $buildNumberString . '/' . $conf . '/log.txt.gz" target="_blank">log.txt.gz</a></td></tr>';
            // Example: http://testresults.qt.io/ci/Qt3D_master_Integration/build_00412/linux-g++-32_Ubuntu_10.04_x86/log.txt.gz
        echo "</table>";
    }
}

?>