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
    // All Project session variables: $_SESSION['arrayProject...']
    // $timescaleType
    // $timescaleValue
    // $timeStart
    // $timeConnect
    // $round

$i = 0;
echo '<table class="fontSmall">';

/* Titles */
echo '<tr>';
echo '<th></th>';
echo '<th colspan="8" class="tableBottomBorder tableSideBorder">LATEST BUILD</th>';
if ($timescaleType == "All")
    echo '<th colspan="3" class="tableBottomBorder tableSideBorder">ALL BUILDS (SINCE ' . $_SESSION['minBuildDate'] . ')</th>';
if ($timescaleType == "Since") {
    if ($round == 1)
        echo '<th colspan="3" class="tableBottomBorder tableSideBorder">Loading All Builds <span class="loading"><span>.</span><span>.</span><span>.</span></span></th>';
    else
        echo '<th colspan="3" class="tableBottomBorder tableSideBorder">ALL BUILDS SINCE ' . $timescaleValue . '</th>';
}
echo '</tr>';
echo '<tr>';
echo '<th></th>';
echo '<th colspan="3" class="tableBottomBorder tableSideBorder">Build Info</th>';
echo '<th colspan="2" class="tableBottomBorder tableSideBorder">Amount of Failed Autotests</th>';
echo '<th colspan="3" class="tableBottomBorder tableSideBorder">Amount of Configurations</th>';
echo '<th colspan="3" class="tableBottomBorder tableSideBorder">Amount of Builds</th>';
echo '</tr>';
echo '<tr class="tableBottomBorder">';
echo '<td></td>';
echo '<td class="tableLeftBorder tableCellCentered">ID</td>';
echo '<td class="tableCellCentered">Result</td>';
echo '<td class="tableCellCentered">Date</td>';
echo '<td class="tableLeftBorder tableCellCentered">Significant</td>';
echo '<td class="tableCellCentered">Insignificant</td>';
echo '<td class="tableLeftBorder tableCellCentered">Force success</td>';
echo '<td class="tableCellCentered">Insignificant</td>';
echo '<td class="tableCellCentered">Total</td>';
echo '<td class="tableLeftBorder tableCellCentered">Failed</td>';
echo '<td class="tableCellCentered">Successful</td>';
echo '<td class="tableRightBorder tableCellCentered">Total</td>';
echo '</tr>';

/* Counters for printing totals summary row */
$listCutMode = FALSE;
$printedProjects = 0;
$latestFailingSignAutotestCount = 0;
$latestFailingInsignAutotestCount = 0;
$latestForceSuccessConfCount = 0;
$latestInsignConfCount = 0;
$latestTotalConfCount = 0;
$allFailureCount = 0;
$allSuccessCount = 0;
$allTotalCount = 0;

/* Read the Build statistics for each Project in filtered Timescope (session variables already include the statistics for all Builds in the database) */
if ($round == 2 AND $timescaleType == "Since") {
    $arrayProjectBuildSinceCount = array();
    $arrayProjectBuildSinceCountSuccess = array();
    $arrayProjectBuildSinceCountFailure = array();
    foreach ($_SESSION['arrayProjectName'] as $key=>$value) {                        // Loop the Projects
        $sql = "SELECT 'SUCCESS', COUNT(result) AS 'count'
                FROM ci
                WHERE project=\"$value\" AND result=\"SUCCESS\" AND timestamp>=\"$timescaleValue\"
                UNION
                SELECT 'FAILURE', COUNT(result) AS 'count'
                FROM ci
                WHERE project=\"$value\" AND result=\"FAILURE\" AND timestamp>=\"$timescaleValue\"
                UNION
                SELECT 'Total', COUNT(result) AS 'count'
                FROM ci
                WHERE project=\"$value\" AND timestamp>=\"$timescaleValue\"";        // Will return three rows
        if ($useMysqli) {
            $result = mysqli_query($conn, $sql);
            $numberOfRows = mysqli_num_rows($result);
        } else {
            $selectdb="USE $db";
            $result = mysql_query($selectdb) or die (mysql_error());
            $result = mysql_query($sql) or die (mysql_error());
            $numberOfRows = mysql_num_rows($result);
        }
        for ($j=0; $j<$numberOfRows; $j++) {                                         // Loop the counts (three rows)
            if ($useMysqli)
                $resultRow = mysqli_fetch_row($result);
            else
                $resultRow = mysql_fetch_row($result);
            if ($resultRow[0] == "SUCCESS")
                $arrayProjectBuildSinceCountSuccess[] = $resultRow[1];
            if ($resultRow[0] == "FAILURE")
                $arrayProjectBuildSinceCountFailure[] = $resultRow[1];
            if ($resultRow[0] == "Total")
                $arrayProjectBuildSinceCount[] = $resultRow[1];
        }
    }
    if ($useMysqli)
        mysqli_free_result($result);                                                 // Free result set
}
$timeBuildStats = microtime(true);

