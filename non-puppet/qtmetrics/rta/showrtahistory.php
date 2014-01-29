<?php
session_start();
?>

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
include(__DIR__.'/../commonfunctions.php');
include(__DIR__.'/../commondefinitions.php');
include(__DIR__.'/../connectiondefinitions.php');
include "metricsboxdefinitions.php";
include "definitions.php";
include "functions.php";

/* Print table title row (the same columns to be used in showTestHistoryTableEnd and showTestHistory) */
function showTestHistoryTableTitle()
{
    echo '<table class="fontSmall">';
    echo '<tr class="tableBottomBorderThick">';
    echo '<th colspan="2"></th>';                       // Test Job name, configuration and build number
    echo '<th></th>';                                   // Passes/Failures
    echo '<th><a href="javascript:void(0);" onclick="showMessageWindow(\'rta/msgstatuschangesdescription.html\')">
              status<br>&<br>changes</a></th>';         // Status and changes between the last and last-1 with traffic lights
    echo '<th class="tableSideBorder">last</th>';       // Last build Pass/Failure counts
    for ($i=1; $i<=HISTORYJOBLISTCOUNT - 1; $i++) {     // Last-1 ... last-n
        echo '<th class="tableSideBorder">last -' . $i . '</th>';
    }
    echo '</tr>';
    // Leave the table 'open', to be closed in showTestHistoryTableEnd
}

/* Close the table */
function showTestHistoryTableEnd()
{
    echo '</table>';
}

