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
include "definitions.php";
include "functions.php";

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

        /* Step 1. Save the data from the directory structure */
        $i = -1;                                                            // Initialize to identify the loop start (increased below to start from 0)
        $rtaTestJobCount = 0;
        $rtaTestJobId = array();
        $rtaTestJobName = array();
        $rtaTestHistoryNumbers = array();
        $rtaTestHistoryMin = array();
        $rtaTestHistoryMax = array();
        foreach ($directories as $directory) {
            $dirName = substr($directory, strripos($directory, "/") + 1);   // A) Main level test job directory checked first to initialize the variables ...
            // Step 1.1: Save test job name based on the main level test job directory name
            if (!is_numeric($dirName)) {                                    // Directory name is a job name (not a build number directory name)
                $i++;                                                       // Used for table index
                $rtaTestJobId[$i] = $i;
                $rtaTestJobName[$i] = $dirName;
                $rtaTestHistoryNumbers[$i] = array();                       // Initialize
                $rtaTestHistoryMin[$i] = RTATESTHISTORYNUMBERMAX;           // -,,- (to count down to min)
                $rtaTestHistoryMax[$i] = 0;                                 // -,,- (to count up to max)
            }
            // Step 1.2: Save the first and last Jenkins test history number of each test job based on the subdirectory names
            else {                                                          // B) ... and then its history directories
                $rtaTestHistoryNumbers[$i][] = $dirName;
                if ($dirName < $rtaTestHistoryMin[$i])
                    $rtaTestHistoryMin[$i] = $dirName;
                if ($dirName > $rtaTestHistoryMax[$i])
                    $rtaTestHistoryMax[$i] = $dirName;
            }
        }
        /* Step 2. Save the data from the tar.gz file names and their content (done in a separate loop to optimize the speed of execution) */
        $i = -1;                                                            // Initialize to identify the loop start (increased below to start from 0)
        $rtaTestJobLatestBuild = array();                                   // Latest installer build number
        $rtaTestConfs = array();                                            // List of configurations per each test job
        $rtaTestConfsHistory = array();                                     // List of configurations per each test job and its history number
        foreach ($directories as $directory) {
            $dirName = substr($directory, strripos($directory, "/") + 1);   // A) Main level test job directory checked first to initialize the variables ...
            if (!is_numeric($dirName)) {                                    // Directory name is a job name (not a build number directory name)
                $i++;                                                       // Used for table index
                $j = 0;                                                     // Initialize
                $rtaTestJobLatestBuild[$i] = 0;                             // -,,-
                $rtaTestConfs[$i] = array();                                // -,,- (per each test job)
                $rtaTestConfsHistory[$i] = array();                         // -,,- (per each test job)
            } else {                                                        // B) ... and then its history directories
            // Step 2.1: Save the configuration names of each test job based on the tar.gz file name
                $rtaTestConfsHistory[$i][$j] = array();                     // Per each history number in a test job
                $handle = opendir($directory);
                while (($entry = readdir($handle)) !== FALSE) {             // Check the results in a tar.gz file (e.g. linux-g++-Ubuntu11.10-x86.tar.gz)
                    if ($entry == "." || $entry == "..") {
                        continue;
                    }
                    $buildNumber = 0;
                    $configuration = substr($entry, 0, strpos($entry, TARFILENAMEEXTENSION));
                    $rtaTestConfs[$i][] = $configuration;                   // Per each test job
                    $rtaTestConfsHistory[$i][$j][] = $configuration;        // Per each history number in a test job
            // Step 2.2: Save the installer build number of each test job for the latest test run from the result XML files in the tar.gz files
                    if ($dirName == $rtaTestHistoryMax[$i]) {               // Check the latest run only
                        $filePath = $directory . '/' . $entry;
                        if (is_file($filePath)) {
                            try {                                           // Open an existing phar
                                $archive  = new PharData($filePath);
                                foreach (new RecursiveIteratorIterator($archive ) as $file) {               // The summary and several result files
                                    if (stripos($file->getFileName(), RESULTXMLFILENAMEPREFIX) === 0) {     // Check for the result files (e.g. result_10_08_17.446.xml)
                                        $filePathPhar = 'phar://' . $directory . '/' . $entry . '/' . $file->getFileName();
                                        saveDownloadXmlData($filePathPhar, $buildNumber);                   // Get the installer build number from the 'download' result file
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
        /* Sort the lists */
        $rtaTestJobCount = $i + 1;
        for ($i=0; $i<$rtaTestJobCount; $i++) {                             // Check each RTA test job directory (e.g. Qt5_RTA_opensource_installer_tests_linux_32bit) and its test runs (e.g. 220)
            array_multisort($rtaTestHistoryNumbers[$i], SORT_DESC, $rtaTestConfsHistory[$i]);  // Sort the history numbers in descending order (and keep the linking via their configurations)
            sort($rtaTestConfs[$i]);                                        // Sort alphabetically
            $rtaTestConfs[$i] = array_unique($rtaTestConfs[$i]);            // Remove duplicate values
        }
        array_multisort($rtaTestJobName, $rtaTestJobId);                    // Sort the test jobs alphabetically (and keep the linking via their Id)
        /* Save the session variables */
        $_SESSION['rtaTestJobCount'] = $rtaTestJobCount;
        $_SESSION['rtaTestJobId'] = $rtaTestJobId;
        $_SESSION['rtaTestJobName'] = $rtaTestJobName;
        $_SESSION['rtaTestJobLatestBuild'] = $rtaTestJobLatestBuild;
        $_SESSION['rtaTestConfs'] = $rtaTestConfs;
        $_SESSION['rtaTestConfsHistory'] = $rtaTestConfsHistory;
        $_SESSION['rtaTestHistoryNumbers'] = $rtaTestHistoryNumbers;
        $_SESSION['rtaTestHistoryMin'] = $rtaTestHistoryMin;
        $_SESSION['rtaTestHistoryMax'] = $rtaTestHistoryMax;
    }

    /* Get the filter values from the list of RTA test job names (that were saved above) */
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

/* Print the buttons */
echo '<div id="filterTitle">';
echo '<b>FILTERS:</b>';
echo '</div>';
echo '<div id="filterButtons">';
echo '<button onclick="clearFilters()">Clear selections</button>';
echo '&nbsp;';
echo '<button onclick="reloadFilters()">Reload</button>';
echo '</div>';

/* Print the filters */
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
echo '<select name="job" id="job" onchange="filterJob(this.value)" class="hiddenElement">';  // Note: The filter is hidden, used via history box links instead
    echo "<option value=\"All\">All</option>";
    if ($rtaXmlBaseDir != "") {
        for ($i=0; $i<$rtaTestJobCount; $i++) {
            $j = $rtaTestJobId[$i];
            foreach ($rtaTestConfs[$j] as $key=>$valueConf) {
                $value = $rtaTestJobName[$i] . FILTERSEPARATOR . 'conf'. FILTERVALUESEPARATOR . $valueConf;   // Include both job name and configuration
                echo "<option value=\"$value\">$value</option>";
            }
        }
    }
echo '</select>';
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
