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

define("RESULTXMLFILENAME", "summary.xml");         // The result summary file name
define("TARFILENAMEEXTENSION", ".tar.gz");          // Tar file name used for configuration name by removing this extension
define("HISTORYJOBLISTCOUNT", 13);                  // The maximum number of test jobs to be shown in the history view

define("HISTORYTESTCASECOUNT", 0);                  // History data items
define("HISTORYERRORCOUNT", 1);
define("HISTORYFATALCOUNT", 2);
define("HISTORYFAILCOUNT", 3);
define("HISTORYXPASSCOUNT", 4);
define("HISTORYPASSCOUNT", 5);

/* Save summary information for a test job from XML files, including the latest and previous available test jobs  */
function saveXmlHistory($configuration, $testConfHistory, $xmlDirectory, $testHistory, $xmlFileName, &$testJobHistory)
{
    $testCaseCount = array();
    $failureTypeErrorCount = array();
    $failureTypeFatalCount = array();
    $failureTypeFailCount = array();
    $failureTypeXpassCount = array();
    $failureTypePassCount = array();
    $i = 0;
    foreach ($testHistory as $test) {                                                       // Check each RTA test job history
        if (in_array($configuration, $testConfHistory[$i])) {                               // Check that the configuration is available for each history
            $resultFile = simplexml_load_file($xmlDirectory . $test . '/' . $xmlFileName);
            foreach ($resultFile->children() as $summary) {                                 // There can be several <summary> tags in the source XML file
                $testCaseCount[$i] = $testCaseCount[$i] + $summary->testCases;
                $failureTypeErrorCount[$i] = $failureTypeErrorCount[$i] + $summary->errors;
                $failureTypeFatalCount[$i] = $failureTypeFatalCount[$i] + $summary->fatals;
                $failureTypeFailCount[$i] = $failureTypeFailCount[$i] + $summary->failures;
                $failureTypeXpassCount[$i] = $failureTypeXpassCount[$i] + $summary->xpasses;
                $failureTypePassCount[$i] = $failureTypePassCount[$i] + $summary->passes;
            }
        } else {
            $testCaseCount[$i] = '-';
            $failureTypeErrorCount[$i] = '-';
            $failureTypeFatalCount[$i] = '-';
            $failureTypeFailCount[$i] = '-';
            $failureTypeXpassCount[$i] = '-';
            $failureTypePassCount[$i] = '-';
        }
        $i++;
    }
    $testJobHistory[HISTORYTESTCASECOUNT] = $testCaseCount;
    $testJobHistory[HISTORYERRORCOUNT] = $failureTypeErrorCount;
    $testJobHistory[HISTORYFATALCOUNT] = $failureTypeFatalCount;
    $testJobHistory[HISTORYFAILCOUNT] = $failureTypeFailCount;
    $testJobHistory[HISTORYXPASSCOUNT] = $failureTypeXpassCount;
    $testJobHistory[HISTORYPASSCOUNT] = $failureTypePassCount;
}

/* Print table title row (the same columns to be used in showTableEnd and showXmlResultFailures) */
function showTableTitle()
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
    // Leave the table 'open', to be closed in showTableEnd
}

/* Close the table */
function showTableEnd()
{
    echo '</table>';
}

