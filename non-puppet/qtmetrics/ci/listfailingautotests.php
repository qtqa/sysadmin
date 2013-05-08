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
    // $_SESSION['arrayProjectBuildScopeMin']
    // $project
    // $projectFilter
    // $confFilter
    // $timeStart
    // $timeConnect

/* Read data from database */
$sql = "SELECT name,project,build_number,cfg
        FROM test
        WHERE insignificant=0 $projectFilter $confFilter
        ORDER BY name, project, build_number DESC";
define("DBCOLUMNTESTNAME", 0);
define("DBCOLUMNTESTPROJECT", 1);
define("DBCOLUMNTESTBUILD", 2);
define("DBCOLUMNTESTCFG", 3);
if ($useMysqli) {
    $result = mysqli_query($conn, $sql);
    $numberOfRows = mysqli_num_rows($result);
} else {
    $result = mysql_query($sql) or die (mysql_error());
    $numberOfRows = mysql_num_rows($result);
}
$timeRead = microtime(true);

/* Result metrics arrays to be printed */
$arrayMetricsNames = array();
$arrayMetricsTotals = array();
$arrayMetricsProjectTotals = array();
$arrayMetricsProjectBuildNumbers = array();

/* Temporary arrays for calculation */
$arrayProjectNames = array();
$arrayProjectBuildLatests = array();
$arrayProjectBuildScopeMins = array();
$arrayProjectBuildCounts = array();
$arrayProjectBuildNumbers = array();
if ($project == "All") {
    $arrayProjectNames = $_SESSION['arrayProjectName'];
    $arrayProjectBuildLatests = $_SESSION['arrayProjectBuildLatest'];
    $arrayProjectBuildScopeMins = $_SESSION['arrayProjectBuildScopeMin'];
} else {                                                                      // When Project filtered use data from that Project only (simplifies the sorting etc.)
    foreach($_SESSION['arrayProjectName'] as $projectKey => $projectValue) {
        if ($project == $projectValue) {
            $arrayProjectNames[0] = $_SESSION['arrayProjectName'][$projectKey];
            $arrayProjectBuildLatests[0] = $_SESSION['arrayProjectBuildLatest'][$projectKey];
            $arrayProjectBuildScopeMins[0] = $_SESSION['arrayProjectBuildScopeMin'][$projectKey];
            $projectBuildLatest = $arrayProjectBuildLatests[0];               // Save build number for the filtered Project (for printing later below)
            $projectBuildScopeMin = $arrayProjectBuildScopeMins[0];           // -,,-
        }
    }
}