/* Print summary information for a test job as one row in a table, including the latest and previous available test jobs
   Input:   $testJobName        (string)  the main title
            $testConfiguration  (string)  detailed information shown below the title
            $testLatestBuild    -,,-
            $testHistory        (array)   [number] the test history numbers from latest [0] to older ones [1,2,...]
            $testJobHistory     (array)   [type][number] the number of passes and each failure [type] for each test history number [0 is the latest]
            $rowNumber          (integer) a counter how many times this function called, used to separate every other row with a different background color
            $jobFilter          (string)  to identify if the job has been filtered or not (history view level 1 or 2)
   Output:  (none)
*/
function showTestHistory($testJobName, $testConfiguration, $testLatestBuild, $testHistory, $testJobHistory, $rowNumber, $jobFilter)
{
    /* First row: Job name, Passes (title), Passes changes, Passes history */
    if ($rowNumber % 2 == 0)
        echo '<tr>';
    else
        echo '<tr class="tableBackgroundColored">';
    if ($jobFilter == "All") {                                  // Show filter link on level 1 ...
        $selectedJob = $testJobName . FILTERSEPARATOR . 'conf'. FILTERVALUESEPARATOR . $testConfiguration;    // Note: The filter values (in getfilters.php) must include all these values
        echo '<td colspan="2"><a href="javascript:void(0);" onclick="filterJob(\'' . $selectedJob . '\')"><b>' . $testJobName . '</b></a></td>';
    } else {                                                    // ... but not on level 2
        echo '<td colspan="2"><b>' . $testJobName . '</b></td>';
    }
    echo '<td class="tableSideBorder tableBottomBorder fontColorGreen"><b>Passes</b></td>';
    if (count($testHistory) > 1) {                              // If history available
        $change = $testJobHistory[HISTORYPASSCOUNT][0] - $testJobHistory[HISTORYPASSCOUNT][1];
        if ($change > 0)
            $changeCount = '+' . $change;
        if ($change < 0)
            $changeCount = $change;
        if ($change == 0)
            $changeCount = '';
    } else {                                                    // History not available
        $changeCount = '';
    }
    echo '<td class="tableSideBorder tableBottomBorder tableCellCentered">' . $changeCount . '</td>';
    $i = 0;
    foreach ($testHistory as $test) {
        if ($i <= HISTORYJOBLISTCOUNT - 1) {
            echo '<td class="tableSideBorder tableBottomBorder tableCellCentered fontColorGreen">' . $testJobHistory[HISTORYPASSCOUNT][$i] . '</td>';
            $i++;
        }
    }
    for ($j=$i; $j<=HISTORYJOBLISTCOUNT - 1; $j++) {    // Fill the table with empty cells for the unavailable history items
        echo '<td></td>';
    }
    echo '</tr>';

    /* Second row: Configuration, Failures (title), Failures changes, Failures history (Failures includes all types: ERROR, FATAL, FAIL, XPASS) */
    if ($rowNumber % 2 == 0)
        echo '<tr>';
    else
        echo '<tr class="tableBackgroundColored">';
    echo '<td class="tableBottomBorderThick">' . $testConfiguration . '</td>';
    echo '<td class="tableBottomBorderThick">Installer build: ' . $testLatestBuild . '</td>';
    echo '<td class="tableBottomBorderThick tableSideBorder fontColorRed"><b>Failures</b></td>';
    if (count($testHistory) > 1) {                              // If history available
        $changePlus = 0;
        $changeMinus = 0;
        // Check if there are any new or removed failures of any type
        $i = $testJobHistory[HISTORYERRORCOUNT][0] - $testJobHistory[HISTORYERRORCOUNT][1];
        if ($i > 0)
            $changePlus = $changePlus + $i;
        if ($i < 0)
            $changeMinus = $changeMinus + $i;
        $i = $testJobHistory[HISTORYFATALCOUNT][0] - $testJobHistory[HISTORYFATALCOUNT][1];
        if ($i > 0)
            $changePlus = $changePlus + $i;
        if ($i < 0)
            $changeMinus = $changeMinus + $i;
        $i = $testJobHistory[HISTORYFAILCOUNT][0] - $testJobHistory[HISTORYFAILCOUNT][1];
        if ($i > 0)
            $changePlus = $changePlus + $i;
        if ($i < 0)
            $changeMinus = $changeMinus + $i;
        $i = $testJobHistory[HISTORYXPASSCOUNT][0] - $testJobHistory[HISTORYXPASSCOUNT][1];
        if ($i > 0)
            $changePlus = $changePlus + $i;
        if ($i < 0)
            $changeMinus = $changeMinus + $i;
        // Print the changes
        if ($changePlus > 0) {
            if ($changeMinus == 0)                                          // Case 1: Only new failures
                $changeCount = '+' . $changePlus;
            else                                                            // Case 2: Both new and removed failures
                $changeCount = '+' . $changePlus . ' / ' . $changeMinus;
            $changeColor = ' tableCellBackgroundRed';
        } else {                                                            // Case 3: Only removed failures
            $changeCount = $changeMinus;
            $changeColor = ' tableCellBackgroundYellow';
        }
        if ($changePlus == 0 AND $changeMinus == 0) {                       // Case 4: No changes
            $changeCount = '';
            $changeColor = '';
        }
    } else {                                                    // History not available
        $changeCount = '';
        $changeColor = '';
    }
    $i = $testJobHistory[HISTORYERRORCOUNT][0] + $testJobHistory[HISTORYFATALCOUNT][0]
            + $testJobHistory[HISTORYFAILCOUNT][0] + $testJobHistory[HISTORYXPASSCOUNT][0];
    if ($changeCount == '' AND $i > 0)                                      // Case 5: No changes but still some failures
        $changeColor = ' tableCellBackgroundYellow';
    if ($i == 0)                                                            // Case 6: Not any failures
        $changeColor = ' tableCellBackgroundGreen';
    echo '<td class="tableBottomBorderThick tableSideBorder tableCellCentered' . $changeColor . '"><b>' . $changeCount . '</b></td>';
    $i = 0;
    foreach ($testHistory as $test) {                           // Show each available history item
        if ($i <= HISTORYJOBLISTCOUNT - 1) {
            if ($testJobHistory[HISTORYPASSCOUNT][$i] == '-')               // In case the history item is not available (Note: calculation with each failure type results to value '0' instead)
                $testFailureCount = '-';
            else
                $testFailureCount = $testJobHistory[HISTORYERRORCOUNT][$i] + $testJobHistory[HISTORYFATALCOUNT][$i]
                                  + $testJobHistory[HISTORYFAILCOUNT][$i] + $testJobHistory[HISTORYXPASSCOUNT][$i];
            echo '<td class="tableBottomBorderThick tableSideBorder tableCellCentered fontColorRed">' . $testFailureCount . '</td>';
            $i++;
        }
    }
    for ($j=$i; $j<=HISTORYJOBLISTCOUNT - 1; $j++) {            // Fill the table with empty cells for the unavailable history items
        echo '<td class="tableBottomBorderThick"></td>';
    }
    echo '</tr>';
}