/* Print Project data from the session variables */
foreach ($_SESSION['arrayProjectName'] as $key=>$value) {

    /* When Timescale filtered, and the latest Build is not within the Timescale ... */
    if ($timescaleType == "Since")
        if ($_SESSION['arrayProjectBuildLatestTimestamp'][$key] < $timescaleValue)
            continue;                                                         // ... skip to the next Project (in the foreach loop)

    /* Highlight every other row for better readability */
    if ($i % 2 == 0)
        echo '<tr>';
    else
        echo '<tr class="tableBackgroundColored">';

    /* Project name */
    echo '<td><a href="javascript:void(0);" onclick="filterProject(\'' . $value . '\')">' . $value . '</a></td>';

    /* Latest Build number and result */
    echo '<td class="tableLeftBorder">' . $_SESSION['arrayProjectBuildLatest'][$key] . '</td>';
    $fontColorClass = "fontColorBlack";
    if ($_SESSION['arrayProjectBuildLatestResult'][$key] == "SUCCESS")
        $fontColorClass = "fontColorGreen";
    if ($_SESSION['arrayProjectBuildLatestResult'][$key] == "FAILURE")
        $fontColorClass = "fontColorRed";
    echo '<td class="' . $fontColorClass . '">' . $_SESSION['arrayProjectBuildLatestResult'][$key] . '</td>';
    $date = strstr($_SESSION['arrayProjectBuildLatestTimestamp'][$key], ' ', TRUE);
    echo '<td>' . $date . '</td>';

    /* Latest Build: Number of failed significant/insignificant autotests */
    $count = $_SESSION['arrayProjectBuildLatestSignificantCount'][$key];
    if ($count > 0)
        echo '<td class="tableLeftBorder tableCellCentered">' . $count . '</td>';
    else
        echo '<td class="tableLeftBorder tableCellCentered">-</td>';
    $latestFailingSignAutotestCount = $latestFailingSignAutotestCount + $count;
    $count = $_SESSION['arrayProjectBuildLatestInsignificantCount'][$key];
    if ($count > 0)
        echo '<td class="tableCellCentered">' . $count . '</td>';
    else
        echo '<td class="tableCellCentered">-</td>';
    $latestFailingInsignAutotestCount = $latestFailingInsignAutotestCount + $count;

    /* Latest Build: Force success and insignificant Configurations vs. All Configurations */
    $count = $_SESSION['arrayProjectBuildLatestConfCountForceSuccess'][$key];
    if ($count > 0)
        echo '<td class="tableLeftBorder tableCellCentered">' . $count . '</td>';
    else
        echo '<td class="tableLeftBorder tableCellCentered">-</td>';
    $latestForceSuccessConfCount = $latestForceSuccessConfCount + $count;
    $count = $_SESSION['arrayProjectBuildLatestConfCountInsignificant'][$key];
    if ($count > 0)
        echo '<td class="tableCellCentered">' . $count . '</td>';
    else
        echo '<td class="tableCellCentered">-</td>';
    $latestInsignConfCount = $latestInsignConfCount + $count;
    $count = $_SESSION['arrayProjectBuildLatestConfCount'][$key];
    if ($count > 0)
        echo '<td class="tableCellCentered">' . $count . '</td>';
    else
        echo '<td class="tableCellCentered">-</td>';
    $latestTotalConfCount = $latestTotalConfCount + $count;

    /* All Builds: Failure count and ratio */
    if ($timescaleType == "All") {                                         // For all Builds: read from the session variables
        $count = $_SESSION['arrayProjectBuildCountFailure'][$key];
        $ratio = round(100*$count/$_SESSION['arrayProjectBuildCount'][$key],0);
    }
    if ($timescaleType == "Since") {                                       // With Timescope: read from the arrays calculated above
        if ($round == 1) {
            $count = -1;
        } else {
            $count = $arrayProjectBuildSinceCountFailure[$key];
            $ratio = round(100*$count/$arrayProjectBuildSinceCount[$key],0);
        }
    }
    if ($count > 0)
        echo '<td class="tableLeftBorder tableCellAlignRight">' . $count . ' (' . $ratio . '%)</td>';
    if ($count == 0)
        echo '<td class="tableLeftBorder tableCellCentered">-</td>';
    if ($count == -1)
        echo '<td class="tableLeftBorder tableCellCentered"></td>';
    $allFailureCount = $allFailureCount + $count;

    /* All Builds: Success count and ratio */
    if ($timescaleType == "All") {                                         // For all Builds: read from the session variables
        $count = $_SESSION['arrayProjectBuildCountSuccess'][$key];
        $ratio = round(100*$count/$_SESSION['arrayProjectBuildCount'][$key],0);
    }
    if ($timescaleType == "Since") {                                       // With Timescope: read from the arrays calculated above
        if ($round == 1) {
            $count = -1;
        } else {
            $count = $arrayProjectBuildSinceCountSuccess[$key];
            $ratio = round(100*$count/$arrayProjectBuildSinceCount[$key],0);
        }
    }
    if ($count > 0)
        echo '<td class="tableCellAlignRight">' . $count . ' (' . $ratio . '%)</td>';
    if ($count == 0)
        echo '<td class="tableCellCentered">-</td>';
    if ($count == -1)
        echo '<td class="tableCellCentered"></td>';
    $allSuccessCount = $allSuccessCount + $count;

    /* All Builds: Total count */
    if ($timescaleType == "All")                                          // For all Builds: read from the session variables
        $count = $_SESSION['arrayProjectBuildCount'][$key];
    if ($timescaleType == "Since")                                        // With Timescope: read from the arrays calculated above
        if ($round == 1) {
            $count = -1;
        } else {
            $count = $arrayProjectBuildSinceCount[$key];
        }
    if ($count > 0)
        echo '<td class="tableRightBorder tableCellAlignRight">' . $count . '</td>';
    if ($count == 0)
        echo '<td class="tableRightBorder tableCellCentered">-</td>';
    if ($count == -1)
        echo '<td class="tableRightBorder tableCellCentered"></td>';
    $allTotalCount = $allTotalCount + $count;

    echo "</tr>";
    $i++;
    if ($i > 12 AND !isset($_SESSION['projectDashboardShowFullList'])) {  // List cut mode: By default show only n items in the list to leave room for possible other metrics boxes
        $listCutMode = TRUE;
        break;
    }
}
$printedProjects = $i;
$timeProjectData = microtime(true);

