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

/* Following 'input' variabes must be set prior to including this file */
    // $_SESSION['arrayProjectName']
    // $_SESSION['arrayProjectBuildLatest']
    // $_SESSION['arrayProjectBuildLatestResult']
    // $project
    // $conf
    // $projectFilter
    // $confFilter

/* Read the latest Build number (used also in other listxxx files) */
foreach ($_SESSION['arrayProjectName'] as $key=>$value) {
    if ($value == $project) {
        $latestBuild = $_SESSION['arrayProjectBuildLatest'][$key];
        $latestBuildResult = $_SESSION['arrayProjectBuildLatestResult'][$key];
        $latestBuildTimestamp = $_SESSION['arrayProjectBuildLatestTimestamp'][$key];
        $latestBuildDuration = $_SESSION['arrayProjectBuildLatestDuration'][$key];
    }
}
$buildNumberString = createBuildNumberString($latestBuild);
$projectConfValid = FALSE;               // Can be used to identify if Configuration is available for the latest Project Build (later in other listxxx.php files)

/* Project data */
if ($conf == "All") {
    echo "<table>";
    echo "<tr><td>Project: </td><td class=\"tableCellBackgroundTitle\">$project</td></tr>";
    echo "<tr><td>Latest Build: </td><td class=\"tableCellBackgroundTitle\">$latestBuild</td></tr>";
    $fontColorClass = "fontColorBlack";
    if ($latestBuildResult == "SUCCESS")
        $fontColorClass = "fontColorGreen";
    if ($latestBuildResult == "FAILURE")
        $fontColorClass = "fontColorRed";
    echo '<tr><td>Latest Build Result: </td><td class="' . $fontColorClass . '">' . $latestBuildResult . '</td></tr>';
    echo "<tr><td>Build Date: </td><td>$latestBuildTimestamp</td></tr>";
    echo "<tr><td>Build Duration: </td><td>$latestBuildDuration</td></tr>";
    echo '<tr><td>Build Log File: </td><td><a href="' . LOGFILEPATHCI . $project
        . '/build_' . $buildNumberString . '/log.txt.gz" target="_blank">log.txt.gz</a></td></tr>';
        // Example: http://testresults.qt-project.org/ci/Qt3D_master_Integration/build_00412/log.txt.gz
    echo "</table><br/>";
}

/* Configuration data */
else {
    $sql = "SELECT result, timestamp, duration, forcesuccess, insignificant
            FROM cfg
            WHERE $projectFilter $confFilter AND build_number=$latestBuild";
    $dbColumnCfgResult = 0;
    $dbColumnCfgTimestamp = 1;
    $dbColumnCfgDuration = 2;
    $dbColumnCfgForceSuccess = 3;
    $dbColumnCfgInsignificant = 4;
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
        $latestBuildResult = $resultRow[$dbColumnCfgResult];
        $latestBuildTimestamp = $resultRow[$dbColumnCfgTimestamp];
        $latestBuildDuration = $resultRow[$dbColumnCfgDuration];
        $latestBuildForceSuccess = $resultRow[$dbColumnCfgForceSuccess];
        $latestBuildInsignificant = $resultRow[$dbColumnCfgInsignificant];
        $projectConfValid = TRUE;
        echo "<table>";
        echo "<tr><td>Project: </td><td class=\"tableCellBackgroundTitle\">$project</td></tr>";
        echo "<tr><td>Configuration: </td><td class=\"tableCellBackgroundTitle\">$conf</td></tr>";
        echo "<tr><td>Latest Build: </td><td class=\"tableCellBackgroundTitle\">$latestBuild</td></tr>";
        $fontColorClass = "fontColorBlack";
        if ($latestBuildResult == "SUCCESS")
            $fontColorClass = "fontColorGreen";
        if ($latestBuildResult == "FAILURE")
            $fontColorClass = "fontColorRed";
        echo '<tr><td>Latest Build Result: </td><td class="' . $fontColorClass . '">' . $latestBuildResult . '</td></tr>';
        echo "<tr><td>Build Date: </td><td>$latestBuildTimestamp</td></tr>";
        echo "<tr><td>Build Duration: </td><td>$latestBuildDuration</td></tr>";
        if ($latestBuildForceSuccess == 1)
            echo '<tr><td>Force Success: </td><td>' . FLAGON . '</td></tr>';
        else
            echo '<tr><td>Force Success: </td><td>' . FLAGOFF . '</td></tr>';
        if ($latestBuildInsignificant == 1)
            echo '<tr><td>Insignificant: </td><td>' . FLAGON . '</td></tr>';
        else
            echo '<tr><td>Insignificant: </td><td>' . FLAGOFF . '</td></tr>';
        echo '<tr><td>Build Log File: </td><td><a href="' . LOGFILEPATHCI . $project
            . '/build_' . $buildNumberString . '/' . $conf . '/log.txt.gz" target="_blank">log.txt.gz</a></td></tr>';
            // Example: http://testresults.qt-project.org/ci/Qt3D_master_Integration/build_00412/linux-g++-32_Ubuntu_10.04_x86/log.txt.gz
        echo "</table><br/>";
    }
}
echo "<br>";

?>