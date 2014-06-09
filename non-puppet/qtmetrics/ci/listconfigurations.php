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
    // $build
    // $buildNumber        (in listgeneraldata.php)
    // $timescaleType
    // $timescaleValue
    // $projectFilter
    // $confFilter
    // $showElapsedTime
    // $timeEnd
    // $round

$timeStartThis = $timeEnd;                          // Start where previous step ended

/* Read data from database */
if ($build == 0) {                          // Show the latest build ...
    $sql = cleanSqlString(
           "SELECT cfg, result, forcesuccess, insignificant
            FROM cfg_latest
            WHERE $confFilter AND $projectFilter
            ORDER BY result, cfg");
} else {                                    // ... or the selected build
    $sql = cleanSqlString(
           "SELECT cfg, result, forcesuccess, insignificant
            FROM cfg
            WHERE $confFilter AND $projectFilter AND build_number=$buildNumber
            ORDER BY result, cfg");
}
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
    $buildForceSuccessCount = 0;
    $buildInsignCount = 0;
    $buildFailingSignAutotestCount = 0;
    $buildFailingInsignAutotestCount = 0;
    $allFailureCount = 0;
    $allSuccessCount = 0;
    $allTotalCount = 0;

    echo '<div class="metricsTitle">';
    echo '<b>Configurations</b>';
    echo '</div>';

    /* Titles */
    echo '<table class="fontSmall">';
    echo '<tr>';
    echo '<th></th>';
    echo '<td colspan="5" class="tableBottomBorder tableSideBorder tableCellCentered tableCellBuildSelected">';
    if ($build == 0)                                // Show the latest build ...
        echo 'LATEST BUILD';
    else                                            // ... or the selected build
        echo 'BUILD ' . $buildNumber;
    echo '</td>';
    if ($round == 1) {
        if ($timescaleType == "All")
            echo '<td colspan="3" class="tableBottomBorder tableSideBorder tableCellCentered timescaleAll">';
        if ($timescaleType == "Since")
            echo '<td colspan="3" class="tableBottomBorder tableSideBorder tableCellCentered timescaleSince">';
        echo 'Loading All Builds <span class="loading"><span>.</span><span>.</span><span>.</span></span></td>';
    } else {
        if ($timescaleType == "All")
            echo '<td colspan="3" class="tableBottomBorder tableSideBorder tableCellCentered timescaleAll"><b>ALL BUILDS (SINCE ' . $_SESSION['minBuildDate'] . ')</b>';
        if ($timescaleType == "Since")
            echo '<td colspan="3" class="tableBottomBorder tableSideBorder tableCellCentered timescaleSince">ALL BUILDS SINCE ' . $timescaleValue . '</td>';
    }
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

        $timeReadLatestStart = microtime(true);

        /* Build result */
        $fontColorClass = "fontColorBlack";
        if ($resultRow[$dbColumnCfgResult] == "SUCCESS")
            $fontColorClass = "fontColorGreen";
        if ($resultRow[$dbColumnCfgResult] == "FAILURE")
            $fontColorClass = "fontColorRed";
        echo '<td class="tableLeftBorder tableCellCentered ' . $fontColorClass . '">' . $resultRow[$dbColumnCfgResult] . '</td>';

        /* Build force success and Insignificant */
        if ($resultRow[$dbColumnCfgForceSuccess] == 1) {
            echo '<td class="tableCellCentered">' . FLAGON . '</td>';
            $buildForceSuccessCount++;
        } else {
            echo '<td class="tableCellCentered">' . FLAGOFF . '</td>';
        }
        if ($resultRow[$dbColumnCfgInsignificant] == 1) {
            echo '<td class="tableCellCentered">' . FLAGON . '</td>';
            $buildInsignCount++;
        } else {
            echo '<td class="tableCellCentered">' . FLAGOFF . '</td>';
        }

        /* Build number of failed significant/insignificant autotests */
        if ($build == 0) {                          // Show the latest build ...
            $sql = cleanSqlString(
                   "SELECT 'significant', COUNT(name) AS 'count'
                    FROM test_latest
                    WHERE $projectFilter AND cfg=\"$confName\" AND insignificant=0
                    UNION
                    SELECT 'insignificant', COUNT(name) AS 'count'
                    FROM test_latest
                    WHERE $projectFilter AND cfg=\"$confName\" AND insignificant=1");                                   // Will return two rows
        } else {                                    // ... or the selected build
            $sql = cleanSqlString(
                   "SELECT 'significant', COUNT(name) AS 'count'
                    FROM test
                    WHERE $projectFilter AND build_number=$buildNumber AND cfg=\"$confName\" AND insignificant=0
                    UNION
                    SELECT 'insignificant', COUNT(name) AS 'count'
                    FROM test
                    WHERE $projectFilter AND build_number=$buildNumber AND cfg=\"$confName\" AND insignificant=1");     // Will return two rows
        }
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
        $buildFailingSignAutotestCount = $buildFailingSignAutotestCount + $confSignAutotestCount;
        $buildFailingInsignAutotestCount = $buildFailingInsignAutotestCount + $confInsignAutotestCount;
        if ($useMysqli)
            mysqli_free_result($result2);                                   // Free result set

        $timeReadLatestEnd = microtime(true);
        $timeReadLatest = $timeReadLatest + round($timeReadLatestEnd - $timeReadLatestStart, 4);
        $timeReadAllStart = microtime(true);

        /* All Builds data */
        if ($round == 1) {
            $confFailureCount = -1;
            $confSuccessCount = -1;
            $confTotalCount = -1;
        } else {
            $timescopeFilter = "";
            if ($timescaleType == "Since")
                $timescopeFilter = "AND timestamp>=\"$timescaleValue\"";
            $sql = cleanSqlString(
                   "SELECT result, COUNT(result) AS count
                    FROM cfg
                    WHERE cfg=\"$confName\" AND $projectFilter $timescopeFilter
                    GROUP BY result
                    UNION
                    SELECT 'Total', COUNT(cfg) AS count
                    FROM cfg
                    WHERE cfg=\"$confName\" AND $projectFilter $timescopeFilter");   // Will return up to five rows (results ABORTED,FAILURE,SUCCESS,undef and the Total)
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
            for ($j=0; $j<$numberOfRows2; $j++) {                           // Loop to print Conf success rate (up to five rows)
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
        }
        if ($confFailureCount > 0)
            echo '<td class="tableLeftBorder tableCellAlignRight">' . $confFailureCount . ' (' . round(100*$confFailureCount/$confTotalCount,0) . '%)' . '</td>';
        if ($confFailureCount == 0)
            echo '<td class="tableLeftBorder tableCellCentered">-</td>';
        if ($confFailureCount == -1)
            echo '<td class="tableLeftBorder tableCellCentered"></td>';
        if ($confSuccessCount > 0)
            echo '<td class="tableCellAlignRight">' . $confSuccessCount . ' (' . round(100*$confSuccessCount/$confTotalCount,0) . '%)' . '</td>';
        if ($confSuccessCount == 0)
            echo '<td class="tableCellCentered">-</td>';
        if ($confSuccessCount == -1)
            echo '<td class="tableCellCentered"></td>';
        if ($confTotalCount > 0)
            echo '<td class="tableRightBorder tableCellAlignRight">' . $confTotalCount . '</td>';
        if ($confTotalCount == 0)
            echo '<td class="tableRightBorder tableCellCentered">-</td>';
        if ($confTotalCount == -1)
            echo '<td class="tableRightBorder tableCellCentered"></td>';
        $allFailureCount = $allFailureCount + $confFailureCount;
        $allSuccessCount = $allSuccessCount + $confSuccessCount;
        $allTotalCount = $allTotalCount + $confTotalCount;
        $timeReadAllEnd = microtime(true);
        $timeReadAll = $timeReadAll + round($timeReadAllEnd - $timeReadAllStart, 4);
        if ($useMysqli)
            mysqli_free_result($result2);                                   // Free result set

        echo "</tr>";
    }
    $printedConfs = $numberOfRows;

    /* Print Totals summary row */
    echo '<tr>';
    echo '<td class="tableRightBorder tableTopBorder">total (' . $printedConfs . ')</td>';
    echo '<td class="tableLeftBorder tableTopBorder"></td>';
    echo '<td class="tableTopBorder tableCellCentered">' . $buildForceSuccessCount . '</td>';
    echo '<td class="tableRightBorder tableTopBorder tableCellCentered">' . $buildInsignCount . '</td>';
    echo '<td class="tableLeftBorder tableTopBorder tableCellCentered">' . $buildFailingSignAutotestCount . '</td>';
    echo '<td class="tableRightBorder tableTopBorder tableCellCentered">' . $buildFailingInsignAutotestCount . '</td>';
    if ($round == 1) {
            echo '<td class="tableLeftBorder tableTopBorder tableCellCentered"></td>';
            echo '<td class="tableTopBorder tableCellCentered"></td>';
            echo '<td class="tableRightBorder tableTopBorder tableCellCentered"></td>';
    } else {
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
    }
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
    $timeDbRead = round($timeRead - $timeStartThis, 4);
    $time = round($timeEnd - $timeStartThis, 4);
    echo "<div class=\"elapdedTime\">";
    echo "<ul><li>";
    echo "<b>Total time:</b>&nbsp $time s (database read and calculation time; round $round)<br>";
    echo "Latest builds: $timeReadLatest s<br>";
    if ($round == 2)
        echo "All builds:&nbsp&nbsp&nbsp&nbsp&nbsp $timeReadAll s<br>";
    echo "</li></ul>";
    echo "</div>";
} else {
    echo "<br>";
}

?>