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
    // $project
    // $timescaleType
    // $timescaleValue
    // $projectFilter
    // $confFilter
    // $showElapsedTime
    // $timeStart
    // $timeConnect

/* Read data from database */
$buildFilter = "";
if ($project <> "All") {
    $build = "";
    foreach ($_SESSION['arrayProjectName'] as $key=>$value) {
        if ($value == $project)
            $build = $_SESSION['arrayProjectBuildLatest'][$key];
    }
    $buildFilter = " AND build_number=$build";
}
$sql = "SELECT cfg, result, forcesuccess, insignificant
        FROM cfg
        WHERE $projectFilter $buildFilter $confFilter
        ORDER BY result,cfg";
$dbColumnCfgCfg = 0;
$dbColumnCfgResult = 1;
$dbColumnCfgForceSuccess = 2;
$dbColumnCfgInsignificant = 3;
if ($useMysqli) {
    $result = mysqli_query($conn, $sql);
    $numberOfRows = mysqli_num_rows($result);
} else {
    $result = mysql_query($sql) or die (mysql_error());
    $numberOfRows = mysql_num_rows($result);
}

/* Print the data */
if ($numberOfRows > 0) {

    /* Counters for printing totals summary row */
    $printedConfs = 0;
    $latestForceSuccessCount = 0;
    $latestInsignCount = 0;
    $latestFailingSignAutotestCount = 0;
    $latestFailingInsignAutotestCount = 0;
    $allFailureCount = 0;
    $allSuccessCount = 0;
    $allTotalCount = 0;

    echo '<b>Configurations</b><br>';

    /* Titles */
    echo '<table class="fontSmall">';
    echo '<tr>';
    echo '<th></th>';
    echo '<th colspan="5" class="tableBottomBorder tableSideBorder">LATEST BUILD</th>';
    if ($timescaleType == "All")
        echo '<th colspan="3" class="tableBottomBorder tableSideBorder">ALL BUILDS (SINCE ' . $_SESSION['minBuildDate'] . ')</th>';
    if ($timescaleType == "Since")
        echo '<th colspan="3" class="tableBottomBorder tableSideBorder">ALL BUILDS SINCE ' . $timescaleValue . '</th>';
    echo '</tr>';
    echo '<tr>';
    echo '<th></th>';
    echo '<th colspan="3" class="tableBottomBorder tableSideBorder">Build Info</th>';
    echo '<th colspan="2" class="tableBottomBorder tableSideBorder">Amount of Failed Autotests</th>';
    echo '<th colspan="3" class="tableBottomBorder tableSideBorder">Amount of Builds</th>';
    echo '</tr>';
    echo '<tr class="tableBottomBorder">';
    echo '<td></td>';
    echo '<td class="tableLeftBorder tableCellCentered">Result</td>';
    echo '<td class="tableCellCentered">Force success</td>';
    echo '<td class="tableCellCentered">Insignificant</td>';
    echo '<td class="tableLeftBorder tableCellCentered">Significant</td>';
    echo '<td class="tableCellCentered">Insignificant</td>';
    echo '<td class="tableLeftBorder tableCellCentered">Failed</td>';
    echo '<td class="tableCellCentered">Successful</td>';
    echo '<td class="tableRightBorder tableCellCentered">Total</td>';
    echo '</tr>';
    for ($i=0; $i<$numberOfRows; $i++) {                                    // Loop to print Confs
        if ($useMysqli)
            $resultRow = mysqli_fetch_row($result);
        else
            $resultRow = mysql_fetch_row($result);
        if ($i % 2 == 0)
            echo '<tr>';
        else
            echo '<tr class="tableBackgroundColored">';

       /* Configuration name */
        $confName = $resultRow[$dbColumnCfgCfg];
        echo '<td><a href="javascript:void(0);" onclick="filterConf(\'' . $confName
            . '\')">' . $confName . '</a></td>';

        /* Latest Build: Result */
        $fontColorClass = "fontColorBlack";
        if ($resultRow[$dbColumnCfgResult] == "SUCCESS")
            $fontColorClass = "fontColorGreen";
        if ($resultRow[$dbColumnCfgResult] == "FAILURE")
            $fontColorClass = "fontColorRed";
        echo '<td class="tableLeftBorder tableCellCentered ' . $fontColorClass . '">' . $resultRow[$dbColumnCfgResult] . '</td>';

        /* Latest Build: Force success and Insignificant */
        if ($resultRow[$dbColumnCfgForceSuccess] == 1) {
            echo '<td class="tableCellCentered">' . FLAGON . '</td>';
            $latestForceSuccessCount++;
        } else {
            echo '<td class="tableCellCentered">' . FLAGOFF . '</td>';
        }
        if ($resultRow[$dbColumnCfgInsignificant] == 1) {
            echo '<td class="tableCellCentered">' . FLAGON . '</td>';
            $latestInsignCount++;
        } else {
            echo '<td class="tableCellCentered">' . FLAGOFF . '</td>';
        }

        /* Latest Build: Number of failed significant/insignificant autotests */
        $sql = "SELECT 'significant', COUNT(name) AS 'count'
                FROM test
                WHERE insignificant=0 AND $projectFilter $buildFilter AND cfg=\"$confName\"
                UNION
                SELECT 'insignificant', COUNT(name) AS 'count'
                FROM test
                WHERE insignificant=1 AND $projectFilter $buildFilter AND cfg=\"$confName\"";       // Will return two rows
        if ($useMysqli) {
            $result2 = mysqli_query($conn, $sql);
            $numberOfRows2 = mysqli_num_rows($result2);
        } else {
            $result2 = mysql_query($sql) or die (mysql_error());
            $numberOfRows2 = mysql_num_rows($result2);
        }
        $confSignAutotestCount = 0;
        $confInsignAutotestCount = 0;
        for ($j=0; $j<$numberOfRows2; $j++) {                               // Loop to print Conf success rate (two rows)
            if ($useMysqli)
                $resultRow2 = mysqli_fetch_row($result2);
            else
                $resultRow2 = mysql_fetch_row($result2);
            if ($resultRow2[0] == "significant")
                $confSignAutotestCount = $resultRow2[1];
            if ($resultRow2[0] == "insignificant")
                $confInsignAutotestCount = $resultRow2[1];
        }
        if ($confSignAutotestCount > 0)
            echo '<td class="tableLeftBorder tableCellCentered">' . $confSignAutotestCount . '</td>';
        else
            echo '<td class="tableLeftBorder tableCellCentered">-</td>';
        if ($confInsignAutotestCount > 0)
            echo '<td class="tableCellCentered">' . $confInsignAutotestCount . '</td>';
        else
            echo '<td class="tableCellCentered">-</td>';
        $latestFailingSignAutotestCount = $latestFailingSignAutotestCount + $confSignAutotestCount;
        $latestFailingInsignAutotestCount = $latestFailingInsignAutotestCount + $confInsignAutotestCount;
        if ($useMysqli)
            mysqli_free_result($result2);                                   // Free result set

        /* All Builds data */
        $timescopeFilter = "";
        if ($timescaleType == "Since")
            $timescopeFilter = " AND timestamp>=\"$timescaleValue\"";
        $sql = "SELECT result, COUNT(result) AS count
                FROM cfg
                WHERE $projectFilter AND cfg=\"$confName\" $timescopeFilter
                GROUP BY result
                UNION
                SELECT 'Total', COUNT(cfg) AS count
                FROM cfg
                WHERE $projectFilter AND cfg=\"$confName\" $timescopeFilter";    // Will return up to five rows (results ABORTED,FAILURE,SUCCESS,undef and the Total)
        if ($useMysqli) {
            $result2 = mysqli_query($conn, $sql);
            $numberOfRows2 = mysqli_num_rows($result2);
        } else {
            $result2 = mysql_query($sql) or die (mysql_error());
            $numberOfRows2 = mysql_num_rows($result2);
        }
        $confFailureCount = 0;
        $confSuccessCount = 0;
        $confTotalCount = 0;
        for ($j=0; $j<$numberOfRows2; $j++) {                               // Loop to print Conf success rate (up to five rows)
            if ($useMysqli)
                $resultRow2 = mysqli_fetch_row($result2);
            else
                $resultRow2 = mysql_fetch_row($result2);
            if ($resultRow2[0] == "FAILURE")
                $confFailureCount = $resultRow2[1];
            if ($resultRow2[0] == "SUCCESS")
                $confSuccessCount = $resultRow2[1];
            if ($resultRow2[0] == "Total")
                $confTotalCount = $resultRow2[1];
        }
        if ($confFailureCount > 0)
            echo '<td class="tableLeftBorder tableCellAlignRight">' . $confFailureCount . ' (' . round(100*$confFailureCount/$confTotalCount,0) . '%)' . '</td>';
        else
            echo '<td class="tableLeftBorder tableCellCentered">-</td>';
        if ($confSuccessCount > 0)
            echo '<td class="tableCellAlignRight">' . $confSuccessCount . ' (' . round(100*$confSuccessCount/$confTotalCount,0) . '%)' . '</td>';
        else
            echo '<td class="tableCellCentered">-</td>';
        if ($confTotalCount > 0)
            echo '<td class="tableRightBorder tableCellAlignRight">' . $confTotalCount . '</td>';
        else
            echo '<td class="tableRightBorder tableCellCentered">-</td>';
        $allFailureCount = $allFailureCount + $confFailureCount;
        $allSuccessCount = $allSuccessCount + $confSuccessCount;
        $allTotalCount = $allTotalCount + $confTotalCount;
        if ($useMysqli)
            mysqli_free_result($result2);                                   // Free result set

        echo "</tr>";
    }
    $printedConfs = $numberOfRows;

    /* Print Totals summary row */
    echo '<tr>';
    echo '<td class="tableRightBorder tableTopBorder">total (' . $printedConfs . ')</td>';
    echo '<td class="tableLeftBorder tableTopBorder"></td>';
    echo '<td class="tableTopBorder tableCellCentered">' . $latestForceSuccessCount . '</td>';
    echo '<td class="tableRightBorder tableTopBorder tableCellCentered">' . $latestInsignCount . '</td>';
    echo '<td class="tableLeftBorder tableTopBorder tableCellCentered">' . $latestFailingSignAutotestCount . '</td>';
    echo '<td class="tableRightBorder tableTopBorder tableCellCentered">' . $latestFailingInsignAutotestCount . '</td>';
    if ($allFailureCount > 0)
        echo '<td class="tableLeftBorder tableTopBorder tableCellAlignRight">' . $allFailureCount . ' (' . round(100*$allFailureCount/$allTotalCount,0) . '%)</td>';
    else
        echo '<td class="tableLeftBorder tableTopBorder tableCellCentered">-</td>';
    if ($allSuccessCount > 0)
        echo '<td class="tableTopBorder tableCellAlignRight">' . $allSuccessCount . ' (' . round(100*$allSuccessCount/$allTotalCount,0) . '%)</td>';
    else
        echo '<td class="tableTopBorder tableCellCentered">-</td>';
    if ($allTotalCount > 0)
        echo '<td class="tableRightBorder tableTopBorder tableCellAlignRight">' . $allTotalCount . '</td>';
    else
        echo '<td class="tableRightBorder tableTopBorder tableCellCentered">-</td>';
    echo '</tr>';

    echo "</table>";
} else {
    echo "(no items)<br/>";
}
$timeRead = microtime(true);

if ($useMysqli)
    mysqli_free_result($result);                                            // Free result set

/* Elapsed time */
if ($showElapsedTime) {
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
} else {
    echo "<br>";
}

?>