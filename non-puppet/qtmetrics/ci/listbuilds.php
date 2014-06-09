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
    // $build
    // $timescaleType
    // $timescaleValue
    // $projectFilter
    // $confFilter
    // $latestBuildNumber        (in listgeneraldata.php)
    // $showElapsedTime
    // $timeStart
    // $timeConnect

$timeStartThis = microtime(true);

/* Read data from database */
if ($project<>"All" AND $conf=="All") {                                       // Print Project Builds
    $sql = cleanSqlString(
           "SELECT build_number, result, timestamp
            FROM ci
            WHERE $projectFilter
            ORDER BY build_number");
}
if ($project<>"All" AND $conf<>"All") {                                       // Print Configuration Builds
    $sql = cleanSqlString(
           "SELECT build_number, result, timestamp
            FROM cfg
            WHERE $confFilter AND $projectFilter
            ORDER BY build_number");
}
$dbColumnBuildNumber = 0;
$dbColumnResult = 1;
$dbColumnTimestamp = 2;
if ($useMysqli) {
    $result = mysqli_query($conn, $sql);
    $numberOfRows = mysqli_num_rows($result);
} else {
    $result = mysql_query($sql) or die (mysql_error());
    $numberOfRows = mysql_num_rows($result);
}
$timeRead = microtime(true);
$arrayBuildNumbersRow = array();
$arrayBuildDatesRow = array();
$arrayBuildResultsRow = array();
$arrayBuildLinksRow = array();
if ($numberOfRows>0) {
    $printedBuildCount = 0;
    for ($i=0; $i<$numberOfRows; $i++) {                                    // Loop the Builds
        $printThisBuild = FALSE;
        if ($useMysqli)
            $resultRow = mysqli_fetch_row($result);
        else
            $resultRow = mysql_fetch_row($result);
        if ($numberOfRows > HISTORYBUILDCOUNT) {                            // Limit number of Builds printed (the last n ones)
            if ($resultRow[$dbColumnBuildNumber] > $latestBuildNumber - HISTORYBUILDCOUNT)
                $printThisBuild = TRUE;
        } else {
            $printThisBuild = TRUE;
        }
        if ($printThisBuild) {

            /* Build number */
            $buildNumberOffset = $latestBuildNumber - $resultRow[$dbColumnBuildNumber];
            if ($buildNumberOffset == $build) {                                     // Highlight the selected build
                $cellColorBuild = '<td class="tableCellCentered tableCellBuildSelected">';
                $filterLink = $resultRow[$dbColumnBuildNumber];
            } else {
                $cellColorBuild = '<td class="tableCellCentered">';
                $filterLink = '<b><a href="javascript:void(0);" onclick="filterBuild(' . $buildNumberOffset . ')">' . $resultRow[$dbColumnBuildNumber] . '</a></b>';
            }
            $arrayBuildNumbersRow[] = $cellColorBuild . $filterLink . '</td>';

            /* Build date */
            $date = strstr($resultRow[$dbColumnTimestamp], ' ', TRUE);              // 'yyyy-mm-dd hh:mm:ss' -> 'yyyy-mm-dd'
            $cellColor = $cellColorBuild;                                           // By default show like the build number
            if ($timescaleType == "Since" AND $date >= $timescaleValue)
                    $cellColor = '<td class="tableCellCentered timescaleSince">';   // Highlight date if inside the selected timescale
            $date = strstr($date, '-', FALSE);                                      // 'yyyy-mm-dd' -> '-mm-dd'
            $date = substr($date,1);                                                // '-mm-dd' -> 'mm-dd'
            $arrayBuildDatesRow[] = $cellColor . $date . '</td>';

            /* Build result */
            $cellColor = '<td class="tableSingleBorder">';
            if ($resultRow[$dbColumnResult] == "SUCCESS")
                $cellColor = '<td class="tableSingleBorder tableCellBackgroundGreen">';
            if ($resultRow[$dbColumnResult] == "FAILURE")
                $cellColor = '<td class="tableSingleBorder tableCellBackgroundRed">';
            $arrayBuildResultsRow[] = $cellColor . $resultRow[$dbColumnResult] . '</td>';
            $printedBuildCount++;

            /* Build log directory link */
            $buildNumberString = createBuildNumberString($resultRow[$dbColumnBuildNumber]);         // Create the link url to build directory...
            $buildLink = '<a href="' . LOGFILEPATHCI . $project . '/build_' . $buildNumberString;   // Example: http://testresults.qt-project.org/ci/Qt3D_master_Integration/build_00412
            if ($conf <> "All")
                $buildLink = $buildLink . '/' . $conf;                                              // Example: http://testresults.qt-project.org/ci/Qt3D_master_Integration/build_00412/linux-g++-32_Ubuntu_10.04_x86
            $buildLink = $buildLink . '" target="_blank"><img src="images/open-folder.png" alt="open" title="Open the folder for build '
                . $resultRow[$dbColumnBuildNumber] . '"></a>';
            $arrayBuildLinksRow[] = '<td class="tableTopBorder tableBottomBorder tableCellCentered">' . $buildLink . '</td>';

        }
    }
}

if ($useMysqli)
    mysqli_free_result($result);                                            // Free result set

/* Print the data */
echo '<div class="metricsTitle">';
if ($project<>"All" AND $conf=="All")
    echo '<b>Project Build history</b> (last ' . HISTORYBUILDCOUNT . ' Builds)';
if ($project<>"All" AND $conf<>"All")
    echo '<b>Configuration Build history</b> (last ' . HISTORYBUILDCOUNT . ' Builds)';
echo '</div>';
if ($printedBuildCount > 0) {
    echo "<table class=\"fontSmall tableSingleBorder\">";

    /* Build number */
    echo "<tr>";
    echo "<td class=\"tableSingleBorder\"><b>Build</b></td>";
    for ($i=0; $i<$printedBuildCount; $i++)
        echo $arrayBuildNumbersRow[$i];
    echo "</tr>";

    /* Build date */
    echo "<tr>";
    echo "<td class=\"tableSingleBorder\"><b>Date</b></td>";
    for ($i=0; $i<$printedBuildCount; $i++)
        echo $arrayBuildDatesRow[$i];
    echo "</tr>";

    /* Build result */
    echo "<tr class=\"tableSingleBorder\">";
    echo "<td class=\"tableSingleBorder\"><b>Result</b></td>";
    for ($i=0; $i<$printedBuildCount; $i++)
        echo $arrayBuildResultsRow[$i];
    echo "</tr>";

    /* Build log directory link */
    echo "<tr class=\"tableSingleBorder\">";
    echo "<td class=\"tableSingleBorder\"><b>Log files</b></td>";
    for ($i=0; $i<$printedBuildCount; $i++)
        echo $arrayBuildLinksRow[$i];
    echo "</tr>";

    echo "</table>";
} else {
    echo "(no items)<br/>";
}

/* Elapsed time */
if ($showElapsedTime) {
    $timeEnd = microtime(true);
    $timeDbConnect = round($timeConnect - $timeStart, 4);
    $timeDbRead = round($timeRead - $timeStartThis, 4);
    $timeCalc = round($timeEnd - $timeRead, 4);
    $time = round($timeEnd - $timeStartThis, 4);
    echo "<div class=\"elapdedTime\">";
    echo "<ul><li>";
    echo "<b>Total time</b> (round $round): $time s (database read time: $timeDbRead s, calculation time: $timeCalc s); database connect time: $timeDbConnect s";
    echo "</li></ul>";
    echo "</div>";
} else {
    echo "<br>";
}

?>