/* Print Totals summary row */
if ($listCutMode == FALSE) {
    echo '<tr>';
    echo '<td class="tableRightBorder tableTopBorder">total (' . $printedProjects . ')</td>';
    echo '<td class="tableLeftBorder tableTopBorder"></td>';
    echo '<td class="tableTopBorder"></td>';
    echo '<td class="tableRightBorder tableTopBorder"></td>';
    echo '<td class="tableLeftBorder tableTopBorder tableCellCentered">' . $latestFailingSignAutotestCount . '</td>';
    echo '<td class="tableRightBorder tableTopBorder tableCellCentered">' . $latestFailingInsignAutotestCount . '</td>';
    echo '<td class="tableLeftBorder tableTopBorder tableCellCentered">' . $latestForceSuccessConfCount . '</td>';
    echo '<td class="tableTopBorder tableCellCentered">' . $latestInsignConfCount . '</td>';
    echo '<td class="tableRightBorder tableTopBorder tableCellCentered">' . $latestTotalConfCount . '</td>';
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
}

echo "</table>";

if ($round == 2 AND !isset($_SESSION['projectDashboardShowFullList'])) {
    echo '<br/><a href="javascript:void(0);" onclick="clearProjectFilters()">Show full list...</a><br/><br/>';   // List cut mode: If only first n items shown, add a link to see all
    $_SESSION['projectDashboardShowFullList'] = TRUE;                                                            // List cut mode: After refreshing the metrics box, show all items instead (set below to return the default 'cut mode')
}

/* Elapsed time */
if ($showElapsedTime) {
    $timeEnd = microtime(true);
    $timeDbConnect = round($timeConnect - $timeStart, 4);
    $timeBuilds = round($timeBuildStats - $timeConnect, 4);
    $timeProjects = round($timeProjectData - $timeBuildStats, 4);
    $time = round($timeEnd - $timeStart, 4);
    echo "<div class=\"elapdedTime\">";
    echo "<ul><li>";
    echo "<b>Total time:</b>&nbsp $time s (round $round)<br>";
    echo "Database connect time: $timeDbConnect s, read build data: $timeBuilds s, print project data: $timeProjects s";
    echo "</li></ul>";
    echo "</div>";
}

?>