/* Calculate the data */
$j = -1;                                                                      // Counter for resulting metrics rows (one per ach autotest)
$itemname="empty";
for ($i=0; $i<$numberOfRows; $i++) {                                          // Loop the rows (each autotest appears n times)
    if ($useMysqli)
        $resultRow = mysqli_fetch_row($result);
    else
        $resultRow = mysql_fetch_row($result);
    if($itemname <> "empty" AND $resultRow[DBCOLUMNTESTNAME] <> $itemname) {  // New autotest name ($resultRow is sorted by autotest name so change in name means new autotest rows will begin)
                                                                              // (this means the results of one autotest is now calculated, therefore save the results)
        $arrayMetricsProjectTotals[$j] = $arrayProjectBuildCounts;            // -> Save the calculated Project totals as a new row for one autotest
        $arrayMetricsProjectBuildNumbers[$j] = $arrayProjectBuildNumbers;     // -> Save the list of build numbers as a new row for one autotest
    }
    if($itemname == "empty" OR $resultRow[DBCOLUMNTESTNAME] <> $itemname) {   // First or new autotest name ($resultRow is sorted by autotest name so change in name means new autotest rows will begin)
        $j++;
        $arrayMetricsNames[$j] = $resultRow[DBCOLUMNTESTNAME];                // -> Save new name
        $arrayMetricsTotals[$j] = 0;                                          // Initialize
        foreach($arrayProjectNames as $projectKey => $projectValue) {
            $arrayMetricsProjectTotals[$j][$projectKey] = 0;                  // Initialize
            $arrayProjectBuildCounts[$projectKey] = 0;
            $arrayProjectBuildNumbers[$projectKey] = "";
        }
        $itemname = $resultRow[DBCOLUMNTESTNAME];
    }
    foreach($arrayProjectNames as $projectKey => $projectValue) {             // Count the Project builds in the Project specific Build number scope
        if ($resultRow[DBCOLUMNTESTPROJECT] == $projectValue) {                                // Find the Project
            if ($resultRow[DBCOLUMNTESTBUILD] >= $arrayProjectBuildScopeMins[$projectKey]) {   // If autotest is within the Build number scope for the Project
                $arrayProjectBuildCounts[$projectKey]++;                                       // -> Increase the Project specific count
                $buildstring = $resultRow[DBCOLUMNTESTBUILD];                 // Create the link url to build directory...
                if ($latestBuild < 10000)
                    $buildstring = '0' . $resultRow[DBCOLUMNTESTBUILD];
                if ($latestBuild < 1000)
                    $buildstring = '00' . $resultRow[DBCOLUMNTESTBUILD];
                if ($latestBuild < 100)
                    $buildstring = '000' . $resultRow[DBCOLUMNTESTBUILD];
                if ($latestBuild < 10)
                    $buildstring = '0000' . $resultRow[DBCOLUMNTESTBUILD];
                if ($conf == "All")
                    $buildLink = '<a href="' . LOGFILEPATHCI . $project . '/build_' . $buildstring
                        . '" target="_blank">' . $resultRow[DBCOLUMNTESTBUILD] . '</a>';                // Example: http://testresults.qt-project.org/ci/Qt3D_master_Integration/build_00412
                else
                    $buildLink = '<a href="' . LOGFILEPATHCI . $project . '/build_' . $buildstring
                        . '/' . $conf . '" target="_blank">' . $resultRow[DBCOLUMNTESTBUILD] . '</a>';  // Example: http://testresults.qt-project.org/ci/Qt3D_master_Integration/build_00412/linux-g++-32_Ubuntu_10.04_x86
                if ($arrayProjectBuildCounts[$projectKey] <= AUTOTEST_LATESTBUILDCOUNT)        // List the Build numbers
                    $arrayProjectBuildNumbers[$projectKey] = $arrayProjectBuildNumbers[$projectKey] . ',' . $buildLink;
                if ($arrayProjectBuildCounts[$projectKey] == AUTOTEST_LATESTBUILDCOUNT+1)      // List truncated to AUTOTEST_LATESTBUILDCOUNT
                    $arrayProjectBuildNumbers[$projectKey] = $arrayProjectBuildNumbers[$projectKey] . '...';
                $arrayMetricsTotals[$j]++;                                                     // -> Increase the total count
            }
        }
    }
}
$arrayMetricsProjectTotals[$j] = $arrayProjectBuildCounts;                    // -> Save the calculated Project totals for the last autotest
$arrayMetricsProjectBuildNumbers[$j] = $arrayProjectBuildNumbers;             // -> Save the list of build numbers as a new row for the last autotest

/* Sort the data based on total count */
$arraySortedAutotest = array();                                               // List of autotest ids in sorted order
$rows = array();                                                              // Temporary array to be used in sorting the autotests
$rows = $arrayMetricsTotals;
$numberOfRows = count($rows);

$arraySortedProject = array();                                                // List of project ids in sorted order (per each autotest)
$projectRows = array();                                                       // Temporary array to be used in sorting the projects (per each autotest)

