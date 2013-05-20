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
    // $projectFilter
    // $confFilter
    // $latestBuild        (in listgeneraldata.php)
    // $timeStart
    // $timeConnect

/* Read data from database */
if ($project<>"All" AND $conf=="All") {                                       // Print Project Builds
    $sql = "SELECT build_number,result, timestamp
            FROM ci
            WHERE $projectFilter
            ORDER BY build_number";
}
if ($project<>"All" AND $conf<>"All") {                                       // Print Configuration Builds
    $sql = "SELECT build_number,result, timestamp
            FROM cfg
            WHERE $projectFilter $confFilter
            ORDER BY build_number";
}
define("DBCOLUMNBUILD", 0);
define("DBCOLUMNRESULT", 1);
define("DBCOLUMNTIMESTAMP", 2);
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
if ($numberOfRows>0) {
    $printedBuildCount = 0;
    for ($i=0; $i<$numberOfRows; $i++) {                                    // Loop the Builds
        $printThisBuild = FALSE;
        if ($useMysqli)
            $resultRow = mysqli_fetch_row($result);
        else
            $resultRow = mysql_fetch_row($result);
        if ($numberOfRows > HISTORYBUILDCOUNT) {                            // Limit number of Builds printed (the last n ones)
            if ($resultRow[DBCOLUMNBUILD] > $latestBuild - HISTORYBUILDCOUNT)
                $printThisBuild = TRUE;
        } else {
            $printThisBuild = TRUE;
        }
        if ($printThisBuild) {

            /* Build number */
            $buildstring = $resultRow[DBCOLUMNBUILD];                       // Create the link url to build directory...
            if ($resultRow[DBCOLUMNBUILD] < 10000)
                $buildstring = '0' . $resultRow[DBCOLUMNBUILD];
            if ($resultRow[DBCOLUMNBUILD] < 1000)
                $buildstring = '00' . $resultRow[DBCOLUMNBUILD];
            if ($resultRow[DBCOLUMNBUILD] < 100)
                $buildstring = '000' . $resultRow[DBCOLUMNBUILD];
            if ($resultRow[DBCOLUMNBUILD] < 10)
                $buildstring = '0000' . $resultRow[DBCOLUMNBUILD];
            if ($conf == "All") {
                $buildLink = '<a href="' . LOGFILEPATHCI . $project . '/build_' . $buildstring
                    . '" target="_blank">' . $resultRow[DBCOLUMNBUILD] . '</a>';                // Example: http://testresults.qt-project.org/ci/Qt3D_master_Integration/build_00412
            } else {
                $buildLink = '<a href="' . LOGFILEPATHCI . $project . '/build_' . $buildstring
                    . '/' . $conf . '" target="_blank">' . $resultRow[DBCOLUMNBUILD] . '</a>';  // Example: http://testresults.qt-project.org/ci/Qt3D_master_Integration/build_00412/linux-g++-32_Ubuntu_10.04_x86
            }
            $arrayBuildNumbersRow[] = '<td class="tableCellCentered">' . $buildLink . '</td>';

            /* Build date */
            $date = strstr($resultRow[DBCOLUMNTIMESTAMP], ' ', TRUE);       // 'yyyy-mm-dd hh:mm:ss' -> 'yyyy-mm-dd'
            $date = strstr($date, '-', FALSE);                              // 'yyyy-mm-dd' -> '-mm-dd'
            $date = substr($date,1);                                        // '-mm-dd' -> 'mm-dd'
            $arrayBuildDatesRow[] = '<td class="tableCellCentered">' . $date . '</td>';

            /* Build result */
            $cellColor = '<td class="tableSingleBorder">';
            if ($resultRow[DBCOLUMNRESULT] == "SUCCESS")
                $cellColor = '<td class="tableSingleBorder tableCellBackgroundGreen">';
            if ($resultRow[DBCOLUMNRESULT] == "FAILURE")
                $cellColor = '<td class="tableSingleBorder tableCellBackgroundRed">';
            $arrayBuildResultsRow[] = $cellColor . $resultRow[DBCOLUMNRESULT] . '</td>';
            $printedBuildCount++;
        }
    }
}

if ($useMysqli)
    mysqli_free_result($result);                                            // Free result set

/* Print the data */
echo '<b>Build history</b> (last ' . HISTORYBUILDCOUNT . ' Builds)<br/><br/>';
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

    echo "</table>";
} else {
    echo "(no items)<br/>";
}

/* Elapsed time */
$timeEnd = microtime(true);
$timeDbConnect = round($timeConnect - $timeStart, 2);
$timeDbRead = round($timeRead - $timeConnect, 2);
$timeCalc = round($timeEnd - $timeRead, 2);
$time = round($timeEnd - $timeStart, 2);
echo "<div class=\"elapdedTime\">";
echo "<ul><li>";
echo "Total time: $time s (database connect time: $timeDbConnect s, database read time: $timeDbRead s, calculation time: $timeCalc s)";
echo "</li></ul>";
echo "</div>";

?>