/* Print summary information from XML files, including the latest and previous available test jobs  */
function showTestHistory($testJobName, $testConfiguration, $testLatestBuild, $testHistory, &$testJobHistory, $rowNumber)
{
    /* First row: Job name, Passes (title), Passes changes, Passes history */
    if ($rowNumber % 2 == 0)
        echo '<tr>';
    else
        echo '<tr class="tableBackgroundColored">';
    echo '<td colspan="2"><b>' . $testJobName . '</b></td>';
    echo '<td class="tableSideBorder tableBottomBorder fontColorGreen"><b>Passes</b></td>';
    if (count($testHistory) > 1) {                              // If history available
        $change = $testJobHistory[HISTORYPASSCOUNT][0] - $testJobHistory[HISTORYPASSCOUNT][1];
        if ($change > 0) {
            $changeCount = '+' . $change;
        }
        if ($change < 0) {
            $changeCount = $change;
        }
        if ($change == 0) {
            $changeCount = '';
        }
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
        /* Check if there are any new or removed failures of any type */
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
        /* Print the changes */
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
    foreach ($testHistory as $test) {
        if ($i <= HISTORYJOBLISTCOUNT - 1) {
            if ($testJobHistory[HISTORYPASSCOUNT][$i] == '-')               // In case the history item is not available (calculation with the failure types result to value '0')
                $testFailureCount = '-';
            else
                $testFailureCount = $testJobHistory[HISTORYERRORCOUNT][$i] + $testJobHistory[HISTORYFATALCOUNT][$i]
                                  + $testJobHistory[HISTORYFAILCOUNT][$i] + $testJobHistory[HISTORYXPASSCOUNT][$i];
            echo '<td class="tableBottomBorderThick tableSideBorder tableCellCentered fontColorRed">' . $testFailureCount . '</td>';
            $i++;
        }
    }
    for ($j=$i; $j<=HISTORYJOBLISTCOUNT - 1; $j++) {    // Fill the table with empty cells for the unavailable history items
        echo '<td class="tableBottomBorderThick"></td>';
    }
    echo '</tr>';
}

/************************************************************/
/* NESTED LEVEL 1: The default view                         */
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

    /* Print the titles and used filters */
    echo '<a href="javascript:void(0);" class="imgLink" onclick="showMessageWindow(\'rta/msgrtahistorylevel1.html\')"><img src="images/info.png" alt="info"></a>&nbsp&nbsp';
    echo '<b>RTA HISTORY SUMMARY:</b><br/><br/>';
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

    if (isset($_SESSION['rtaTestJobCount'])) {

        /* Get data from the session variables */
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

        $testJobHistory = array();

        /* Print table titles */
        showTableTitle();

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
        for ($i=0; $i<$rtaTestJobCount; $i++) {                                 // Check each RTA test job directory (e.g. Qt5_RTA_opensource_installer_tests_linux_32bit) and its test runs (e.g. 220)
            if (strpos($rtaTestJobName[$i], $filterTest) > 0 AND                // Check possible filtering
                strpos($rtaTestJobName[$i], $filterLicense) > 0 AND
                strpos($rtaTestJobName[$i], $filterPlatform) > 0) {
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
                            if ($entry == "." || $entry == "..") {
                                continue;
                            }
                            $filePath = $directory . '/' . $entry;
                            if (is_file($filePath)) {
                                try {                                           // Open an existing phar
                                    $archive  = new PharData($filePath);
                                    foreach (new RecursiveIteratorIterator($archive ) as $file) {
                                        if ($file->getFileName() == RESULTXMLFILENAME) {        // Check for the summary file
                                            // Get the history data
                                            $filePathPhar = 'phar://' . substr($directory, 0, strripos($directory, "/") + 1);
                                            $fileName = $entry . '/' . $file->getFileName();
                                            $configuration = substr($entry, 0, strpos($entry, TARFILENAMEEXTENSION));
                                            saveXmlHistory($configuration, $rtaTestConfsHistory[$j], $filePathPhar,
                                                           $rtaTestHistoryNumbers[$j], $fileName, $testJobHistory);
                                            // Print the history data
                                            showTestHistory($rtaTestJobName[$i], $configuration, $rtaTestJobLatestBuild[$j],
                                                            $rtaTestHistoryNumbers[$j], $testJobHistory, $k);
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

        /* Show summary and close the table */
        showTableEnd();

    } else {
        echo '<br>Filter values not ready or they are expired, please <a href="javascript:void(0);" onclick="reloadFilters()">reload</a> ...';
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