/* Print table title row (the same columns to be used in showTestComparisonTableEnd and showTestComparison) */
function showTestComparisonTableTitle()
{
    echo '<table class="fontSmall">';
    echo '<tr class="tableBottomBorder">';
    echo '<th class="tableSideBorder">Last</th>';
    echo '<th class="tableSideBorder">Last-1</th>';
    echo '</tr>';
    // Leave the table 'open', to be closed in showTestHistoryTableEnd
}

/* Close the table */
function showTestComparisonTableEnd()
{
    echo '<tr class="tableTopBorder">';
    echo '<td></td><td></td>';
    echo '</tr>';
    echo '</table>';
}

/* Show the comparison of two test job runs for a test job configuration
   Input:   $testJobName        (string) the main title, same for last and previous
            $testConfiguration  (string) detailed information shown below the title, same for last and previous
            $buildNumber        (array)  [number] detailed information shown below the title for last [0] and previous [1] test history number
            $testHistoryNumber  -,,-
            $timestamp          -,,-
            $testJobSummary     (array)  [number][type] the number of passes and each failure [type] for last [0] and previous [1] test history [number]
            $failureDescription (array)  [number][failure] the list of failures for last [0] and previous [1] test history [number] with type,
                                         test name, file name, line number and failure description itself for each [failure]
   Output:  (none)
*/
function showTestComparison($testJobName, $testConfiguration, $buildNumber, $testHistoryNumber, $timestamp, $testJobSummary, $failureDescription)
{
    // General info: Latest run
    if (strpos($testJobName, "enterprise") !== FALSE)
        $testHistoryNumberLink = PACKAGINGJENKINSENTERPRISE;
    else
        $testHistoryNumberLink = PACKAGINGJENKINSOPENSOURCE;
    if ($testHistoryNumberLink != "")
        $testHistoryNumberLink = $testHistoryNumberLink . 'job/' . $testJobName . '/' . $testHistoryNumber[0] .
                                 '/cfg=' . $testConfiguration . '/squishReport/';
    echo '<tr class="tableBottomBorder">';
    echo '<td class="tableSideBorder">';
    echo '<b>' . $testJobName . '</b><br><br>';
    echo '<table>';
    echo '<tr><td><b>Job start time: </b></td><td>' . $timestamp[0] . '</td></tr>';
    echo '<tr><td><b>Configuration: </b></td><td>' . $testConfiguration . '</td></tr>';
    echo '<tr><td><b>Installer build number: </b></td><td>' . $buildNumber[0] . '</td></tr>';
    echo '<tr><td><b>Jenkins build history: </b></td><td><a href="' . $testHistoryNumberLink .
         '" title="Report opens if available in Jenkins" target="_blank">' . $testHistoryNumber[0] .
         ' (open squish report)</a></td></tr>';
    echo '</table>';
    echo '</td>';
    // General info: Previous run
    if (count($testHistoryNumber) == 1 OR $testJobSummary[1][TESTPASSCOUNT] == '')      // If there are history items for this specific configuration
        $booPreviousAvailable = FALSE;
    else
        $booPreviousAvailable = TRUE;
    if (strpos($testJobName, "enterprise") !== FALSE)
        $testHistoryNumberLink = PACKAGINGJENKINSENTERPRISE;
    else
        $testHistoryNumberLink = PACKAGINGJENKINSOPENSOURCE;
    if ($testHistoryNumberLink != "")
        $testHistoryNumberLink = $testHistoryNumberLink . 'job/' . $testJobName . '/' . $testHistoryNumber[1] .
                                 '/cfg=' . $testConfiguration . '/squishReport/';
    echo '<td class="tableSideBorder">';
    echo '<b>' . $testJobName . '</b><br><br>';
    if ($booPreviousAvailable) {
        echo '<table>';
        echo '<tr><td><b>Job start time: </b></td><td>' . $timestamp[1] . '</td></tr>';
        echo '<tr><td><b>Configuration: </b></td><td>' . $testConfiguration . '</td></tr>';
        echo '<tr><td><b>Installer build number: </b></td><td>' . $buildNumber[1] . '</td></tr>';
        echo '<tr><td><b>Jenkins build history: </b></td><td><a href="' . $testHistoryNumberLink .
             '" title="Report opens if available in Jenkins" target="_blank">' . $testHistoryNumber[1] .
             ' (open squish report)</a></td></tr>';
        echo '</table>';
    } else {
        echo '(previous run not found)';
    }
    echo '</td>';
    echo '</tr>';
    // Summary data: Latest run
    $latestTotal = 0;
    $previousTotal = 0;
    $changeTotal = 0;
    $change = array();
    $changeCounts = array();
    $j = count($testJobSummary[0]);
    for ($i=0; $i<$j; $i++) {                                                       // Calculate the change of each count
        if ($booPreviousAvailable) {
            $change[$i] = $testJobSummary[0][$i] - $testJobSummary[1][$i];
            if ($change[$i] > 0)
                $changeCounts[$i] = '+' . $change[$i];
            if ($change[$i] < 0)
                $changeCounts[$i] = $change[$i];
            if ($change[$i] == 0)
                $changeCounts[$i] = '';
        } else {
            $changeCounts[$i] = '';
        }
        if ($i <> TESTPASSCOUNT) {
            $latestTotal = $latestTotal + $testJobSummary[0][$i];
            $previousTotal = $previousTotal + $testJobSummary[1][$i];
            $changeTotal = $changeTotal + $change[$i];
        }
    }
    if ($changeTotal > 0)
        $changeTotal = '+' . $changeTotal;
    if ($changeTotal == 0)
        $changeTotal = '';
    echo '<tr class="tableBottomBorder">';
    echo '<td class="tableSideBorder"><b>Summary:</b>';
    echo '<table>';
    echo '<tr class="fontColorGreen"><td><b>PASSes: </b></td><td>' . $testJobSummary[0][TESTPASSCOUNT] . '</td><td><i>' . $changeCounts[TESTPASSCOUNT] . '</i></td></tr>';
    echo '<tr><td><b>ERRORs: </b></td><td>' . $testJobSummary[0][TESTERRORCOUNT] . '</td><td><i>' . $changeCounts[TESTERRORCOUNT] . '</i></td></tr>';
    echo '<tr><td><b>FAILs: </b></td><td>' . $testJobSummary[0][TESTFAILCOUNT] . '</td><td><i>' . $changeCounts[TESTFAILCOUNT] . '</i></td></tr>';
    echo '<tr><td><b>FATALs: </b></td><td>' . $testJobSummary[0][TESTFATALCOUNT] . '</td><td><i>' . $changeCounts[TESTFATALCOUNT] . '</i></td></tr>';
    echo '<tr><td><b>XPASSes: </b></td><td>' . $testJobSummary[0][TESTXPASSCOUNT] . '</td><td><i>' . $changeCounts[TESTXPASSCOUNT] . '</i></td></tr>';
    echo '<tr class="tableTopBorder"><td><b>All Failures: </b></td><td>' . $latestTotal . '</td><td><i>' . $changeTotal . '</i></td></tr>';
    echo '</table>';
    echo '</td>';
    // Summary data: Previous run
    echo '<td class="tableSideBorder"><b>Summary:</b>';
    if ($booPreviousAvailable) {
        echo '<table>';
        echo '<tr class="fontColorGreen"><td><b>PASSes: </b></td><td>' . $testJobSummary[1][TESTPASSCOUNT] . '</td></tr>';
        echo '<tr><td><b>ERRORs: </b></td><td>' . $testJobSummary[1][TESTERRORCOUNT] . '</td></tr>';
        echo '<tr><td><b>FAILs: </b></td><td>' . $testJobSummary[1][TESTFAILCOUNT] . '</td></tr>';
        echo '<tr><td><b>FATALs: </b></td><td>' . $testJobSummary[1][TESTFATALCOUNT] . '</td></tr>';
        echo '<tr><td><b>XPASSes: </b></td><td>' . $testJobSummary[1][TESTXPASSCOUNT] . '</td></tr>';
        echo '<tr class="tableTopBorder"><td><b>All Failures: </b></td><td>' . $previousTotal . '</td></tr>';
        echo '</table>';
    }
    echo '</td>';
    echo '</tr>';
    // Failure list: Latest run
    $k = 0;
    $failureCountSame = 0;
    $failureCountAdded = 0;
    $failureCountRemoved = 0;
    foreach ($failureDescription[0] as $failureLatest) {                            // First list by the latest failures
        if ($k % 2 == 0)
            echo '<tr>';
        else
            echo '<tr class="tableBackgroundColored">';
        echo '<td class="tableSideBorder">';                                        // Latest failure
        echo $failureLatest;
        echo '</td>';
        echo '<td class="tableSideBorder">';                                        // Previous failure
        if ($booPreviousAvailable) {
            $m = 0;
            for ($m=0; $m<count($failureDescription[1]); $m++) {                    // Check the previous failures ...
                if ($failureLatest == $failureDescription[1][$m]) {                 // ... and if same failure listed ...
                    echo $failureDescription[1][$m];                                // ... list it ...
                    $failureDescription[1][$m] = '-';                               // ... clear it not to print it twice
                    $failureCountSame++;
                }
            }
        }
        echo '</td>';
        echo '</tr>';
        $k++;
    }
    $failureCountAdded = $k - $failureCountSame;
    if ($booPreviousAvailable) {
        foreach ($failureDescription[1] as $failurePrevious) {                      // Then list the previous removed failures (those not yet listed above)
            if ($failurePrevious <> '-') {                                          // If not yet listed above ...
                if ($k % 2 == 0)
                    echo '<tr>';
                else
                    echo '<tr class="tableBackgroundColored">';
                echo '<td class="tableSideBorder">';                                // Latest failure
                echo '</td>';
                echo '<td class="tableSideBorder">';                                // Previous failure
                echo $failurePrevious;                                              // ... list it ...
                $failureCountRemoved++;
                echo '</td>';
                echo '</tr>';
                $k++;
            }
        }
    }
    // Change summary
    if ($booPreviousAvailable) {
        echo '<tr class="tableSideBorder tableTopBorder">';
        echo '<td><b>Summary of changes:</b>';
        echo '<table>';
        echo '<tr><td><b>Same: </b></td><td>' . $failureCountSame . '</td></tr>';
        echo '<tr><td><b>Added: </b></td><td>' . $failureCountAdded . '</td></tr>';
        echo '<tr><td><b>Removed: </b></td><td>' . $failureCountRemoved . '</td></tr>';
        echo '</table>';
        echo '</td>';
        echo '<td>';
        echo '</td>';
        echo '</tr>';
    }
}

