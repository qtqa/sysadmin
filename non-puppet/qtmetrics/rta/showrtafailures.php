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

define("RESULTXMLFILENAMEPREFIX", "result");    // The result file name starts with this string
define("TARFILENAMEEXTENSION", ".tar.gz");      // Tar file name used for configuration name by removing this extension

define("WORDWRAPCHARSNORMAL", 90);
define("WORDWRAPCHARSBOLD", 80);

define("TESTERRORCOUNT", 0);
define("TESTFATALCOUNT", 1);
define("TESTFAILCOUNT", 2);
define("TESTXPASSCOUNT", 3);
define("TESTPASSCOUNT", 4);

/* Save the failure information for a test job from XML file or files (in the latter case this function is called several times in a row)
   Listed failures are: ERROR, FATAL, FAIL and UNEXPECTED_PASS (=XPASS) */
function saveXmlFailures($xmlResultFile, &$timestamp, &$failureDescription, &$testJobSummary)
{
    define("BUILDNUMBERTITLE", "nstaller build number:");           // The leading "I" left out on purpose
    $resultFile = simplexml_load_file($xmlResultFile);
    foreach ($resultFile->children() as $test) {                                        // Usually one per each XML result file
        if ($timestamp == "")
            $timestamp = str_replace("T", "&nbsp;&nbsp;", $test->prolog['time']);       // Just to improve readability
        foreach ($test->children() as $testCase) {
            $name = $testCase['name'];
            /* ERROR or FATAL (from <message type="ERROR" or "FATAL") */
            foreach ($testCase->message as $message) {
                if ($message['type'] == "ERROR" OR $message['type'] == "FATAL") {
                    $failureDescription = $failureDescription . '<b>' . $message['type'] . ' in ' . $name . '</b><br>';
                    $failureDescription = $failureDescription . '<b>(' . wordwrap($message['file'], WORDWRAPCHARSBOLD, "<br>\n", TRUE) .
                                          ': ' . $message['line'] . ')</b><br>';
                    foreach ($message->description as $description) {                   // Details from each <description> and <description type="DETAILED">
                        if ($description <> "")
                            $failureDescription = $failureDescription . wordwrap($description, WORDWRAPCHARSNORMAL, "<br>\n", TRUE) . '<br>';
                    }
                }
                if ($message['type'] == "ERROR")
                    $testJobSummary[TESTERRORCOUNT]++;
                if ($message['type'] == "FATAL")
                    $testJobSummary[TESTFATALCOUNT]++;
            }
            /* FAIL or UNEXPECTED_PASS plus the PASS (from <verification ... <result type="FAIL" or "XPASS") */
            foreach ($testCase->verification as $verification) {
                foreach ($verification->result as $result) {
                    if ($result['type'] == "FAIL" OR $result['type'] == "XPASS") {
                        $failureDescription = $failureDescription . '<b>' . $result['type'] . ' in ' . $name . '</b><br>';
                        $failureDescription = $failureDescription . '<b>(' . wordwrap($verification['file'], WORDWRAPCHARSBOLD, "<br>\n", TRUE) .
                                              ': ' . $verification['line'] . ')</b><br>';
                        foreach ($result->description as $description) {                // Details from each <description> and <description type="DETAILED">
                            if ($description <> "")
                                $failureDescription = $failureDescription . wordwrap($description, WORDWRAPCHARSNORMAL, "<br>\n", TRUE) . '<br>';
                        }
                    }
                    if ($result['type'] == "FAIL")
                        $testJobSummary[TESTFAILCOUNT]++;
                    if ($result['type'] == "XPASS")
                        $testJobSummary[TESTXPASSCOUNT]++;
                    if ($result['type'] == "PASS")
                        $testJobSummary[TESTPASSCOUNT]++;
                }
            }
        }
        /* There may also be high level messages outside the testCase scope */
        foreach ($test->message as $message) {
            if ($message['type'] == "ERROR" OR $message['type'] == "FATAL" OR $message['type'] == "FAIL" OR $message['type'] == "XPASS") {
                $testJobSummary[TESTFATALCOUNT]++;
                $failureDescription = $failureDescription . '<b>' . $message['type'] . ' message</b><br>';
                foreach ($message->description as $description) {                       // Details from each <description type="DETAILED">
                    if ($description['type'] == "DETAILED")
                        $failureDescription = $failureDescription . wordwrap($description, WORDWRAPCHARSNORMAL, "<br>\n", TRUE) . '<br>';
                }
            }
        }
    }
}

