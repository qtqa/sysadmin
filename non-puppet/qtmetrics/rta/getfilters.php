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
include(__DIR__.'/../commondefinitions.php');
include(__DIR__.'/../connectiondefinitions.php');
include "metricsboxdefinitions.php";

define("RESULTXMLFILENAMEPREFIX", "result");            // The result file name starts with this string
define("TARFILENAMEEXTENSION", ".tar.gz");              // Tar file name used for configuration name by removing this extension
define("RTATESTHISTORYNUMBERMAX", 999);                 // The biggest Jenkins build history number used to identify build number directory names from main level ones
define("TESTTYPESEPARATOR", "_tests_");                 // String to separate the test type and platform (e.g. "Qt5_RTA_opensource_installer_tests_linux_32bit")
define("LICENSETYPESEPARATOR", "_RTA_");                // String to separate the license type
define("BUILDNUMBERTITLE", "nstaller build number:");   // String to tag the build number; the leading "I" left out on purpose (e.g. "Installer build number: 216")

/* Save the data for a test job from XML file or files (in the latter case this function is called several times in a row) */
function saveXmlData($xmlResultFile, &$buildNumber)
{
    $resultFile = simplexml_load_file($xmlResultFile);
    foreach ($resultFile->children() as $test) {        // Usually one per each XML result file
        foreach ($test->children() as $testCase) {
            $name = $testCase['name'];
            /* Build number (from <message type="LOG"; appears only once per result file, or in one result file in case of several files) */
            foreach ($testCase->message as $message) {
                if ($buildNumber == 0) {
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

$timeStart = microtime(true);

/* Get filter values only if the source data directory is set */
$rtaXmlBaseDir = RTAXMLBASEDIRECTORY;
if ($rtaXmlBaseDir != "") {

    /* Loop the directory structure to save basic data for each RTA test job to session variables (so these are updated only once per session) */
    if (!isset($_SESSION['rtaTestJobCount'])) {
        $directories = new RecursiveIteratorIterator(
            new ParentIterator(
                new RecursiveDirectoryIterator($rtaXmlBaseDir)),
                RecursiveIteratorIterator::SELF_FIRST);
        $i = RTATESTHISTORYNUMBERMAX + 1;
        $rtaTestJobCount = 0;
        $rtaTestJobId = array();
        $rtaTestJobName = array();
        $rtaTestHistoryNumbers = array();
        $rtaTestHistoryMin = array();
        $rtaTestHistoryMax = array();
        /* Save the data from the directory structure (without opening the tar.gz files) */
        foreach ($directories as $directory) {
            $dirName = substr($directory, strripos($directory, "/") + 1);
            // Main level test job directory checked first to initialize the variables ...
            if (strlen((string)$dirName) > strlen((string)RTATESTHISTORYNUMBERMAX)) {   // Directory name is longer than the pure build number directory name
                if ($i == RTATESTHISTORYNUMBERMAX + 1)
                    $i = 0;
                else
                    $i++;
                $rtaTestJobId[$i] = $i;
                $rtaTestJobName[$i] = $dirName;
                $rtaTestHistoryNumbers[$i] = array();
                $rtaTestHistoryMin[$i] = RTATESTHISTORYNUMBERMAX;
                $rtaTestHistoryMax[$i] = 0;
            // ... and then its history directories
            } else {
                $rtaTestHistoryNumbers[$i][] = $dirName;
                if ($dirName < $rtaTestHistoryMin[$i])
                    $rtaTestHistoryMin[$i] = $dirName;
                if ($dirName > $rtaTestHistoryMax[$i])
                    $rtaTestHistoryMax[$i] = $dirName;
            }
        }
        /* Get the data by checking the tar.gz files */
        $i = RTATESTHISTORYNUMBERMAX + 1;
        $rtaTestJobLatestBuild = array();                                   // Latest installer build number
        $rtaTestConfs = array();                                            // List of configurations per each test job
        $rtaTestConfsHistory = array();                                     // List of configurations per each test job and its history number
        foreach ($directories as $directory) {
            $dirName = substr($directory, strripos($directory, "/") + 1);
            // Main level test job directory checked first to initialize the variables ...
            if (strlen((string)$dirName) > strlen((string)RTATESTHISTORYNUMBERMAX)) {   // Directory name is longer than the pure build number directory name
                if ($i == RTATESTHISTORYNUMBERMAX + 1)
                    $i = 0;
                else
                    $i++;
                $j = 0;
                $rtaTestJobLatestBuild[$i] = 0;
                $rtaTestConfs[$i] = array();                                // Per each test job
                $rtaTestConfsHistory[$i] = array();                         // Per each test job
            // ... and then its history directories
            } else {
                $rtaTestConfsHistory[$i][$j] = array();                     // Per each history number in a test job
                $handle = opendir($directory);
                while (($entry = readdir($handle)) !== FALSE) {             // Check the results in a tar.gz file (e.g. linux-g++-Ubuntu11.10-x86.tar.gz)
                    if ($entry == "." || $entry == "..") {
                        continue;
                    }
                    $buildNumber = 0;
                    $configuration = substr($entry, 0, strpos($entry, TARFILENAMEEXTENSION));
                    $rtaTestConfs[$i][] = $configuration;
                    $rtaTestConfsHistory[$i][$j][] = $configuration;
                    if ($dirName == $rtaTestHistoryMax[$i]) {               // Check the latest run only
                        $filePath = $directory . '/' . $entry;
                        if (is_file($filePath)) {
                            try {                                           // Open an existing phar
                                $archive  = new PharData($filePath);
                                foreach (new RecursiveIteratorIterator($archive ) as $file) {
                                    if (stripos($file->getFileName(), RESULTXMLFILENAMEPREFIX) === 0) {        // Check for the result file (e.g. result_10_08_17.446.xml)
                                        // Get the failure data (Note: May be several XML files)
                                        $filePathPhar = 'phar://' . $directory . '/' . $entry . '/' . $file->getFileName();
                                        saveXmlData($filePathPhar, $buildNumber);
                                    }
                                }
                            } catch (Exception $e) {
                                echo 'Could not open Phar: ', $e;
                            }
                        }
                        clearstatcache();
                        $rtaTestJobLatestBuild[$i] = $buildNumber;
                    }
                }
                closedir($handle);
                $j++;
            }
        }
        /* Manipulate the lists */
        $rtaTestJobCount = $i + 1;
        for ($i=0; $i<$rtaTestJobCount; $i++) {                             // Check each RTA test job directory (e.g. Qt5_RTA_opensource_installer_tests_linux_32bit) and its test runs (e.g. 220)
            array_multisort($rtaTestHistoryNumbers[$i], SORT_DESC, $rtaTestConfsHistory[$i]);  // Sort the history numbers in descending order (and keep the linking via their configurations)
            sort($rtaTestConfs[$i]);                                        // Sort alphabetically
            $rtaTestConfs[$i] = array_unique($rtaTestConfs[$i]);            // Remove duplicate values
        }
        /* Save session variables */
        array_multisort($rtaTestJobName, $rtaTestJobId);                    // Sort the test jobs alphabetically (and keep the linking via their Id)
        $_SESSION['rtaTestJobCount'] = $rtaTestJobCount;                    // Save session variables
        $_SESSION['rtaTestJobId'] = $rtaTestJobId;
        $_SESSION['rtaTestJobName'] = $rtaTestJobName;
        $_SESSION['rtaTestJobLatestBuild'] = $rtaTestJobLatestBuild;
        $_SESSION['rtaTestConfs'] = $rtaTestConfs;
        $_SESSION['rtaTestConfsHistory'] = $rtaTestConfsHistory;
        $_SESSION['rtaTestHistoryNumbers'] = $rtaTestHistoryNumbers;
        $_SESSION['rtaTestHistoryMin'] = $rtaTestHistoryMin;
        $_SESSION['rtaTestHistoryMax'] = $rtaTestHistoryMax;
    }

    /* Get the filter values from the list of RTA test job names */
    $filterValuesTest = array();                                            // Value list for the filter
    $filterValuesLicense = array();                                         // -,,-
    $filterValuesPlatform = array();                                        // -,,-
    $allValuesTest = ".";                                                   // List to ensure one value appears only once
    $allValuesLicense = ".";                                                // -,,-
    $allValuesPlatform = ".";                                               // -,,-
    foreach ($_SESSION['rtaTestJobName'] as $key=>$value) {
        /* Test type */
        $str = substr($value, 0, stripos($value, TESTTYPESEPARATOR));       // Cut the string after test type
        $str = substr($str, strripos($str, "_") + 1);                       // Cut the string before test type
        if (stripos($allValuesTest, '.' . $str . '.') === FALSE)            // Add only if not yet in the list
            $filterValuesTest[] = $str;
        $allValuesTest = $allValuesTest . $str . '.';
        /* License type */
        $str = substr($value, stripos($value, LICENSETYPESEPARATOR) + strlen(LICENSETYPESEPARATOR));    // Cut the string before license type
        $str = substr($str, 0, stripos($str, "_"));                         // Cut the string after license type
        if (stripos($allValuesLicense, '.' . $str . '.') === FALSE)         // Add only if not yet in the list
            $filterValuesLicense[] = $str;
        $allValuesLicense = $allValuesLicense . $str . '.';
        /* Platform */
        $str = substr($value, stripos($value, TESTTYPESEPARATOR) + strlen(TESTTYPESEPARATOR));          // Cut the string before platform
        $str = substr($str, 0, stripos($str, "_"));                         // Cut the string after platform
        if (stripos($allValuesPlatform, '.' . $str . '.') === FALSE)        // Add only if not yet in the list
            $filterValuesPlatform[] = $str;
        $allValuesPlatform = $allValuesPlatform . $str . '.';
    }
    sort($filterValuesTest);
    sort($filterValuesLicense);
    sort($filterValuesPlatform);
}

/* Print the buttons and filters */
echo '<div id="filterTitle">';
echo '<b>FILTERS:</b>';
echo '</div>';
echo '<div id="filterButtons">';
echo '<button onclick="clearFilters()">Clear selections</button>';
echo '&nbsp;';
echo '<button onclick="reloadFilters()">Reload</button>';
echo '</div>';
echo '<div id="filterFields">';
echo '<form name="form">';
echo '<div id="filterFieldsLeft">';
echo '<label>Test Type:</label>';
echo '<select name="test" id="test" onchange="filterTest(this.value)">';
    echo "<option value=\"All\">All</option>";
    if ($rtaXmlBaseDir != "") {
        foreach ($filterValuesTest as $key=>$value)
            echo "<option value=\"$value\">$value</option>";
    }
echo '</select>';
echo '<br/>';
echo '<label>License Type:</label>';
echo '<select name="license" id="license" onchange="filterLicense(this.value)">';
    echo "<option value=\"All\">All</option>";
    if ($rtaXmlBaseDir != "") {
        foreach ($filterValuesLicense as $key=>$value)
            echo "<option value=\"$value\">$value</option>";
    }
echo '</select>';
echo '<br/>';
echo '<label>Platform:</label>';
echo '<select name="platform" id="platform" onchange="filterPlatform(this.value)">';
    echo "<option value=\"All\">All</option>";
    if ($rtaXmlBaseDir != "") {
        foreach ($filterValuesPlatform as $key=>$value)
            echo "<option value=\"$value\">$value</option>";
    }
echo '</select>';
echo '</div>';
echo '<div id="filterFieldsMiddle">';
echo '</div>';
echo '</form>';
echo '<div id="filterFieldsRight">';
echo '</div>';
echo '</form>';
echo '</div>';

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
