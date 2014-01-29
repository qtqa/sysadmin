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

include "definitions.php";

/* Save the data for a test job from a specific 'download' XML file (identifying the 'download' result file from a 'normal' result file can be
   done only by opening the file)
   NOTE: The reading should be kept at minimum because this is run on page download and may therefore affect the initial delay (called when
   downloading the filters)
   Input:   $xmlResultFile  (string)  file name with complete directory path (and 'phar://' prefix for files in a tar.gz file)
            $buildNumber    (integer) installer build number (based on possible previous calls of this function)
   Output:  $buildNumber    (integer) installer build number
*/
function saveDownloadXmlData($xmlResultFile, &$buildNumber)
{
    if ($buildNumber == 0) {
        $resultFile = simplexml_load_file($xmlResultFile);
        foreach ($resultFile->children() as $test) {    // Usually one per each XML result file
            if ($test['name'] == TESTDOWNLOAD) {        // Optimization: Check only from the dedicated result file where the installer build number is saved (small size compared to normal files)
                foreach ($test->children() as $testCase) {
                    /* Build number (from <message type="LOG"; appears only once per result file, or in one result file in case of several files) */
                    foreach ($testCase->message as $message) {
                        if ($message['type'] == "LOG") {
                            if (strpos($message->description, BUILDNUMBERTITLE) > 0) {
                                $buildNumber = substr($message->description, strpos($message->description, ":") + 2);
                                break 3;                    // Build number found, exit the search (3 loops up)
                            }
                        }
                    }
                }
            }
        }
    }
}