/* Print table title row (the same columns to be used in showTestFailuresTableEnd and showTestFailures) */
function showTestFailuresTableTitle()
{
    echo '<table class="fontSmall">';
    echo '<tr class="tableBottomBorder">';
    echo '<th>Test Job</th>';
    echo '<th class="tableSideBorder">Failure Description</th>';
    echo '<th class="tableSideBorder">Summary</th>';
    echo '</tr>';
    // Leave the table 'open', to be closed in showTestFailuresTableEnd
}

/* Close the table */
function showTestFailuresTableEnd()
{
    echo '<tr class="tableTopBorder">';
    echo '<td></td>';           // Test Job
    echo '<td></td>';           // Failure Description
    echo '<td></td>';           // Summary
    echo '</tr>';
    echo '</table>';
}

/* Print failure information */
function showTestFailures($testJobName, $testConfiguration, $buildNumber, $testHistoryNumber, $timestamp, $failureDescription,
                          $testJobSummary, $rowNumber)
{
    if ($rowNumber % 2 == 0)
        echo '<tr>';
    else
        echo '<tr class="tableBackgroundColored">';
    if (strpos($testJobName, "enterprise") !== FALSE)
        $testHistoryNumberLink = PACKAGINGJENKINSENTERPRISE;
    else
        $testHistoryNumberLink = PACKAGINGJENKINSOPENSOURCE;
    if ($testHistoryNumberLink != "")
        $testHistoryNumberLink = $testHistoryNumberLink . 'job/' . $testJobName . '/' . $testHistoryNumber . '/cfg=' . $testConfiguration . '/squishReport/';
    echo '<td><b>' . $testJobName . '</b><br><br>';                                         // Test job info
    echo '<table>';
    echo '<tr><td><b>Job start time: </b></td><td>' . $timestamp . '</td></tr>';
    echo '<tr><td><b>Configuration: </b></td><td>' . $testConfiguration . '</td></tr>';
    echo '<tr><td><b>Installer build number: </b></td><td>' . $buildNumber . '</td></tr>';
    echo '<tr><td><b>Jenkins build history: </b></td><td><a href="' . $testHistoryNumberLink . '" target="_blank">' . $testHistoryNumber . ' (open squish report)</a></td></tr>';
    echo '</table>';
    echo '</td>';
    echo '<td class="tableSideBorder">' . $failureDescription . '<br></td>';                // Failure Description
    echo '<td class="tableSideBorder">';                                                    // Summary totals
    echo '<table>';
    echo '<tr class="fontColorGreen"><td><b>PASSes: </b></td><td>' . $testJobSummary[TESTPASSCOUNT] . '</td></tr>';
    echo '<tr><td><b>ERRORs: </b></td><td>' . $testJobSummary[TESTERRORCOUNT] . '</td></tr>';
    echo '<tr><td><b>FAILs: </b></td><td>' . $testJobSummary[TESTFAILCOUNT] . '</td></tr>';
    echo '<tr><td><b>FATALs: </b></td><td>' . $testJobSummary[TESTFATALCOUNT] . '</td></tr>';
    echo '<tr><td><b>XPASSes: </b></td><td>' . $testJobSummary[TESTXPASSCOUNT] . '</td></tr>';
    echo '</table>';
    echo '</td>';
    echo '</tr>';
}

