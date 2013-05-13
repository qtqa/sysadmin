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
    // $projectFilter
    // $confFilter
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
$sql = "SELECT cfg,result,project,build_number
        FROM cfg
        WHERE $projectFilter $buildFilter $confFilter
        ORDER BY result,cfg";
define("DBCOLUMNCFGCFG", 0);
define("DBCOLUMNCFGRESULT", 1);
define("DBCOLUMNCFGPROJECT", 2);
define("DBCOLUMNCFGBUILD", 3);
if ($useMysqli) {
    $result = mysqli_query($conn, $sql);
    $numberOfRows = mysqli_num_rows($result);
} else {
    $result = mysql_query($sql) or die (mysql_error());
    $numberOfRows = mysql_num_rows($result);
}

/* Print the data */
if ($project == "All")
    echo '<b>Configurations</b><br/>';
else
    echo '<b>Configurations in latest Build</b><br/>';
if ($numberOfRows > 0) {
    echo '<table>';
    echo '<tr class="tableBottomBorder">';
    echo '<th></th>';                                                       // Titles
    echo '<th><br/><br/>Latest<br/>result</th>';
    echo '<th><br/><br/>Force<br/>success</th>';
    echo '<th class="tableLeftBorder"><br/><br/>Failed<br/>Builds</th>';
    echo '<th><br/><br/>Successed<br/>Builds</th>';
    echo '<th><br/><br/>All<br/>Builds</th>';
    echo '<th class="tableLeftBorder">Failed<br/>signif.<br/>autotests<br/>in latest</th>';
    echo '<th>Failed<br/>insignif.<br/>autotests<br/>in latest</th>';
    echo '<th class="tableLeftBorder">All<br/>insignif.<br/>autotests<br/>in latest</th>';
    echo '<th><br/>All<br/>autotests<br/>in latest</th>';
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

        /* Project name and Build number (if listed) */
        if ($project == "All") {                                            // If listed by Project (this file to be called for each Project)
            echo '<td>' . $resultRow[DBCOLUMNCFGPROJECT] . '</td>';
            echo '<td>' . $resultRow[DBCOLUMNCFGBUILD] . '</td>';
        }

       /* Configuration name */
       $confName = $resultRow[DBCOLUMNCFGCFG];

        /* Latest Build result */
        echo '<td><a href="javascript:void(0);" onclick="filterConf(\'' . $confName
            . '\')">' . $confName . '</a></td>';
        $fontColorClass = "fontColorBlack";
        if ($resultRow[DBCOLUMNCFGRESULT] == "SUCCESS")
            $fontColorClass = "fontColorGreen";
        if ($resultRow[DBCOLUMNCFGRESULT] == "FAILURE")
            $fontColorClass = "fontColorRed";
        echo '<td class="' . $fontColorClass . '">' . $resultRow[DBCOLUMNCFGRESULT] . '</td>';

        /* Force success - Data not yet available in db */
        echo '<td class="tableCellCentered">(n/a)</td>';

        /* Build statistics */
        $sql = "SELECT result, COUNT(result) AS count
                FROM cfg
                WHERE $projectFilter AND cfg=\"$confName\"
                GROUP BY result
                UNION
                SELECT 'Total', COUNT(cfg) AS count
                FROM cfg
                WHERE $projectFilter AND cfg=\"$confName\"";
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
        for ($j=0; $j<$numberOfRows2; $j++) {                               // Loop to print Conf success rate
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
            echo '<td>' . $confFailureCount . ' (' . round(100*$confFailureCount/$confTotalCount,0) . '%)' . '</td>';
        else
            echo '<td>-</td>';
        if ($confSuccessCount > 0)
            echo '<td>' . $confSuccessCount . ' (' . round(100*$confSuccessCount/$confTotalCount,0) . '%)' . '</td>';
        else
            echo '<td>-</td>';
        if ($confTotalCount > 0)
            echo '<td>' . $confTotalCount . '</td>';
        else
            echo '<td>-</td>';
        if ($useMysqli)
            mysqli_free_result($result2);                                   // Free result set

        /* Number of failed significant/insignificant autotests */
        $sql = "SELECT 'significant', COUNT(name) AS 'count'
                FROM test
                WHERE insignificant=0 AND $projectFilter $buildFilter AND cfg=\"$confName\"
                UNION
                SELECT 'insignificant', COUNT(name) AS 'count'
                FROM test
                WHERE insignificant=1 AND $projectFilter $buildFilter AND cfg=\"$confName\"";
        if ($useMysqli) {
            $result2 = mysqli_query($conn, $sql);
            $numberOfRows2 = mysqli_num_rows($result2);
        } else {
            $result2 = mysql_query($sql) or die (mysql_error());
            $numberOfRows2 = mysql_num_rows($result2);
        }
        $confSignificantCountLatestBuild = 0;
        $confInsignificantCountLatestBuild = 0;
        for ($j=0; $j<$numberOfRows2; $j++) {                               // Loop to print Conf success rate
            if ($useMysqli)
                $resultRow2 = mysqli_fetch_row($result2);
            else
                $resultRow2 = mysql_fetch_row($result2);
            if ($resultRow2[0] == "significant")
                $confSignificantCountLatestBuild = $resultRow2[1];
            if ($resultRow2[0] == "insignificant")
                $confInsignificantCountLatestBuild = $resultRow2[1];
        }
        if ($confSignificantCountLatestBuild > 0)
            echo '<td class="tableCellCentered">' . $confSignificantCountLatestBuild . '</td>';
        else
            echo '<td class="tableCellCentered">-</td>';
        if ($confInsignificantCountLatestBuild > 0)
            echo '<td class="tableCellCentered">' . $confInsignificantCountLatestBuild . '</td>';
        else
            echo '<td class="tableCellCentered">-</td>';
        if ($useMysqli)
            mysqli_free_result($result2);                                   // Free result set

        /* Insignificant autotests vs. All autotests */
        echo '<td class="tableCellCentered">(n/a)</td>';                    // All insignificant autotests in latest Build; Data not yet available in db
        echo '<td class="tableCellCentered">(n/a)</td>';                    // All autotests in latest Build; Data not yet available in db

        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "(no items)<br/>";
}
$timeRead = microtime(true);

if ($useMysqli)
    mysqli_free_result($result);                                            // Free result set

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