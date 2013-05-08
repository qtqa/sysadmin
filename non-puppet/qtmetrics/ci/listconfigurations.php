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
$timeRead = microtime(true);

/* Print the data */
if ($project == "All")
    echo '<b>Configurations</b><br/><br/>';
else
    echo '<b>Configurations in latest Build</b><br/><br/>';
if ($numberOfRows > 0) {
    echo "<table>";
    for ($i=0; $i<$numberOfRows; $i++) {                                    // Loop to print confs
        if ($useMysqli)
            $resultRow = mysqli_fetch_row($result);
        else
            $resultRow = mysql_fetch_row($result);
        echo "<tr>";
        if ($project == "All") {                                            // List by Project
            echo '<td>' . $resultRow[DBCOLUMNCFGPROJECT] . '</td>';
            echo '<td>' . $resultRow[DBCOLUMNCFGBUILD] . '</td>';
        }
        echo '<td><a href="javascript:void(0);" onclick="filterConf(\'' . $resultRow[DBCOLUMNCFGCFG]
            . '\')">' . $resultRow[DBCOLUMNCFGCFG] . '</a></td>';
        $fontColorClass = "fontColorBlack";
        if ($resultRow[DBCOLUMNCFGRESULT] == "SUCCESS")
            $fontColorClass = "fontColorGreen";
        if ($resultRow[DBCOLUMNCFGRESULT] == "FAILURE")
            $fontColorClass = "fontColorRed";
        echo '<td class="' . $fontColorClass . '">' . $resultRow[DBCOLUMNCFGRESULT] . '<td>';
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "(no items)<br/>";
}

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