for ($i=0; $i<$numberOfRows; $i++) {                                          // 1. Loop and sort the autotests rows (one row per autotest)
    $highestId = 0;
    $highestCount = 0;
    for ($j=0; $j<$numberOfRows; $j++) {                                      // Find the id for the highest value
        if ($rows[$j] >= $highestCount) {                                     // (using >= instead of > to get all items into the list; downside is that the items with the same number appear in descending aplhabetic order)
            $highestCount = $rows[$j];
            $highestId = $j;
        }
    }
    $arraySortedAutotest[] = $highestId;                                      // Save the id
    $rows[$highestId] = -1;                                                   // 'Remove' the highest value and find next in the outer loop

    $projectRows = $arrayMetricsProjectTotals[$i];
    $numberOfProjectRows = count($arrayProjectNames);
    for ($k=0; $k<$numberOfProjectRows; $k++) {                               // 2. Loop and sort the project rows (one row per project)
        $highestIdProject = 0;
        $highestCountProject = 0;
        for ($m=0; $m<$numberOfProjectRows; $m++) {                           // Find the id for the highest value
            if ($projectRows[$m] >= $highestCountProject) {                   // (using >= instead of > to get all items into the list; downside is that the items with the same number appear in descending aplhabetic order)
                $highestCountProject = $projectRows[$m];
                $highestIdProject = $m;
            }
        }
        $arraySortedProject[$i][] = $highestIdProject;                        // Save the project id (for the specific autotest)
        $projectRows[$highestIdProject] = -1;                                 // 'Remove' the highest value and find next in the outer loop
    }
}

/* Print the data */
if ($project == "All")
    echo '<b>Top ' . TOPLISTSIZE . ' most commonly failing autotests, significant only</b><br/><br/>';
else
    echo '<b>Top ' . TOPLISTSIZE . ' most commonly failing autotests, significant only</b> (last '
        . AUTOTEST_LATESTBUILDCOUNT . ' Builds ' . $projectBuildScopeMin . '-' . $projectBuildLatest . ')<br/><br/>';
$numberOfRows = count($arraySortedAutotest);                                  // Start printing autotests...
if ($numberOfRows > 0) {
    if ($numberOfRows > TOPLISTSIZE)
        $numberOfRows = TOPLISTSIZE;                                          // Limit the list as defined
    if ($project == "All")
        echo "<table border=\"1\">";
    else
        echo "<table>";
    for ($i=0; $i<$numberOfRows; $i++) {                                      // Loop to print autotests
        echo "<tr>";
        $j = $arraySortedAutotest[$i];                                        // Use the sorted list
        echo '<td>('. $arrayMetricsTotals[$j] . ')</td>';
        echo '<td>'. $arrayMetricsNames[$j] . '</td>';
        if ($project == "All") {                                              // List by Project
            $numberOfProjectRows = count($arraySortedProject[$j]);
            if ($numberOfProjectRows > AUTOTEST_PROJECTCOUNT)
                $numberOfProjectRows = AUTOTEST_PROJECTCOUNT;                 // Limit the list as defined
            echo "<td><table>";
            for ($k=0; $k<$numberOfProjectRows; $k++) {                       // Loop to print Projects for each autotest
                $m = $arraySortedProject[$j][$k];                             // Use the sorted list
                if ($arrayMetricsProjectTotals[$j][$m] > 0) {                 // Skip Project if count is zero
                    echo "<tr>";
                    echo '<td>(' . $arrayMetricsProjectTotals[$j][$m] . ')</td>';
                    echo '<td><a href="javascript:void(0);" onclick="filterProject(\''
                        . $arrayProjectNames[$m] . '\')">' . $arrayProjectNames[$m] . '</a></td>';
                    echo '<td>' . substr($arrayMetricsProjectBuildNumbers[$j][$m],1) . '</td>';    // Skip leading ','
                    echo "</tr>";
                }
            }
            echo "</table></td>";
        } else {                                                              // List only the Builds (for the selected Project)
            $m = $arraySortedProject[$j][0];                                  // Use the sorted list
            echo '<td>';
            if ($arrayMetricsProjectTotals[$j][$m] > 0)                       // Skip Project if count is zero
                echo substr($arrayMetricsProjectBuildNumbers[$j][$m],1);      // Skip leading ','
//                echo '<a href="' . $link . '" target="_blank">' . substr($arrayMetricsProjectBuildNumbers[$j][$m],1) . '</a>';    // Skip leading ','
            echo '</td>';
        }
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "(no items)<br/>";
}

if ($useMysqli)
    mysqli_free_result($result);                                              // Free result set

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