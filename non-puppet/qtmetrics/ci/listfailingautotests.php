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
    // $build
    // $buildNumber        (in listgeneraldata.php)
    // $projectFilter
    // $confFilter
    // $showElapsedTime
    // $timeEnd

$timeStartThis = $timeEnd;                          // Start where previous step ended

/* Check the blocking (non-insignificant) Configurations (to skip printing significant Autotests for insignificant Configurations) */
$arrayBlockingConfs = array();
if ($build == 0) {                          // Show the latest build ...
    $sql = cleanSqlString(
           "SELECT cfg
            FROM cfg_latest
            WHERE $confFilter AND $projectFilter AND insignificant=0");
} else {                                    // ... or the selected build
    $sql = cleanSqlString(
           "SELECT cfg
            FROM cfg
            WHERE $confFilter AND $projectFilter AND build_number=$buildNumber AND insignificant=0");
}
$dbColumnCfgCfg = 0;
if ($useMysqli) {
    $result = mysqli_query($conn, $sql);
    $numberOfRows = mysqli_num_rows($result);
} else {
    $result = mysql_query($sql) or die (mysql_error());
    $numberOfRows = mysql_num_rows($result);
}
for ($i=0; $i<$numberOfRows; $i++) {                                          // Loop the Configurations
    if ($useMysqli)
        $resultRow = mysqli_fetch_row($result);
    else
        $resultRow = mysql_fetch_row($result);
    $arrayBlockingConfs[] = $resultRow[$dbColumnCfgCfg];
}

/* Read Autotest data from database */
if ($build == 0) {                          // Show the latest build ...
    $sql = cleanSqlString(
           "SELECT name, project, build_number, cfg
            FROM test_latest
            WHERE $projectFilter AND $confFilter AND insignificant=0
            ORDER BY name, project, build_number DESC");
} else {                                    // ... or the selected build
    $sql = cleanSqlString(
           "SELECT name, project, build_number, cfg
            FROM test
            WHERE $projectFilter AND build_number=$buildNumber AND $confFilter AND insignificant=0
            ORDER BY name, project, build_number DESC");
}
$dbColumnTestName = 0;
$dbColumnTestProject = 1;
$dbColumnTestBuild = 2;
$dbColumnTestCfg = 3;
if ($useMysqli) {
    $result = mysqli_query($conn, $sql);
    $numberOfRows = mysqli_num_rows($result);
} else {
    $result = mysql_query($sql) or die (mysql_error());
    $numberOfRows = mysql_num_rows($result);
}
$timeRead = microtime(true);

/* Result storages to be printed */
$arrayAutotestNames = array();
$arrayAutotestTotals = array();
$arrayAutotestConfLinks = array();

/* Get the the significant Autotests */
$j = -1;                                                                      // Counter for resulting metrics rows (one per each autotest)
$itemname="empty";
$failedAutotestCount = 0;
for ($i=0; $i<$numberOfRows; $i++) {                                          // Loop the rows (each autotest may appears several times i.e. for several Configurations)
    if ($useMysqli)
        $resultRow = mysqli_fetch_row($result);
    else
        $resultRow = mysql_fetch_row($result);
    if($itemname <> "empty" AND $resultRow[$dbColumnTestName] <> $itemname) { // STEP 3: New Autotest name in the list ($resultRow is sorted by autotest name so change in name means new autotest rows will begin)
                                                                              // (this means the results of one autotest is now calculated, therefore save the results)
        $arrayAutotestTotals[$j] = $autotestConfCount;                        // -> Save the calculated totals as a new row for one autotest
    }
    if ($itemname == "empty" OR $resultRow[$dbColumnTestName] <> $itemname) { // STEP 1: First or new Autotest name ($resultRow is sorted by autotest name so change in name means new autotest rows will begin)
        $j++;
        $arrayAutotestNames[$j] = $resultRow[$dbColumnTestName];              // -> Save new name
        $arrayAutotestTotals[$j] = 0;                                         // Initialize
        $autotestConfCount = 0;                                               // Initialize
        $arrayAutotestConfLinks[$j] = "";                                     // Initialize
        $itemname = $resultRow[$dbColumnTestName];
    }
    foreach($arrayBlockingConfs as $key => $value) {                          // Loop all blocking Configurations
        if ($resultRow[$dbColumnTestCfg] == $value) {                         // If the Configuration for this Autotest is a blocking one
            $autotestConfCount++;                                             // STEP 2: Save data for the Autotest
            $failedAutotestCount++;
            $buildNumberString = createBuildNumberString($resultRow[$dbColumnTestBuild]); // Create the link url to build directory...
            $link = '<a href="' . LOGFILEPATHCI . $project . '/build_' . $buildNumberString
                . '/' . $resultRow[$dbColumnTestCfg] . '" target="_blank">' . $resultRow[$dbColumnTestCfg] . '</a>';  // Example: http://testresults.qt-project.org/ci/Qt3D_master_Integration/build_00412/linux-g++-32_Ubuntu_10.04_x86
            $arrayAutotestConfLinks[$j] = $arrayAutotestConfLinks[$j] . ', ' . $link;
        }
    }
}
$arrayAutotestTotals[$j] = $autotestConfCount;                                // STEP 4: All Autotests checked: Save the calculated totals for the last autotest

if ($useMysqli)
    mysqli_free_result($result);                                              // Free result set

/* Print the data */
echo '<div class="metricsTitle">';
echo '<b>Failed Autotests that caused Build failure</b> (significant Autotests in blocking Configurations)';
echo '</div>';
if ($failedAutotestCount > 0) {
    echo '<table class="fontSmall">';
    echo '<tr>';
    echo '<th></th>';
    echo '<td class="tableBottomBorder tableSideBorder tableCellCentered tableCellBuildSelected">';
    if ($build == 0)                                // Show the latest build ...
        echo 'LATEST BUILD</td>';
    else                                            // ... or the selected build
        echo 'BUILD ' . $buildNumber . '</td>';
    echo '</tr>';
    echo '<tr class="tableBottomBorder">';
    echo '<td></td>';
    echo '<td class="tableSideBorder">List of Configurations (link to testresults directory)</td>';
    echo '</tr>';
    $j = 0;
    foreach($arrayAutotestNames as $key => $value) {                          // Loop to print autotests
        if ($arrayAutotestTotals[$key] > 0) {
            if ($j % 2 == 0)
                echo '<tr>';
            else
                echo '<tr class="tableBackgroundColored">';
            echo '<td>'. $arrayAutotestNames[$key] . '</td>';
            echo '<td class="tableSideBorder">'. substr($arrayAutotestConfLinks[$key],2) . '</td>';     // Skip leading ", "
            echo "</tr>";
            $j++;
        }
    }
    echo "</table>";
} else {
    echo "(Not any Failed Autotests)<br/>";
}

/* Elapsed time */
if ($showElapsedTime) {
    $timeEnd = microtime(true);
    $timeDbRead = round($timeRead - $timeStartThis, 4);
    $timeCalc = round($timeEnd - $timeRead, 4);
    $time = round($timeEnd - $timeStartThis, 4);
    echo "<div class=\"elapdedTime\">";
    echo "<ul><li>";
    echo "<b>Total time</b> (round $round): $time s (database read time: $timeDbRead s, calculation time: $timeCalc s)";
    echo "</li></ul>";
    echo "</div>";
} else {
    echo "<br>";
}

?>