/* Save the failure information for a test job from result_***.xml file or files (in the latter case this function is called several times in a row)
   Listed failures are: ERROR, FATAL, FAIL and UNEXPECTED_PASS (=XPASS)
   Input:   $xmlResultFile      (string)  file name with complete directory path (and 'phar://' prefix for files in a tar.gz file)
            $timestamp          (string)  start time, the earliest (in case of possible previous calls of this function)
            $buildNumber        (integer) installer build number (based on possible previous calls of this function)
            $failureDescription (array)   [failure] the list of failures (based on possible previous calls of this function)
            $testJobSummary     (array)   [type] the number of passes and each failure type (based on possible previous calls of this function)
   Output:  $timestamp          (string)  start time, updated if earlier timestamp found
            $buildNumber        (integer) installer build number, updated if not previously found
            $failureDescription (array)   [failure] the list of failures, new failures added to the end of list
            $testJobSummary     (array)   [type] the number of passes and each failure type, previous counts increased
*/
/* Failure data items */
define("TESTERRORCOUNT", 0);
define("TESTFATALCOUNT", 1);
define("TESTFAILCOUNT", 2);
define("TESTXPASSCOUNT", 3);
define("TESTPASSCOUNT", 4);
function saveXmlFailures($xmlResultFile, &$timestamp, &$buildNumber, &$failureDescription, &$testJobSummary)
{
    // This function can be used both to start from zero or to continue from previous search (initialization of $i)
    $i = $testJobSummary[TESTERRORCOUNT] + $testJobSummary[TESTFATALCOUNT] + $testJobSummary[TESTFAILCOUNT] + $testJobSummary[TESTXPASSCOUNT];
    $resultFile = simplexml_load_file($xmlResultFile);
    foreach ($resultFile->children() as $test) {                                        // Usually one per each XML result file
        $timestampNew = str_replace("T", "&nbsp;&nbsp;", $test->prolog['time']);        // Just to improve readability
        if ($timestamp == "" OR $timestampNew < $timestamp)                             // if not saved yet or if earlier than already saved
            $timestamp = $timestampNew;
        foreach ($test->children() as $testCase) {
            $name = $testCase['name'];
            /* ERROR or FATAL (from <message type="ERROR" or "FATAL") */
            foreach ($testCase->message as $message) {
                if ($message['type'] == "ERROR" OR $message['type'] == "FATAL") {
                    $failureDescription[$i] = $failureDescription[$i] . '<b>' . $message['type'] . ' in ' . $name . '</b><br>';
                    $failureDescription[$i] = $failureDescription[$i] . '<b>(' . wordwrap($message['file'], WORDWRAPCHARSBOLD, "<br>\n", TRUE) .
                                          ': ' . $message['line'] . ')</b><br>';
                    foreach ($message->description as $description) {                   // Details from each <description> and <description type="DETAILED">
                        if ($description <> "")
                            $failureDescription[$i] = $failureDescription[$i] . wordwrap($description, WORDWRAPCHARSNORMAL, "<br>\n", TRUE) . '<br>';
                    }
                    $i++;                                                               // Each failure to be separate item in the list
                }
                if ($message['type'] == "ERROR")
                    $testJobSummary[TESTERRORCOUNT]++;
                if ($message['type'] == "FATAL")
                    $testJobSummary[TESTFATALCOUNT]++;
                if ($buildNumber == 0) {                                                // Check build number if not yet found
                    if (strpos($message->description, BUILDNUMBERTITLE) > 0) {
                        $buildNumber = substr($message->description, strpos($message->description, ":") + 2);
                    }
                }
            }
            /* FAIL or UNEXPECTED_PASS plus the PASS (from <verification ... <result type="FAIL" or "XPASS") */
            foreach ($testCase->verification as $verification) {
                foreach ($verification->result as $result) {
                    if ($result['type'] == "FAIL" OR $result['type'] == "XPASS") {
                        $failureDescription[$i] = $failureDescription[$i] . '<b>' . $result['type'] . ' in ' . $name . '</b><br>';
                        $failureDescription[$i] = $failureDescription[$i] . '<b>(' . wordwrap($verification['file'], WORDWRAPCHARSBOLD, "<br>\n", TRUE) .
                                              ': ' . $verification['line'] . ')</b><br>';
                        foreach ($result->description as $description) {                // Details from each <description> and <description type="DETAILED">
                            if ($description <> "")
                                $failureDescription[$i] = $failureDescription[$i] . wordwrap($description, WORDWRAPCHARSNORMAL, "<br>\n", TRUE) . '<br>';
                        }
                        $i++;                                                           // Each failure to be separate item in the list
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
                $failureDescription[$i] = $failureDescription[$i] . '<b>' . $message['type'] . ' message</b><br>';
                foreach ($message->description as $description) {                       // Details from each <description type="DETAILED">
                    if ($description['type'] == "DETAILED")
                        $failureDescription[$i] = $failureDescription[$i] . wordwrap($description, WORDWRAPCHARSNORMAL, "<br>\n", TRUE) . '<br>';
                }
                $i++;                                                                  // Each failure to be separate item in the list
            }
            if ($message['type'] == "ERROR")
                $testJobSummary[TESTERRORCOUNT]++;
            if ($message['type'] == "FATAL")
                $testJobSummary[TESTFATALCOUNT]++;
            if ($result['type'] == "FAIL")
                $testJobSummary[TESTFAILCOUNT]++;
            if ($result['type'] == "XPASS")
                $testJobSummary[TESTXPASSCOUNT]++;
        }
    }
    sort($failureDescription);                                                          // Sort the failures based on types (first word in description) for better readability
}

/* Save summary information for a test job from summary.xml files, including the latest and previous available test jobs
   Input:   $configuration      (string)  to check that the configuration tar.gz file is available for each test job run
            $testConfHistory    (array)   [number] configuration per each history number in the test job
            $xmlDirectory       (string)  the directory path (and 'phar://' prefix for files in a tar.gz file) prior to the history number
                                          (with tailing "/")
            $testHistory        (array)   [number] the test history numbers from latest to older ones (to be used for complete XML file name path
                                          after the $xmlDirectory)
            $xmlFileName        (string)  tar.gz file name and the XML file name to be opened (to be used for complete XML file name path after
                                          each test history number)
   Output:  $testJobHistory     (array)   [type][number] the number of passes and each failure [type] for each test history [number].
                                          Value "-" used if data is not available for a specific configuration in the related test job run.
*/
/* History data items */
define("HISTORYTESTCASECOUNT", 0);
define("HISTORYERRORCOUNT", 1);
define("HISTORYFATALCOUNT", 2);
define("HISTORYFAILCOUNT", 3);
define("HISTORYXPASSCOUNT", 4);
define("HISTORYPASSCOUNT", 5);
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

?>