/************************************************************/
/* START                                                    */
/************************************************************/

$timeStart = microtime(true);

/* Proceed only if the source data directory is set */
$rtaXmlBaseDir = RTAXMLBASEDIRECTORY;
if ($rtaXmlBaseDir != "") {

    /* Get the filters */
    $arrayFilters = array();
    $arrayFilter = array();
    $filters = $_GET["filters"];
    $filters = rawurldecode($filters);              // Decode the encoded parameter (encoding in ajaxrequest.js)
    $arrayFilters = explode(FILTERSEPARATOR, $filters);
    $arrayFilter = explode(FILTERVALUESEPARATOR, $arrayFilters[FILTERTEST]);
    $test = $arrayFilter[1];
    $arrayFilter = explode(FILTERVALUESEPARATOR, $arrayFilters[FILTERLICENSE]);
    $license = $arrayFilter[1];
    $arrayFilter = explode(FILTERVALUESEPARATOR, $arrayFilters[FILTERPLATFORM]);
    $platform = $arrayFilter[1];
    $arrayFilter = explode(FILTERVALUESEPARATOR, $arrayFilters[FILTERTESTJOB]);
    $job = $arrayFilter[1];
    $arrayFilter = explode(FILTERVALUESEPARATOR, $arrayFilters[FILTERTESTCONF]);
    $conf = $arrayFilter[1];

    /* Get data from the session variables */
    if (isset($_SESSION['rtaTestJobCount'])) {
        $rtaTestJobId = array();
        $rtaTestJobName = array();
        $rtaTestHistoryNumbers = array();
        $rtaTestHistoryMin = array();
        $rtaTestHistoryMax = array();
        $rtaTestJobCount = $_SESSION['rtaTestJobCount'];
        $rtaTestJobId = $_SESSION['rtaTestJobId'];
        $rtaTestJobName = $_SESSION['rtaTestJobName'];
        $rtaTestJobLatestBuild = $_SESSION['rtaTestJobLatestBuild'];
        $rtaTestConfs = $_SESSION['rtaTestConfs'];
        $rtaTestConfsHistory = $_SESSION['rtaTestConfsHistory'];
        $rtaTestHistoryNumbers = $_SESSION['rtaTestHistoryNumbers'];
        $rtaTestHistoryMin = $_SESSION['rtaTestHistoryMin'];
        $rtaTestHistoryMax = $_SESSION['rtaTestHistoryMax'];
    }

/*************************************************************************/
/* NESTED LEVEL 1: The default history view (partly common with level 2) */
/*************************************************************************/

    /* Level 1 titles and used filters */
    if ($job == "All") {
        echo '<a href="javascript:void(0);" class="imgLink" onclick="showMessageWindow(\'rta/msgrtahistorylevel1.html\')"><img src="images/info.png" alt="info"></a>&nbsp&nbsp';
        echo '<b>RTA HISTORY:</b><br><br>';
        if ($test <> "All" OR $license <> "All" OR $platform <> "All") {
            echo '<table>';
            if ($test <> "All")
                echo '<tr><td>Test Type:</td><td class="tableCellBackgroundTitle">' . $test . '</td></tr>';
            if ($license <> "All")
                echo '<tr><td>License Type:</td><td class="tableCellBackgroundTitle">' . $license . '</td></tr>';
            if ($platform <> "All")
                echo '<tr><td>Platform:</td><td class="tableCellBackgroundTitle">' . $platform . '</td></tr>';
            echo '</table>';
            echo '<br>';
        }
    }
    /* Level 2 titles and used filters */
    else {
        echo '<a href="javascript:void(0);" class="imgLink" onclick="showMessageWindow(\'rta/msgrtahistorylevel2.html\')">
              <img src="images/info.png" alt="info"></a>&nbsp&nbsp';
        echo '<b>RTA HISTORY:</b> <a href="javascript:void(0);" onclick="filterJob(\'All\')">Select Job</a> -> ' .
              $job . ' (' . $conf . ')' . '<br><br>';
        echo '<table>';
        echo '<tr><td>Test Job:</td><td class="tableCellBackgroundTitle">' . $job . '</td></tr>';
        echo '<tr><td>Configuration:</td><td class="tableCellBackgroundTitle">' . $conf . '</td></tr>';
        echo '</table>';
        echo '<br>';
        echo '<br><b>Test history</b><br>';
    }

    // The following applies to level 1 (all or filtered rows), and to level 2 where separately noted (the selected job and configuration row only)
    if (isset($_SESSION['rtaTestJobCount'])) {

        $testJobHistory = array();

        /* Print table titles */
        showTestHistoryTableTitle();

        /* Loop each RTA test job and its test history in sorted order ($rtaTestJobName is sorted, other data linked with the $rtaTestJobId) */
        $k = 0;
        if ($test == "All")
            $filterTest = '_';                                                  // Filtering: Set the string to be found in the test job name (i.e. directory name)
        else
            $filterTest = '_' . $test . '_';                                    // -,,-
        if ($license == "All")
            $filterLicense = '_';                                               // -,,-
        else
            $filterLicense = '_' . $license . '_';                              // -,,-
        if ($platform == "All")
            $filterPlatform = '_';                                              // -,,-
        else
            $filterPlatform = '_' . $platform . '_';                            // -,,-
        if ($job <> "All") {                                                    // Level 2 view: Show the history row for selected job and configuration
            $filterTest = '';
            $filterLicense = '';
            $filterPlatform = '';
        }
        for ($i=0; $i<$rtaTestJobCount; $i++) {                                 // Check each RTA test job directory (e.g. Qt5_RTA_opensource_installer_tests_linux_32bit) and its test runs (e.g. 220)
            if ((strpos($rtaTestJobName[$i], $filterTest) > 0 AND               // Check possible filtering
                strpos($rtaTestJobName[$i], $filterLicense) > 0 AND
                strpos($rtaTestJobName[$i], $filterPlatform) > 0) OR
                $rtaTestJobName[$i] == $job) {                                  // Level 2 view: Check the job name
                $j = $rtaTestJobId[$i];
                $rtaTestJobDirectory = $rtaXmlBaseDir . $rtaTestJobName[$i];    // Set pointer to the related test job directory
                $directories = new RecursiveIteratorIterator(
                    new ParentIterator(
                        new RecursiveDirectoryIterator($rtaTestJobDirectory)),
                        RecursiveIteratorIterator::SELF_FIRST);
                foreach ($directories as $directory) {                          // Check each RTA test job history (e.g. 220, 219, 218)
                    $dirName = substr($directory, strripos($directory, "/") + 1);
                    if ($dirName == $rtaTestHistoryMax[$j]) {                   // Show the history based on the latest run only
                        $handle = opendir($directory);
                        while (($entry = readdir($handle)) !== FALSE) {         // Check the results in a tar.gz file (e.g. linux-g++-Ubuntu11.10-x86.tar.gz)
                            if ($entry == "." || $entry == "..")
                                continue;
                            $configuration = substr($entry, 0, strpos($entry, TARFILENAMEEXTENSION));
                            if ($job <> "All" AND $configuration <> $conf)      // Level 2 view: Check only the filtered configuration
                                continue;
                            $filePath = $directory . '/' . $entry;
                            if (is_file($filePath)) {
                                try {                                           // Open an existing phar
                                    $archive  = new PharData($filePath);
                                    foreach (new RecursiveIteratorIterator($archive ) as $file) {
                                        if ($file->getFileName() == SUMMARYXMLFILENAME) {        // Check for the summary file
                                            // Get the history data
                                            $filePathPhar = 'phar://' . substr($directory, 0, strripos($directory, "/") + 1);
                                            $fileName = $entry . '/' . $file->getFileName();
                                            saveXmlHistory($configuration, $rtaTestConfsHistory[$j], $filePathPhar,
                                                           $rtaTestHistoryNumbers[$j], $fileName, $testJobHistory);
                                            // Print the history data
                                            showTestHistory($rtaTestJobName[$i], $configuration, $rtaTestJobLatestBuild[$j],
                                                            $rtaTestHistoryNumbers[$j], $testJobHistory, $k, $job);
                                            $k++;
                                        }
                                    }
                                } catch (Exception $e) {
                                    echo 'Could not open Phar: ', $e;
                                }
                            }
                            clearstatcache();
                        }
                        closedir($handle);
                    }
                }
            }
        }

        /* Close the table */
        showTestHistoryTableEnd();

    } else {
        echo '<br>Filter values not ready or they are expired, please <a href="javascript:void(0);" onclick="reloadFilters()">reload</a> ...';
    }

/************************************************************/
/* NESTED LEVEL 2: The comparison view                      */
/************************************************************/

    if ($job <> "All") {
        echo '<br><br><b>Comparison of the last two test runs</b><br><br>';
        if (isset($_SESSION['rtaTestJobCount'])) {

            $testJobSummary = array();
            $timestamp = array();
            $buildNumber = array();
            $failureDescription = array();

            /* Print table titles */
            showTestComparisonTableTitle();

            /* Loop each RTA test job and its test history in sorted order ($rtaTestJobName is sorted, other data linked with the $rtaTestJobId) */
            for ($i=0; $i<$rtaTestJobCount; $i++) {                                 // Check each RTA test job directory (e.g. Qt5_RTA_opensource_installer_tests_linux_32bit) ...
                if ($job == $rtaTestJobName[$i]) {                                  // ... to find the selected one
                    $j = $rtaTestJobId[$i];
                    $rtaTestJobDirectory = $rtaXmlBaseDir . $rtaTestJobName[$i];    // Set pointer to the related test job directory
                    $directories = new RecursiveIteratorIterator(
                        new ParentIterator(
                            new RecursiveDirectoryIterator($rtaTestJobDirectory)),
                            RecursiveIteratorIterator::SELF_FIRST);
                    foreach ($directories as $directory) {                          // Check each RTA test job history (e.g. 220, 219, 218)
                        $dirName = substr($directory, strripos($directory, "/") + 1);
                        // Check the latest run
                        if ($dirName == $rtaTestHistoryNumbers[$j][0]) {
                            $handle = opendir($directory);
                            while (($entry = readdir($handle)) !== FALSE) {         // Check the results in a tar.gz file (e.g. linux-g++-Ubuntu11.10-x86.tar.gz)
                                if ($entry == "." || $entry == "..")
                                    continue;
                                $configuration = substr($entry, 0, strpos($entry, TARFILENAMEEXTENSION));
                                if ($conf == $configuration) {                      // If this is the selected configuration
                                    $timestamp[0] = '';
                                    $buildNumber[0] = 0;
                                    $failureDescription[0] = array();
                                    $testJobSummary[0] = array();
                                    $testJobSummary[0][TESTERRORCOUNT] = 0;
                                    $testJobSummary[0][TESTFATALCOUNT] = 0;
                                    $testJobSummary[0][TESTFAILCOUNT] = 0;
                                    $testJobSummary[0][TESTXPASSCOUNT] = 0;
                                    $testJobSummary[0][TESTPASSCOUNT] = 0;
                                    $filePath = $directory . '/' . $entry;
                                    if (is_file($filePath)) {
                                        try {                                       // Open an existing phar
                                            $archive  = new PharData($filePath);
                                            foreach (new RecursiveIteratorIterator($archive ) as $file) {
                                                if (stripos($file->getFileName(), RESULTXMLFILENAMEPREFIX) === 0) {        // Check for the result file (e.g. result_10_08_17.446.xml)
                                                    // Get the data from latest test run
                                                    $filePathPhar = 'phar://' . $directory . '/' . $entry . '/' . $file->getFileName();
                                                    saveXmlFailures($filePathPhar, $timestamp[0], $buildNumber[0], $failureDescription[0],
                                                                    $testJobSummary[0]);
                                                }
                                            }
                                        } catch (Exception $e) {
                                            echo 'Could not open Phar: ', $e;
                                        }
                                    }
                                    clearstatcache();
                                }
                            }
                            closedir($handle);
                        }
                        // Check the previous run
                        if ($dirName == $rtaTestHistoryNumbers[$j][1]) {
                            $handle = opendir($directory);
                            while (($entry = readdir($handle)) !== FALSE) {         // Check the results in a tar.gz file (e.g. linux-g++-Ubuntu11.10-x86.tar.gz)
                                if ($entry == "." || $entry == "..") {
                                    continue;
                                }
                                $configuration = substr($entry, 0, strpos($entry, TARFILENAMEEXTENSION));
                                if ($conf == $configuration) {                      // If this is the selected configuration
                                    $timestamp[1] = '';
                                    $buildNumber[1] = 0;
                                    $failureDescription[1] = array();
                                    $testJobSummary[1] = array();
                                    $testJobSummary[1][TESTERRORCOUNT] = 0;
                                    $testJobSummary[1][TESTFATALCOUNT] = 0;
                                    $testJobSummary[1][TESTFAILCOUNT] = 0;
                                    $testJobSummary[1][TESTXPASSCOUNT] = 0;
                                    $testJobSummary[1][TESTPASSCOUNT] = 0;
                                    $filePath = $directory . '/' . $entry;
                                    if (is_file($filePath)) {
                                        try {                                       // Open an existing phar
                                            $archive  = new PharData($filePath);
                                            foreach (new RecursiveIteratorIterator($archive ) as $file) {
                                                if (stripos($file->getFileName(), RESULTXMLFILENAMEPREFIX) === 0) {        // Check for the result file (e.g. result_10_08_17.446.xml)
                                                    // Get the data from latest test run
                                                    $filePathPhar = 'phar://' . $directory . '/' . $entry . '/' . $file->getFileName();
                                                    saveXmlFailures($filePathPhar, $timestamp[1], $buildNumber[1], $failureDescription[1],
                                                                    $testJobSummary[1]);
                                                }
                                            }
                                        } catch (Exception $e) {
                                            echo 'Could not open Phar: ', $e;
                                        }
                                    }
                                    clearstatcache();
                                }
                            }
                            closedir($handle);
                        }
                    }
                }
            }

            /* Print the comparison */
            showTestComparison($job, $conf, $buildNumber, $rtaTestHistoryNumbers[$j], $timestamp, $testJobSummary, $failureDescription);

            /* Close the table */
            showTestComparisonTableEnd();

        } else {
            echo '<br>Filter values not ready or they are expired, please <a href="javascript:void(0);" onclick="reloadFilters()">reload</a> ...';
        }
    }

/* Proceed only if the source data directory is set */
} else {
    echo '<b>Sorry, the source data is not available here!</b>';
}

/* Elapsed time */
if ($showElapsedTime) {
    $timeEnd = microtime(true);
    $time = round($timeEnd - $timeStart, 2);
    echo "<div class=\"elapdedTime\">";
    echo "<ul><li>";
    echo "Total time: $time s";
    echo "</li></ul>";
    echo "</div>";
}

?>