/************************************************************/
/* FLAT VIEW: The latest RTA failures                       */
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
    echo '<a href="javascript:void(0);" class="imgLink" onclick="showMessageWindow(\'rta/msgrtafailures.html\')"><img src="images/info.png" alt="info"></a>&nbsp&nbsp';
    echo '<b>LATEST RTA FAILURES:</b><br/><br/>';
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
        $rtaTestJobCount = 0;
        $rtaTestJobId = array();
        $rtaTestJobName = array();
        $rtaTestHistoryNumbers = array();
        $rtaTestHistoryMin = array();
        $rtaTestHistoryMax = array();
        $rtaTestJobCount = $_SESSION['rtaTestJobCount'];
        $rtaTestJobId = $_SESSION['rtaTestJobId'];
        $rtaTestJobName = $_SESSION['rtaTestJobName'];
        $rtaTestJobLatestBuild = $_SESSION['rtaTestJobLatestBuild'];
        $rtaTestHistoryNumbers = $_SESSION['rtaTestHistoryNumbers'];
        $rtaTestHistoryMin = $_SESSION['rtaTestHistoryMin'];
        $rtaTestHistoryMax = $_SESSION['rtaTestHistoryMax'];

        $testJobSummary = array();

        /* Print table titles */
        showTestFailuresTableTitle();

        /* Check possible filtering */
        $k = 0;
        if ($test == "All")
            $filterTest = '_';                          // Set the string to be found in the test job name (i.e. directory name)
        else
            $filterTest = '_' . $test . '_';            // -,,-
        if ($license == "All")
            $filterLicense = '_';                       // -,,-
        else
            $filterLicense = '_' . $license . '_';      // -,,-
        if ($platform == "All")
            $filterPlatform = '_';                      // -,,-
        else
            $filterPlatform = '_' . $platform . '_';    // -,,-
        for ($i=0; $i<$rtaTestJobCount; $i++) {         // Check each RTA test job directory (e.g. Qt5_RTA_opensource_installer_tests_linux_32bit) and its test runs (e.g. 220)
            if (strpos($rtaTestJobName[$i], $filterTest) > 0 AND
                strpos($rtaTestJobName[$i], $filterLicense) > 0 AND
                strpos($rtaTestJobName[$i], $filterPlatform) > 0) {

                /* Loop the directories in sorted order ($rtaTestJobName is sorted, other data linked with the $rtaTestJobId) */
                $j = $rtaTestJobId[$i];
                $rtaTestJobDirectory = $rtaXmlBaseDir . $rtaTestJobName[$i];
                $directories = new RecursiveIteratorIterator(
                    new ParentIterator(
                        new RecursiveDirectoryIterator($rtaTestJobDirectory)),
                        RecursiveIteratorIterator::SELF_FIRST);
                foreach ($directories as $directory) {                          // Check each RTA test job history (e.g. 220, 219, 218)
                    $dirName = substr($directory, strripos($directory, "/") + 1);
                    if ($dirName == $rtaTestHistoryMax[$j]) {                   // Check the latest run only
                        $handle = opendir($directory);
                        while (($entry = readdir($handle)) !== FALSE) {         // Check the results in a tar.gz file (e.g. linux-g++-Ubuntu11.10-x86.tar.gz)
                            if ($entry == "." || $entry == "..") {
                                continue;
                            }
                            $timestamp = '';
                            $failureDescription = '';
                            $testJobSummary[TESTERRORCOUNT] = 0;
                            $testJobSummary[TESTFATALCOUNT] = 0;
                            $testJobSummary[TESTFAILCOUNT] = 0;
                            $testJobSummary[TESTXPASSCOUNT] = 0;
                            $testJobSummary[TESTPASSCOUNT] = 0;
                            $filePath = $directory . '/' . $entry;
                            if (is_file($filePath)) {
                                try {                                           // Open an existing phar
                                    $archive  = new PharData($filePath);
                                    foreach (new RecursiveIteratorIterator($archive ) as $file) {
                                        if (stripos($file->getFileName(), RESULTXMLFILENAMEPREFIX) === 0) {        // Check for the result file (e.g. result_10_08_17.446.xml)
                                            // Get the failure data (Note: May be several XML files)
                                            $filePathPhar = 'phar://' . $directory . '/' . $entry . '/' . $file->getFileName();
                                            saveXmlFailures($filePathPhar, $timestamp, $failureDescription, $testJobSummary);
                                        }
                                    }
                                    // Print the failure data
                                    $configuration = substr($entry, 0, strpos($entry, TARFILENAMEEXTENSION));
                                    showTestFailures($rtaTestJobName[$i], $configuration, $rtaTestJobLatestBuild[$j],
                                                     $dirName, $timestamp, $failureDescription, $testJobSummary, $k);
                                    $k++;
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
        showTestFailuresTableEnd();

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