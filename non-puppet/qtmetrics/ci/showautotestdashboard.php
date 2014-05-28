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
include "definitions.php";
include "functions.php";
include(__DIR__.'/../commonfunctions.php');
include "metricsboxdefinitions.php";

/* The structure of array $arrayTestcaseConfs */
define("FAILINGTESTCASECONFNAME", 0);
define("FAILINGTESTCASECONFFAILED", 1);
define("FAILINGTESTCASECONFALL", 2);

/* Set the minimum build number to be checked for a project to optimize the performance (by default only to check the latest CITESTRESULTBUILDCOUNT builds)
   Input:   $latestProjectBuild                      (integer) Latest build number in the project
            $minBuildNumberInDatabase                (integer) The first build number that is available in the database
            $timescaleType                           (string)  The timescale filter
   Return:  (integer) The lowest build number to be checked
*/
function setMinBuildNumberToCheck($latestProjectBuild, $minBuildNumberInDatabase, $timescaleType)
{
    if (($latestProjectBuild - $minBuildNumberInDatabase) > CITESTRESULTBUILDCOUNT)     // Read only CITESTRESULTBUILDCOUNT latest builds
        $minBuildNumber = $latestProjectBuild - CITESTRESULTBUILDCOUNT + 1;
    else                                                                                // ... or the first that is available in the database
        $minBuildNumber = $minBuildNumberInDatabase;
    if ($timescaleType <> "All")                                                        // ... but if timescale filter used, use that instead
        $minBuildNumber = $minBuildNumberInDatabase;
    return $minBuildNumber;
}

/* Calculate percentage so that very low but not quite zero result is rounded to 1 and almost 100% but not quite is rounded to 99
   Input:   $numerator                              (integer)  The numerator
            $divider                                (integer)  The divider
   Return:  (integer) percentage (0-100)
*/
function calculatePercentage($numerator, $divider)
{
    $percentage = round(100 * ($numerator / $divider));                                 // Must be rounded to integer for sorting to work
    if ($percentage == 0 AND $numerator > 0)                                            // Not quite 0%
        $percentage = 1;
    if ($percentage == 100 AND $numerator <> $divider)                                  // Not quite 100%
        $percentage = 99;
    return $percentage;
}

/* Set pop-up message to help understanding the shown build scope, and to guide how to change the scope
   Input:   $timescaleType                           (string)  The timescale filter
            $timescaleValue                          (date)    The timescale date filter
   Return:  (string) Pop-up message
*/
function setSeeMoreNote($timescaleType, $timescaleValue)
{
    $seeMoreNote = '&nbsp;&nbsp;&nbsp;&raquo; <span class="popupMessage">see more'
        . '<span><b>How to see more builds:</b><br>';
    if ($timescaleType == "All") {
        $seeMoreNote = $seeMoreNote . 'By default the list below includes results checked from the latest ';
        if (CITESTRESULTBUILDCOUNT == 1)
            $seeMoreNote = $seeMoreNote . 'build for clarity and to optimize the performance.';
        else
            $seeMoreNote = $seeMoreNote . CITESTRESULTBUILDCOUNT . ' builds to optimize the performance.';
    } else {
        $seeMoreNote = $seeMoreNote . 'The list below includes the results checked from the builds since '
            . $timescaleValue . '.';
    }
    $seeMoreNote = $seeMoreNote . ' You can include more builds with the timescale filter in the filter box on the top of the page.';
    $seeMoreNote = $seeMoreNote . '</span></span> &raquo;';
    return $seeMoreNote;
}

/* Scan the test result directory for test result zip files and their xml result files
   Input:   $testResultDirectory                     (string)  Full path to the test result directory (containing the project directories)
            $project                                 (string)  Project name filtered as in the test result directory and database
            $minBuildNumber                          (integer) Minimum build number to be checked
            $conf                                    (string)  Configuration name filtered
            $booCheckTestcases                       (boolean) TRUE to open the test result files in the zip file and check the test cases, FALSE just to scan the test result file names in the zip
            $arrayFailingAutotestNames               (array)   List of autotest names as in the test result directory and database
   Output:  $arrayFailingAutotestBuildConfigurations (array)   The list of project build-configuration pairs that have the result xml data
            $arrayFailingAutotestAllBuilds           (array)   The count of builds (in all project configurations and in all builds within the timescale) of each autotest
              The following used only when $booCheckTestcases == TRUE:
                $arrayTestcaseNames                  (array)   The names for test cases (all included)
                $arrayTestcaseFailed                 (array)   The count of fails in different project configuration builds for each test case
                $arrayTestcaseAll                    (array)   The count of different project configuration builds for each test case
                $arrayTestcaseConfs                  (array of arrays) The names, and failed and total count of builds of each configuration for each test case
                $failingTestcaseCount                (integer) The total number of failed test cases in all project configuration builds
                $testcaseCount                       (integer) The total number of test cases in all project configuration builds (any result)
                $arrayInvalidTestResultFiles         (array)   List of test results files that couldn't be opened
   Return:  (integer) The lowest build where the test result xml files are available, or MAXCIBUILDNUMBER if not any found
            (Note: The zip file may be empty -> save the data only if xml files found from the zip)
*/
function readProjectTestResultDirectory(
            $testResultDirectory, $project, $minBuildNumber, $conf, $booCheckTestcases, $arrayFailingAutotestNames,
            &$arrayFailingAutotestBuildConfigurations, &$arrayFailingAutotestAllBuilds,
            &$arrayTestcaseNames, &$arrayTestcaseFailed, &$arrayTestcaseAll, &$arrayTestcaseConfs,
            &$failingTestcaseCount, &$testcaseCount, &$arrayInvalidTestResultFiles)
{
    $arrayFailingTestcaseConfNames = array();
    /* Count the number of autotests */
    $autotestCount = count($arrayFailingAutotestNames);
    /* Check Project directory (structure is e.g. "QtBase_stable_Integration/build_03681/macx-ios-clang_OSX_10.8" */
    $projectTestResultDirectory = $testResultDirectory . $project;
    $minBuildNumberInDirectory = MAXCIBUILDNUMBER;                          // For saving the first build where test result xml files are available
    $booTestResultDirectory = FALSE;
    $arrayAllTestcaseNames = array();
    if (file_exists($projectTestResultDirectory)) {
        $booTestResultDirectory = TRUE;
        $directories = new RecursiveIteratorIterator(
            new ParentIterator(
                new RecursiveDirectoryIterator($projectTestResultDirectory)),
                RecursiveIteratorIterator::SELF_FIRST);
        /* Check each build directory */
        foreach ($directories as $directory) {                              // Check each CI project build history (e.g. "build_00220", "build_00219", "build_00218")
            $dirName = substr($directory, strpos($directory, $project) + strlen($project) + 1);         // Cut to e.g. "build_03681/macx-ios-clang_OSX_10.8"
            $minBuildNumberDirName = CIBUILDDIRECTORYPREFIX . createBuildNumberString($minBuildNumber); // Convert "220" -> "build_00220"
            if (strpos($dirName, CIBUILDDIRECTORYPREFIX) === 0 AND $dirName < $minBuildNumberDirName)
                continue;                                                   // Skip to next directory if not inside the time scope
            if (strlen($dirName) == strlen($minBuildNumberDirName))
                continue;                                                   // Skip the main build directory (to check only the configuration directories under it)
            /* Continue if build belongs to the time scale */
            $handle = opendir($directory);
            while (($entry = readdir($handle)) !== FALSE) {                 // Check the results in zip file
                if ($entry == "." || $entry == "..")
                    continue;
                $configuration = substr($dirName, strlen($minBuildNumberDirName) + 1);                  // Cut to e.g. "macx-ios-clang_OSX_10.8"
                if ($conf <> "All" AND $configuration <> $conf)             // Skip if not the filtered configuration
                    continue;
                $dirNumber = (int)substr($dirName, strlen(CIBUILDDIRECTORYPREFIX), strlen(strval(MAXCIBUILDNUMBER)));    // Cut to e.g. "3681"
                $arrayFailingAutotestBuildConfigurations[] = $dirNumber . FILTERSEPARATOR . $configuration;     // Save each build-configuration pair with xml data to read failure data from database only for these
                if (!in_array($configuration, $arrayFailingTestcaseConfNames))                                  // If configuration name not listed yet ...
                    $arrayFailingTestcaseConfNames[] = $configuration;                                          // ... save it
                $filePath = $directory . '/' . $entry;
                if ($entry == CITESTRESULTSFILE AND is_file($filePath)) {
                    $zip = zip_open($filePath);
                    /* Check if the autotest has been run for this build */
                    while ($zip_entry = zip_read($zip)) {                   // Check all tst_* xml files in the zip
                        $xmlFilePath = zip_entry_name($zip_entry);
                        $xmlFile = basename(zip_entry_name($zip_entry));
                        for ($k=0; $k<$autotestCount; $k++) {               // Loop all failed autotest (Note: The ones that have always passed are excluded)
                            $zipTestFileNameIdentifiers = explode(";",ZIPTESTFILENAMEIDENTIFIERS);
                            $booTestFileNameMatch = FALSE;
                            foreach ($zipTestFileNameIdentifiers as $identifier) {  // Check each identified string for a test file name
                                if (strpos($xmlFile, $arrayFailingAutotestNames[$k] . $identifier) === 0)
                                    $booTestFileNameMatch = TRUE;
                            }
                            if ($booTestFileNameMatch) {                    // Find the match from the list of failed autotests
                                $arrayFailingAutotestAllBuilds[$k]++;       // Increase the count for related autotest
                                if ($minBuildNumberInDirectory > $dirNumber)
                                    $minBuildNumberInDirectory = $dirNumber;        // Save the lowest build where test result xml files are available
                                                                                    // (Note: The zip file may be empty -> save only if xml files found from the zip)

                                /* Open the test result xml files to read the test case results ($booCheckTestcases == TRUE) */
                                if ($booCheckTestcases) {
                                    if (!zip_entry_open($zip, $zip_entry))
                                        die('cannot open zip!');
                                    $xmlResultFile = 'zip://' . $filePath . '#' . $xmlFilePath;
                                    if (!($resultFile = simplexml_load_file($xmlResultFile))) { // Collect the invalid test result files that couldn't be opened
                                        $arrayInvalidTestResultFiles[] = substr($xmlResultFile, strpos($xmlResultFile, $project));
                                    } else {
                                        foreach ($resultFile->children() as $testCase) {
                                            if ($testCase->getName() == "TestFunction") {       // e.g. <TestFunction name="initTestCase">
                                                foreach ($testCase->children() as $result) {
                                                    if ($result->getName() == "Incident") {     // e.g. <Incident type="pass" file="" line="0" /> (inside the TestFunction)
                                                        $testTag = "";
                                                        // Detailed test name can be in the DataTag, e.g. <TestFunction name="test_qBinaryFind">
                                                        //                                                <Incident type="pass" file="" line="0">
                                                        //                                                <DataTag><![CDATA[sorted-duplicate]]></DataTag>
                                                        //                                                </Incident>
                                                        //                                                <Incident ...
                                                        foreach ($result->DataTag as $tag) {
                                                            if ($tag <> "")
                                                                $testTag = '[' . $tag . ']';
                                                        }
                                                        $testcaseFullName = $testCase['name'] . $testTag;               // e.g. test_qBinaryFind[sorted-duplicate]
                                                        /* Find the configuration (the configuration is always available in the $arrayFailingTestcaseConfNames) */
                                                        foreach ($arrayFailingTestcaseConfNames as $key => $confName) {
                                                            if ($confName == $configuration)
                                                                $confId = $key;
                                                        }
                                                        /* Save testcase data */
                                                        $arrayAllTestcaseNames[] = $testcaseFullName;                   // Collect all test cases (duplicate names cleared at the end)
                                                        if (!in_array($testcaseFullName, $arrayTestcaseNames)) { // If not yet listed ...
                                                            $arrayTestcaseNames[] = $testcaseFullName;               // Testcase name
                                                            $testcaseConfData = array(array());
                                                            $testcaseConfData[$confId][FAILINGTESTCASECONFNAME]
                                                                = $arrayFailingTestcaseConfNames[$confId];           // Configuration name for the testcase
                                                            if ($result['type'] == "fail") {
                                                                $arrayTestcaseFailed[]++;                            // Count the number of fails
                                                                $arrayTestcaseAll[]++;                               // Count all (any result)
                                                                $testcaseConfData[$confId][FAILINGTESTCASECONFFAILED]++;
                                                                $testcaseConfData[$confId][FAILINGTESTCASECONFALL]++;
                                                            } else {                                                 // Reserve the index for possible failures from other builds
                                                                $arrayTestcaseFailed[] = 0;
                                                                $arrayTestcaseAll[] = 1;
                                                                $testcaseConfData[$confId][FAILINGTESTCASECONFFAILED] = 0;
                                                                $testcaseConfData[$confId][FAILINGTESTCASECONFALL] = 1;
                                                            }
                                                            $arrayTestcaseConfs[] = $testcaseConfData;               // Save configuration specific data
                                                        } else {                                                     // ... or if already listed
                                                            foreach ($arrayTestcaseNames as $key => $testcaseName) {
                                                                if ($testcaseName == $testcaseFullName) {
                                                                    $arrayTestcaseConfs[$key][$confId][FAILINGTESTCASECONFNAME]
                                                                        = $arrayFailingTestcaseConfNames[$confId];   // Configuration name for the testcase
                                                                    if ($result['type'] == "fail") {
                                                                        $arrayTestcaseFailed[$key]++;                // Count the number of fails
                                                                        $arrayTestcaseAll[$key]++;                   // Count all (any result)
                                                                        $arrayTestcaseConfs[$key][$confId][FAILINGTESTCASECONFFAILED]++;
                                                                        $arrayTestcaseConfs[$key][$confId][FAILINGTESTCASECONFALL]++;
                                                                    } else {
                                                                        $arrayTestcaseAll[$key]++;
                                                                        $arrayTestcaseConfs[$key][$confId][FAILINGTESTCASECONFALL]++;
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        } // endif foreach as testCase
                                    } // endif else simplexml_load_file
                                } // endif booCheckTestcases

                                break;                                      // Match found, break to next file in zip
                            }
                        }
                    }
                    zip_close($zip);
                }
                clearstatcache();
            }
            closedir($handle);
        } // endif foreach as directory

        /* Calculate/arrange return data */
        $arrayFailingAutotestBuildConfigurations = array_unique($arrayFailingAutotestBuildConfigurations);
        $arrayAllTestcaseNames = array_unique($arrayAllTestcaseNames);
        $testcaseCount = count($arrayTestcaseNames);
        foreach ($arrayTestcaseNames as $key => $testcaseName) {
            if ($arrayTestcaseFailed[$key] > 0)
                $failingTestcaseCount++;
        }
        sort($arrayInvalidTestResultFiles);

    } // endif file_exists()
    return $minBuildNumberInDirectory;
}

/************************************************************/
/* START                                                    */
/************************************************************/

$timeStart = microtime(true);

/* Get the input parameters */
$round = $_GET["round"];
$arrayFilters = array();
$arrayFilter = array();
$filters = $_GET["filters"];
$filters = rawurldecode($filters);            // Decode the encoded parameter (encoding in ajaxrequest.js)
$arrayFilters = explode(FILTERSEPARATOR, $filters);
$arrayFilter = explode(FILTERVALUESEPARATOR, $arrayFilters[FILTERPROJECT]);
$project = $arrayFilter[1];
$arrayFilter = explode(FILTERVALUESEPARATOR, $arrayFilters[FILTERCONF]);
$conf = $arrayFilter[1];
$arrayFilter = explode(FILTERVALUESEPARATOR, $arrayFilters[FILTERAUTOTEST]);
$autotest = $arrayFilter[1];
$arrayFilter = explode(FILTERVALUESEPARATOR, $arrayFilters[FILTERTIMESCALETYPE]);
$timescaleType = $arrayFilter[1];
$arrayFilter = explode(FILTERVALUESEPARATOR, $arrayFilters[FILTERTIMESCALEVALUE]);
$timescaleValue = $arrayFilter[1];
$arrayFilter = explode(FILTERVALUESEPARATOR, $arrayFilters[FILTERSORTBY]);
$sortBy = $arrayFilter[1];

/* Sort field definitions */
define("AUTOTESTSORTBYNOTSET", 0);
define("AUTOTESTSORTBYSIGNAUTOTESTBLOCKINGCONF", 1);
define("AUTOTESTSORTBYSIGNAUTOTESTINSIGNCONF", 2);
define("AUTOTESTSORTBYINSIGNAUTOTESTBLOCKINGCONF", 3);
define("AUTOTESTSORTBYINSIGNAUTOTESTINSIGNGCONF", 4);
define("AUTOTESTSORTBYAUTOTESTFAILEDPERCENTAGE", 5);

/* Connect to the server */
require(__DIR__.'/../connect.php');
$timeConnect = microtime(true);
include(__DIR__.'/../commondefinitions.php');

/* Select database */
if ($useMysqli) {
    // Selected in mysqli_connect() call
} else {
    $selectdb="USE $db";
    $result = mysql_query($selectdb) or die ("Failure: Unable to use the database !");
}

/* Check the latest Build number for the Project */
if ($project <> "All") {
    foreach($_SESSION['arrayProjectName'] as $projectKey => $projectValue) {
        if ($project == $projectValue)
            $latestProjectBuild = $_SESSION['arrayProjectBuildLatest'][$projectKey];
            $minBuildNumberInDirectory = $latestProjectBuild;                       // Initialize, to be calculated later from test results
    }
}

/*************************************************************/
/* NESTED LEVEL 1: No autotest filtering done (default view) */
/*************************************************************/

if ($autotest == "All") {
    if ($round == 1)
        echo "<img src=\"images/ajax-loader.gif\" alt=\"loading\">&nbsp&nbsp";      // On the first round show the loading icon
    else
        echo '<a href="javascript:void(0);" class="imgLink" onclick="showMessageWindow(\'ci/msgautotestdashboardlevel1.html\')">
              <img src="images/info.png" alt="info"></a>&nbsp&nbsp';
    if ($project == "All")
        echo '<b>AUTOTEST DASHBOARD:</b> Select Autotest &nbsp&nbsp&nbsp <i>(filter a Project to see data from all builds with failure %)</i><br/><br/>';
    else
        echo '<b>AUTOTEST DASHBOARD:</b> Select Autotest<br/><br/>';
    if(isset($_SESSION['arrayAutotestName'])) {

        /* Get all (failing) Autotest names and required Project data */
        $arrayFailingAutotestNames = array();
        $arrayFailingAutotestNames = $_SESSION['arrayAutotestName'];
        $autotestCount = count($arrayFailingAutotestNames);
        $arrayProjectName = $_SESSION['arrayProjectName'];
        $arrayProjectBuildLatest = $_SESSION['arrayProjectBuildLatest'];
        $arrayProjectBuildLatestTimestamp = $_SESSION['arrayProjectBuildLatestTimestamp'];

        /* Arrays for number and names Configurations for each Autotest in latest Build (categorised as significant/insignificant) */
        define("SIGNAUTOTESTBLOCKINGCONF", 0);
        $arrayFailingSignAutotestBlockingConfCount = array();
        $arrayFailingSignAutotestBlockingConfNames = array();
        $arrayFailingSignAutotestBlockingConfProjects = array();
        define("SIGNAUTOTESTINSIGNCONF", 1);
        $arrayFailingSignAutotestInsignConfCount = array();
        $arrayFailingSignAutotestInsignConfNames = array();
        $arrayFailingSignAutotestInsignConfProjects = array();
        define("INSIGNAUTOTESTBLOCKINGCONF", 2);
        $arrayFailingInsignAutotestBlockingConfCount = array();
        $arrayFailingInsignAutotestBlockingConfNames = array();
        $arrayFailingInsignAutotestBlockingConfProjects = array();
        define("INSIGNAUTOTESTINSIGNCONF", 3);
        $arrayFailingInsignAutotestInsignConfCount = array();
        $arrayFailingInsignAutotestInsignConfNames = array();
        $arrayFailingInsignAutotestInsignConfProjects = array();

        /* Step 1: Read failing Autotests for latest Build (for each Project and Configuration) */
        $timeLatestStart = microtime(true);
        $maxCount = 0;                                                              // Max count of Autotests in any category (used for sorting the lists)
        $latestAutotests = 0;                                                       // Total count of Autotests in any category (used to identify if any was found)
        $projectFilter = "";
        if ($project <> "All")                                                      // Project filtering
            $projectFilter = "WHERE test_latest.project=\"$project\"";
        $confFilter = "";
        if ($conf <> "All") {                                                       // Conf filtering
            if ($projectFilter == "")
                $confFilter = "WHERE test_latest.cfg=\"$conf\"";
            else
                $confFilter = " AND test_latest.cfg=\"$conf\"";
        }
        $sql = "SELECT name, test_latest.insignificant, test_latest.timestamp, cfg_latest.cfg, cfg_latest.insignificant
                FROM test_latest left join cfg_latest on (test_latest.project = cfg_latest.project AND
                                                        test_latest.cfg = cfg_latest.cfg AND
                                                        test_latest.build_number = cfg_latest.build_number)
                $projectFilter $confFilter";                                        // (Note: Timescale filter not used because it is very slow; Timescale checked instead when looping the data)
        $dbColumnTestName = 0;
        $dbColumnTestInsignificant = 1;
        $dbColumnTestTimestamp = 2;
        $dbColumnCfgCfg = 3;
        $dbColumnCfgInsignificant = 4;
        $timeLatestSelectStart = microtime(true);
        if ($useMysqli) {
            $result = mysqli_query($conn, $sql);
            $numberOfRows = mysqli_num_rows($result);
        } else {
            $selectdb="USE $db";
            $result = mysql_query($selectdb) or die (mysql_error());
            $result = mysql_query($sql) or die (mysql_error());
            $numberOfRows = mysql_num_rows($result);
        }
        $timeLatestSelectEnd = microtime(true);
        for ($j=0; $j<$numberOfRows; $j++) {                                        // Loop the queried Autotests
            if ($useMysqli)
                $resultRow = mysqli_fetch_row($result);
            else
                $resultRow = mysql_fetch_row($result);
            if ($timescaleType == "Since") {                                        // When Timescale filtered ...
                if ($resultRow[$dbColumnTestTimestamp] < $timescaleValue) {         // ... and this is not within the Timescale ...
                    continue;                                                       // ... skip to the next Autotest (in the for loop)
                }
            }
            if ($resultRow[$dbColumnCfgInsignificant] == 0) {                       // Check the Autotest failing category
                if ($resultRow[$dbColumnTestInsignificant] == 0) {
                    $autotestFailureCategory = SIGNAUTOTESTBLOCKINGCONF;
                } else {
                    $autotestFailureCategory = INSIGNAUTOTESTBLOCKINGCONF;
                }
            } else {
                if ($resultRow[$dbColumnTestInsignificant] == 0) {
                    $autotestFailureCategory = SIGNAUTOTESTINSIGNCONF;
                } else {
                    $autotestFailureCategory = INSIGNAUTOTESTINSIGNCONF;
                }
            }
            for ($k=0; $k<$autotestCount; $k++) {                                   // Loop all the available Autotests to collect data per one autotest
                if ($arrayFailingAutotestNames[$k] == $resultRow[$dbColumnTestName]) {
                    switch ($autotestFailureCategory) {
                        case SIGNAUTOTESTBLOCKINGCONF:
                            if (!strpos($arrayFailingSignAutotestBlockingConfNames[$k],$resultRow[$dbColumnCfgCfg])) {   // Each Conf to be listed only once
                                $arrayFailingSignAutotestBlockingConfCount[$k]++;
                                $latestAutotests++;
                                $arrayFailingSignAutotestBlockingConfNames[$k]
                                    = $arrayFailingSignAutotestBlockingConfNames[$k] . '<br>' . $resultRow[$dbColumnCfgCfg];
                                if ($arrayFailingSignAutotestBlockingConfCount[$k] > $maxCount)
                                    $maxCount = $arrayFailingSignAutotestBlockingConfCount[$k];
                            }
                            $arrayFailingSignAutotestBlockingConfProjects[$k]
                                = $arrayFailingSignAutotestBlockingConfProjects[$k] . '<br>'
                                . $projectValue . ' (' . $resultRow[$dbColumnCfgCfg] . ')';                              // List Projects for each Conf (i.e. one Project may appear several times)
                            break;
                        case SIGNAUTOTESTINSIGNCONF:
                            if (!strpos($arrayFailingSignAutotestInsignConfNames[$k],$resultRow[$dbColumnCfgCfg])) {     // Each Conf to be listed only once
                                $arrayFailingSignAutotestInsignConfCount[$k]++;
                                $latestAutotests++;
                                $arrayFailingSignAutotestInsignConfNames[$k]
                                    = $arrayFailingSignAutotestInsignConfNames[$k] . '<br>' . $resultRow[$dbColumnCfgCfg];
                                if ($arrayFailingSignAutotestInsignConfCount[$k] > $maxCount)
                                    $maxCount = $arrayFailingSignAutotestInsignConfCount[$k];
                            }
                            $arrayFailingSignAutotestInsignConfProjects[$k]
                                = $arrayFailingSignAutotestInsignConfProjects[$k] . '<br>'
                                . $projectValue . ' (' . $resultRow[$dbColumnCfgCfg] . ')';                              // List Projects for each Conf (i.e. one Project may appear several times)
                            break;
                        case INSIGNAUTOTESTBLOCKINGCONF:
                            if (!strpos($arrayFailingInsignAutotestBlockingConfNames[$k],$resultRow[$dbColumnCfgCfg])) { // Each Conf to be listed only once
                                $arrayFailingInsignAutotestBlockingConfCount[$k]++;
                                $latestAutotests++;
                                $arrayFailingInsignAutotestBlockingConfNames[$k]
                                    = $arrayFailingInsignAutotestBlockingConfNames[$k] . '<br>' . $resultRow[$dbColumnCfgCfg];
                                if ($arrayFailingInsignAutotestBlockingConfCount[$k] > $maxCount)
                                    $maxCount = $arrayFailingInsignAutotestBlockingConfCount[$k];
                            }
                            $arrayFailingInsignAutotestBlockingConfProjects[$k]
                                = $arrayFailingInsignAutotestBlockingConfProjects[$k] . '<br>'
                                . $projectValue . ' (' . $resultRow[$dbColumnCfgCfg] . ')';                              // List Projects for each Conf (i.e. one Project may appear several times)
                            break;
                        case INSIGNAUTOTESTINSIGNCONF:
                            if (!strpos($arrayFailingInsignAutotestInsignConfNames[$k],$resultRow[$dbColumnCfgCfg])) {   // Each Conf to be listed only once
                                $arrayFailingInsignAutotestInsignConfCount[$k]++;
                                $latestAutotests++;
                                $arrayFailingInsignAutotestInsignConfNames[$k]
                                    = $arrayFailingInsignAutotestInsignConfNames[$k] . '<br>' . $resultRow[$dbColumnCfgCfg];
                                if ($arrayFailingInsignAutotestInsignConfCount[$k] > $maxCount)
                                    $maxCount = $arrayFailingInsignAutotestInsignConfCount[$k];
                            }
                            $arrayFailingInsignAutotestInsignConfProjects[$k]
                                = $arrayFailingInsignAutotestInsignConfProjects[$k] . '<br>'
                                . $projectValue . ' (' . $resultRow[$dbColumnCfgCfg] . ')';                              // List Projects for each Conf (i.e. one Project may appear several times)
                            break;
                    }
                    break;                                                          // Match found, skip the rest
                }
            }            // Endfor all available Autotests
        }                // Endfor queried Autotests

        /* Save data to session variables to be able to use them in nested level 2 below */
        $_SESSION['arrayFailingSignAutotestBlockingConfCount'] = $arrayFailingSignAutotestBlockingConfCount;
        $_SESSION['arrayFailingSignAutotestBlockingConfNames'] = $arrayFailingSignAutotestBlockingConfNames;
        $_SESSION['arrayFailingSignAutotestBlockingConfProjects'] = $arrayFailingSignAutotestBlockingConfProjects;
        $_SESSION['arrayFailingSignAutotestInsignConfCount'] = $arrayFailingSignAutotestInsignConfCount;
        $_SESSION['arrayFailingSignAutotestInsignConfNames'] = $arrayFailingSignAutotestInsignConfNames;
        $_SESSION['arrayFailingSignAutotestInsignConfProjects'] = $arrayFailingSignAutotestInsignConfProjects;
        $_SESSION['arrayFailingInsignAutotestBlockingConfCount'] = $arrayFailingInsignAutotestBlockingConfCount;
        $_SESSION['arrayFailingInsignAutotestBlockingConfNames'] = $arrayFailingInsignAutotestBlockingConfNames;
        $_SESSION['arrayFailingInsignAutotestBlockingConfProjects'] = $arrayFailingInsignAutotestBlockingConfProjects;
        $_SESSION['arrayFailingInsignAutotestInsignConfCount'] = $arrayFailingInsignAutotestInsignConfCount;
        $_SESSION['arrayFailingInsignAutotestInsignConfNames'] = $arrayFailingInsignAutotestInsignConfNames;
        $_SESSION['arrayFailingInsignAutotestInsignConfProjects'] = $arrayFailingInsignAutotestInsignConfProjects;

        if ($useMysqli) {
            mysqli_free_result($result);                                            // Free result set
        }
        $timeLatestEnd = microtime(true);

        /* Step 2: Read failing Autotests for all Builds with possible timescale filtering (ONLY ON SECOND ROUND AND WHEN PROJECT FILTER USED) */
        $arrayFailingAutotestFailedBuilds = array();
        $arrayFailingAutotestAllBuilds = array();
        $arrayFailingAutotestFailedPercentage = array();
        $arrayFailingAutotestBuildConfigurations = array();

        $allAutotestsCount = 0;
        $booPrintAllBuildsTitle = FALSE;
        $booPrintAllBuildsData = FALSE;
        if (isset($_SESSION['previousProject']) AND $project == "All")
            unset($_SESSION['previousProject']);                                    // Clear the session variable if Project filter cleared
        if ($project <> "All")
            $booPrintAllBuildsTitle = TRUE;                                         // All Builds title printed only when Project filtered (server performance issue with huge data amount)
        if ($round == 2 AND $project <> "All")
            $booPrintAllBuildsData = TRUE;                                          // All Builds data printed only on 2nd round and when Project filtered (server performance issue with huge data amount)
        if ($booPrintAllBuildsData) {
            $timeAllStart = microtime(true);

            /* Step 2.1: All autotests for comparison (read from the autotest xml files) */

            /* Check which builds to be checked (after the selected time scale). Note: Another check is which builds include the test result xml files in the first place (see $minBuildNumberInDirectory) */
            $projectFilter = "WHERE project=\"$project\"";                          // Project is filtered here
            $timescaleFilter = "";
            if ($timescaleType <> "All")
                    $timescaleFilter = "AND timestamp>=\"$timescaleValue\"";
            $sql = "SELECT min(build_number)
                    FROM ci
                    $projectFilter $timescaleFilter";
            $dbColumnCiBuildNumber = 0;
            if ($useMysqli) {
                $result2 = mysqli_query($conn, $sql);
            } else {
                $selectdb = "USE $db";
                $result2 = mysql_query($selectdb) or die (mysql_error());
                $result2 = mysql_query($sql) or die (mysql_error());
            }
            if ($useMysqli)
                $resultRow2 = mysqli_fetch_row($result2);
            else
                $resultRow2 = mysql_fetch_row($result2);
            $minBuildNumberInDatabase = $resultRow2[$dbColumnCiBuildNumber];
            $_SESSION['minBuildNumberInDatabase'] = $minBuildNumberInDatabase;      // Save for level 2
            if ($useMysqli)
                mysqli_free_result($result2);                                       // Free result set

            /* Read the test results from the Project directory (structure is e.g. QtBase_stable_Integration/build_03681/macx-ios-clang_OSX_10.8 */
            if (isset($_SESSION['previousProject'])) {
                $previousProject = $_SESSION['previousProject'];
                $previousConfiguration = $_SESSION['previousConfiguration'];
                $previousTimescaleType = $_SESSION['previousTimescaleType'];
                $previousTimescaleValue = $_SESSION['previousTimescaleValue'];
            } else {
                $previousProject = "NA";
                $previousConfiguration = "NA";
                $previousTimescaleType = "NA";
                $previousTimescaleValue = "NA";
            }
            if ($project == $previousProject AND $conf == $previousConfiguration AND
                $timescaleType == $previousTimescaleType AND $timescaleValue == $previousTimescaleValue) {  // Performance optimization: If project and configuration filters not changed -> use the session variables
                $arrayFailingAutotestAllBuilds = $_SESSION['arrayFailingAutotestAllBuilds'];
                $arrayFailingAutotestBuildConfigurations = $_SESSION['arrayFailingAutotestBuildConfigurations'];
                $minBuildNumberInDirectory = $_SESSION['minBuildNumberInDirectory'];
            } else {                                                                                        // ... otherwise read the data from the zip files (this is much slower)
                $minBuildNumberToCheck = setMinBuildNumberToCheck($latestProjectBuild, $minBuildNumberInDatabase, $timescaleType);
                $minBuildNumberInDirectory = readProjectTestResultDirectory(
                    CITESTRESULTSDIRECTORY, $project, $minBuildNumberToCheck, $conf, FALSE, $arrayFailingAutotestNames,
                    $arrayFailingAutotestBuildConfigurations, $arrayFailingAutotestAllBuilds);
            }

            $timeAllXmlDirectoryCheckEnd = microtime(true);

            /* Step 2.2: Failed autotests for comparison */

            /* Read failed Autotests from the database only for those builds and configurations that have also the xml data */
            $projectFilter = "WHERE project = \"$project\"";                        // Project is filtered here
            $confFilter = "cfg = \"NA\"";                                           // Any value that does NOT match with any cfg
            foreach ($arrayFailingAutotestBuildConfigurations as $buildConfiguration) {     // In format "03681;macx-ios-clang_OSX_10.8"
                $arrayBuildConfiguration = explode(FILTERSEPARATOR, $buildConfiguration);
                $xmlBuild = (int)$arrayBuildConfiguration[0];
                $xmlConf = $arrayBuildConfiguration[1];                             // If configuration filtered, this includes only the filtered configuration (therefore no need to check the $conf here)
                if ($xmlBuild >= $minBuildNumberInDirectory)
                    $confFilter = $confFilter . ' OR (build_number = ' . $xmlBuild . ' AND cfg = "' . $xmlConf . '")';    // Include only the confs that had the xml report file
            }
            $confFilter = "($confFilter)";                                          // Close with () due to OR
            $buildFilter = " build_number >= $minBuildNumberInDirectory";
            $timeAllSelectStart = microtime(true);
            $sql = "SELECT name, timestamp
                    FROM test
                    $projectFilter AND $buildFilter AND $confFilter";               // (Note: No need to check timescale here, the build_number is used instead
            $dbColumnTestName = 0;
            $dbColumnTestTimestamp = 1;
            if ($useMysqli) {
                $result2 = mysqli_query($conn, $sql);
                $numberOfRows2 = mysqli_num_rows($result2);
            } else {
                $selectdb = "USE $db";
                $result2 = mysql_query($selectdb) or die (mysql_error());
                $result2 = mysql_query($sql) or die (mysql_error());
                $numberOfRows2 = mysql_num_rows($result2);
            }

            /* Calculate the builds where autotest has failed */
            for ($j=0; $j<$numberOfRows2; $j++) {
                if ($useMysqli)
                    $resultRow2 = mysqli_fetch_row($result2);
                else
                    $resultRow2 = mysql_fetch_row($result2);
                for ($k=0; $k<$autotestCount; $k++) {                               // Loop all the available Autotests to collect data per one autotest
                    if ($arrayFailingAutotestNames[$k] == $resultRow2[$dbColumnTestName])
                        $arrayFailingAutotestFailedBuilds[$k]++;                    // Save fail count
                }
            }
            if ($useMysqli)
                mysqli_free_result($result2);                                       // Free result set
            $timeAllDbFailedCheckEnd = microtime(true);

            /* Step 2.3: Calculate the failure percentage */
            for ($k=0; $k<$autotestCount; $k++)
                $arrayFailingAutotestFailedPercentage[$k]
                    = round(100 * ($arrayFailingAutotestFailedBuilds[$k] / $arrayFailingAutotestAllBuilds[$k]));  // Must be rounded to integer for sorting to work

            /* Save the calculated data for level 2 and for returning from Level 2 to Level 1 (so that it would not be needed to read the data from xml files) */
            $_SESSION['arrayFailingAutotestFailedBuilds'] = $arrayFailingAutotestFailedBuilds;
            $_SESSION['arrayFailingAutotestAllBuilds'] = $arrayFailingAutotestAllBuilds;
            $_SESSION['arrayFailingAutotestFailedPercentage'] = $arrayFailingAutotestFailedPercentage;
            $_SESSION['minBuildNumberInDirectory'] = $minBuildNumberInDirectory;
            $_SESSION['arrayFailingAutotestBuildConfigurations'] = $arrayFailingAutotestBuildConfigurations;
            $_SESSION['previousProject'] = $project;
            $_SESSION['previousConfiguration'] = $conf;
            $_SESSION['previousTimescaleType'] = $timescaleType;
            $_SESSION['previousTimescaleValue'] = $timescaleValue;

            $timeAllEnd = microtime(true);
        } // endif $booPrintAllBuildsData

        /* Read the timestamp of the latest build and the first build with detailed test result data */
        if ($project <> "All") {
            for ($k=0; $k<=1; $k++) {                                               // Run twice: 0 = latest build, 1 = first build with detailed test result data
                $projectFilter = "WHERE project = \"$project\"";                    // Project is filtered here
                if ($k == 0) {
                    $from = "FROM ci_latest";
                    $buildFilter = " AND build_number = $latestProjectBuild";
                } else {
                    $from = "FROM ci";
                    $buildFilter = " AND build_number = $minBuildNumberInDirectory";
                }
                $sql = "SELECT min(timestamp)
                        $from
                        $projectFilter $buildFilter";
                $dbColumnCiTimestamp = 0;
                if ($useMysqli) {
                    $result2 = mysqli_query($conn, $sql);
                    $numberOfRows2 = mysqli_num_rows($result2);
                } else {
                    $selectdb = "USE $db";
                    $result2 = mysql_query($selectdb) or die (mysql_error());
                    $result2 = mysql_query($sql) or die (mysql_error());
                    $numberOfRows2 = mysql_num_rows($result2);
                }
                if ($useMysqli)
                    $resultRow2 = mysqli_fetch_row($result2);
                else
                    $resultRow2 = mysql_fetch_row($result2);
                if ($k == 0)
                    $latestProjectBuildTimestamp = $resultRow2[$dbColumnCiTimestamp];
                else
                    $minBuildNumberInDirectoryTimestamp = $resultRow2[$dbColumnCiTimestamp];
                if ($useMysqli)
                    mysqli_free_result($result2);                                   // Free result set
            }
        }

        /* Print the used filters */
        if ($project <> "All" OR $conf <> "All" OR $timescaleType <> "All") {
            echo '<table>';
            if ($project <> "All")
                echo '<tr><td>Project:</td><td class="tableCellBackgroundTitle">' . $project . '</td></tr>';
            if ($conf <> "All")
                echo '<tr><td>Configuration:</td><td class="tableCellBackgroundTitle">' . $conf . '</td></tr>';
            if ($timescaleType == "Since")
                echo '<tr><td>Since:</td><td class="tableCellBackgroundTitle">' . $timescaleValue . '</td></tr>';
            if ($project <> "All")
                echo '<tr><td>Latest Build:</td><td>' . $latestProjectBuild . ' ('
                    . substr($latestProjectBuildTimestamp, 0, strpos($latestProjectBuildTimestamp, " ")) . ')</td></tr>';
            if ($booPrintAllBuildsData) {
                if ($minBuildNumberInDirectory == MAXCIBUILDNUMBER) {
                    $testResultBuilds = '(not any test result files available)';
                    $testResultBuildsSeeMore = '';
                } else {
                    $testResultBuilds = $minBuildNumberInDirectory . ' ('
                        . substr($minBuildNumberInDirectoryTimestamp, 0, strpos($minBuildNumberInDirectoryTimestamp, " ")) . ')';
                    $testResultBuildsSeeMore = setSeeMoreNote($timescaleType, $timescaleValue);
                }
                echo '<tr><td>Test Results since:</td><td>' . $testResultBuilds . $testResultBuildsSeeMore . '</td></tr>';
            }
            echo '</table>';
            echo '<br/><br/><b>Failed autotests</b><br/><br/>';
        }

        /* Set the default sorting */
        if ($booPrintAllBuildsData) {
            if ($sortBy == AUTOTESTSORTBYNOTSET) {
                if ($minBuildNumberInDirectory == MAXCIBUILDNUMBER)
                    $sortBy = AUTOTESTSORTBYSIGNAUTOTESTBLOCKINGCONF;
                else
                    $sortBy = AUTOTESTSORTBYAUTOTESTFAILEDPERCENTAGE;                           // Sorting based the Failed % when all builds data available and shown
                }
        } else {
            if ($sortBy == AUTOTESTSORTBYNOTSET)
                $sortBy = AUTOTESTSORTBYSIGNAUTOTESTBLOCKINGCONF;                               // Default sorting is the significant blocking when only latest build shown
        }

        /* Print the titles */
        echo '<table class="fontSmall">';
        echo '<tr>';                                                                            // First row
        echo '<th></th>';
        if ($timescaleType == "All" AND CITESTRESULTBUILDCOUNT == 1) {
            if ($booPrintAllBuildsTitle)
                echo '<th colspan="7" class="tableBottomBorder tableSideBorder">';
            else
                echo '<th colspan="4" class="tableBottomBorder tableSideBorder">';
            echo 'LATEST BUILD (SINCE ' . $_SESSION['minBuildDate'] . ')</th>';
        }
        if ($timescaleType == "All" AND CITESTRESULTBUILDCOUNT > 1) {
            echo '<th colspan="4" class="tableBottomBorder tableSideBorder">';
            echo 'LATEST BUILD (SINCE ' . $_SESSION['minBuildDate'] . ')</th>';
            if ($booPrintAllBuildsTitle) {
                echo '<th colspan="3" class="tableBottomBorder tableSideBorder">';
                echo 'ALL BUILDS</th>';
            }
        }
        if ($timescaleType == "Since") {
                echo '<th colspan="4" class="tableBottomBorder tableSideBorder">';
                echo 'LATEST BUILD SINCE ' . $timescaleValue . '</th>';
                if ($booPrintAllBuildsTitle) {
                    echo '<th colspan="3" class="tableBottomBorder tableSideBorder">';
                    echo 'ALL BUILDS SINCE ' . $timescaleValue . '</th>';
                }
        }
        echo '</tr>';
        echo '<tr>';                                                                            // Second row
        echo '<th></th>';
        echo '<th colspan="2" class="tableBottomBorder tableSideBorder">Failed Significant Autotests</th>';
        echo '<th colspan="2" class="tableBottomBorder tableSideBorder">Failed Insignificant Autotests</th>';
        if ($booPrintAllBuildsTitle) {
            if ($round == 1) {
                $xmlBuildInfo = 'Detailed Test Results <span class="loading"><span>.</span><span>.</span><span>.</span></span>';
            } else {
                if ($minBuildNumberInDirectory == MAXCIBUILDNUMBER) {             // No builds found with test result xml files
                    $xmlBuildInfo = "(not any Builds with test result files)";
                } else {
                    if ($timescaleType == "All" AND CITESTRESULTBUILDCOUNT == 1)
                        $xmlBuildInfo = "Detailed Test Results";
                    else
                        $xmlBuildInfo = "Detailed Test Results since Build $minBuildNumberInDirectory";
                }
            }
            echo '<th colspan="3" class="tableBottomBorder tableSideBorder">' . $xmlBuildInfo . '</th>';
        }
        echo '</tr>';
        echo '<tr class="tableBottomBorder">';                                                  // Third row
        echo '<th class="tableCellAlignRight"><a href="javascript:void(0);" onclick="showMessageWindow(\'ci/msgfailuredescription.html\')"> Failure category</a></th>';
        echo '<td class="sortField tableLeftBorder tableCellCentered tableCellBackgroundRedDark">';
        if ($sortBy == AUTOTESTSORTBYSIGNAUTOTESTBLOCKINGCONF)
            echo '1) Blocking<br>Confs&nbsp;&nbsp;&nbsp;<b>&diams;</b>';          // Identify selected sorting
        else
            echo '<a href="javascript:void(0);" onclick="filterAutotest(\'All\',' . AUTOTESTSORTBYSIGNAUTOTESTBLOCKINGCONF . ')">
                  1) Blocking<br>Confs&nbsp;&nbsp;&nbsp;<b><img src="images/sort-descending.png" alt="Sort" title="sort descending"></b></a>';
        echo '</td>';
        echo '<td class="sortField tableRightBorder tableCellCentered tableCellBackgroundRed">';
        if ($sortBy == AUTOTESTSORTBYSIGNAUTOTESTINSIGNCONF)
            echo '2) Insignificant<br>Confs&nbsp;&nbsp;&nbsp;<b>&diams;</b>';     // Identify selected sorting
        else
            echo '<a href="javascript:void(0);" onclick="filterAutotest(\'All\',' . AUTOTESTSORTBYSIGNAUTOTESTINSIGNCONF . ')">
                  2) Insignificant<br>Confs&nbsp;&nbsp;&nbsp;<b><img src="images/sort-descending.png" alt="Sort" title="sort descending"></b></a>';
        echo '</td>';
        echo '<td class="sortField tableLeftBorder tableCellCentered tableCellBackgroundRedLight">';
        if ($sortBy == AUTOTESTSORTBYINSIGNAUTOTESTBLOCKINGCONF)
            echo '3) Blocking<br>Confs&nbsp;&nbsp;&nbsp;<b>&diams;</b>';          // Identify selected sorting
        else
            echo '<a href="javascript:void(0);" onclick="filterAutotest(\'All\',' . AUTOTESTSORTBYINSIGNAUTOTESTBLOCKINGCONF . ')">
                  3) Blocking<br>Confs&nbsp;&nbsp;&nbsp;<b><img src="images/sort-descending.png" alt="Sort" title="sort descending"></b></a>';
        echo '</td>';
        echo '<td class="sortField tableRightBorder tableCellCentered tableCellBackgroundRedLight">';
        if ($sortBy == AUTOTESTSORTBYINSIGNAUTOTESTINSIGNGCONF)
            echo '4) Insignificant<br>Confs&nbsp;&nbsp;&nbsp;<b>&diams;</b>';     // Identify selected sorting
        else
            echo '<a href="javascript:void(0);" onclick="filterAutotest(\'All\',' . AUTOTESTSORTBYINSIGNAUTOTESTINSIGNGCONF . ')">
                  4) Insignificant<br>Confs&nbsp;&nbsp;&nbsp;<b><img src="images/sort-descending.png" alt="Sort" title="sort descending"></b></a>';
        echo '</td>';
        if ($booPrintAllBuildsTitle) {
            echo '<td class="tableBottomBorder tableLeftBorder tableCellCentered">Builds where<br>failed</td>';
            echo '<td class="tableBottomBorder tableCellCentered">Builds where<br>run (all)</td>';
            echo '<td class="sortField tableBottomBorder tableRightBorder tableCellCentered">';
            if ($sortBy == AUTOTESTSORTBYAUTOTESTFAILEDPERCENTAGE)
                echo 'Failed %<br>&diams;';                                       // Identify selected sorting
            else
                echo '<a href="javascript:void(0);" onclick="filterAutotest(\'All\',' . AUTOTESTSORTBYAUTOTESTFAILEDPERCENTAGE . ')">
                      Failed %<br><img src="images/sort-descending.png" alt="Sort" title="sort descending"></a>';
            echo '</td>';
        }
        echo '</tr>';

        /* Print list of Autotests */
        $k = 0;
        $listCutMode = FALSE;
        $failingSignAutotestBlockingConfCount = 0;
        $failingSignAutotestInsignConfCount = 0;
        $failingInsignAutotestBlockingConfCount = 0;
        $failingInsignAutotestInsignConfCount = 0;
        $arrayFailingAutotestFailedBuildsSum = 0;
        $arrayFailingAutotestAllBuildsSum = 0;
        $arrayFailingAutotestFailedPercentageSum = 0;
        if ($maxCount < 100)
            $maxCount = 100;                                                            // Loop at least the percentage scale
        for ($countOrder=$maxCount; $countOrder>=0; $countOrder--) {                    // Sort the list by looping from the highest count
            for ($i=0; $i<$autotestCount; $i++) {                                       // Loop the Autotests
                switch ($sortBy) {                                                      // Check the next value to print in sorting
                    case AUTOTESTSORTBYSIGNAUTOTESTBLOCKINGCONF:
                        $sortFieldValue = $arrayFailingSignAutotestBlockingConfCount[$i];
                        break;
                    case AUTOTESTSORTBYSIGNAUTOTESTINSIGNCONF:
                        $sortFieldValue = $arrayFailingSignAutotestInsignConfCount[$i];
                        break;
                    case AUTOTESTSORTBYINSIGNAUTOTESTBLOCKINGCONF:
                        $sortFieldValue = $arrayFailingInsignAutotestBlockingConfCount[$i];
                        break;
                    case AUTOTESTSORTBYINSIGNAUTOTESTINSIGNGCONF:
                        $sortFieldValue = $arrayFailingInsignAutotestInsignConfCount[$i];
                        break;
                    case AUTOTESTSORTBYAUTOTESTFAILEDPERCENTAGE:
                        $sortFieldValue = $arrayFailingAutotestFailedPercentage[$i];
                        break;
                }
                if ($sortFieldValue == $countOrder) {                                   // Print the ones that are next in the sorting order
                    if ($arrayFailingSignAutotestBlockingConfCount[$i]
                        + $arrayFailingSignAutotestInsignConfCount[$i]
                        + $arrayFailingInsignAutotestBlockingConfCount[$i]
                        + $arrayFailingInsignAutotestInsignConfCount[$i]
                        + $arrayFailingAutotestFailedBuilds[$i] > 0) {                  // Don't print if not any failures in Latest/All Builds
                        if ($k % 2 == 0)
                            echo '<tr>';
                        else
                            echo '<tr class="tableBackgroundColored">';

                        /* Autotest name */
                        echo '<td><a href="javascript:void(0);" onclick="filterAutotest(\'' . $arrayFailingAutotestNames[$i]
                            . '\')">' . $arrayFailingAutotestNames[$i] . '</a></td>';

                        /* Latest Build: Significant Autotests in blocking Configuration (with names as a popup) */
                        if ($arrayFailingSignAutotestBlockingConfCount[$i] > 0) {
                            echo '<td class="tableLeftBorder tableCellCentered fontColorRed"><span class="popupMessage">'
                                . $arrayFailingSignAutotestBlockingConfCount[$i]
                                . '<span><b>' . $arrayFailingAutotestNames[$i] . ':</b><br>'
                                . substr($arrayFailingSignAutotestBlockingConfNames[$i],strlen('<br>'))
                                . '</span></span></td>';                                // Skip leading '<br>' set above
                        } else {
                            echo '<td class="tableLeftBorder tableCellCentered">-</td>';
                        }

                        /* Latest Build: Significant Autotests in insignificant Configuration (with names as a popup) */
                        if ($arrayFailingSignAutotestInsignConfCount[$i] > 0)
                            echo '<td class="tableCellCentered"><span class="popupMessage">'
                                . $arrayFailingSignAutotestInsignConfCount[$i]
                                . '<span><b>' . $arrayFailingAutotestNames[$i] . ':</b><br>'
                                . substr($arrayFailingSignAutotestInsignConfNames[$i],strlen('<br>'))
                                . '</span></span></td>';                                // Skip leading '<br>' set above
                        else
                            echo '<td class="tableCellCentered">-</td>';

                        /* Latest Build: Insignificant Autotests in blocking Configuration (with names as a popup) */
                        if ($arrayFailingInsignAutotestBlockingConfCount[$i] > 0)
                            echo '<td class="tableLeftBorder tableCellCentered"><span class="popupMessage">'
                                . $arrayFailingInsignAutotestBlockingConfCount[$i]
                                . '<span><b>' . $arrayFailingAutotestNames[$i] . ':</b><br>'
                                . substr($arrayFailingInsignAutotestBlockingConfNames[$i],strlen('<br>'))
                                . '</span></span></td>';                                // Skip leading '<br>' set above
                        else
                            echo '<td class="tableLeftBorder tableCellCentered">-</td>';

                        /* Latest Build: Insignificant Autotests in insignificant Configuration (with names as a popup) */
                        if ($arrayFailingInsignAutotestInsignConfCount[$i] > 0)
                            echo '<td class="tableRightBorder tableCellCentered"><span class="popupMessage">'
                                . $arrayFailingInsignAutotestInsignConfCount[$i]
                                . '<span><b>' . $arrayFailingAutotestNames[$i] . ':</b><br>'
                                . substr($arrayFailingInsignAutotestInsignConfNames[$i],strlen('<br>'))
                                . '</span></span></td>';                                // Skip leading '<br>' set above
                        else
                            echo '<td class="tableRightBorder tableCellCentered">-</td>';

                        if ($booPrintAllBuildsTitle) {
                            if ($booPrintAllBuildsData) {
                                /* All Builds: Builds where failed */
                                if ($arrayFailingAutotestFailedBuilds[$i] > 0)
                                    echo '<td class="tableLeftBorder tableCellCentered">'
                                        . $arrayFailingAutotestFailedBuilds[$i] . '</td>';
                                else
                                    echo '<td class="tableLeftBorder tableCellCentered">-</td>';

                                /* All Builds: Builds where run (all) */
                                if ($arrayFailingAutotestAllBuilds[$i] > 0)
                                    echo '<td class="tableCellCentered">'
                                        . $arrayFailingAutotestAllBuilds[$i] . '</td>';
                                else
                                    if ($booTestResultDirectory)
                                        echo '<td class="tableCellCentered">-</td>';
                                    else
                                        echo '<td class="tableCellCentered">(n/a)</td>';

                                /* All Builds: Failed % */
                                if ($arrayFailingAutotestFailedPercentage[$i] > 0) {
                                    if ($arrayFailingAutotestFailedPercentage[$i] >= AUTOTESTFAILUREWARNINGLEVEL)
                                        echo '<td class="tableRightBorder tableCellCentered fontColorRed">';
                                    else
                                        echo '<td class="tableRightBorder tableCellCentered">';
                                    echo $arrayFailingAutotestFailedPercentage[$i] . '%</td>';
                                } else {
                                    echo '<td class="tableRightBorder tableCellCentered">-</td>';
                                }

                            } else {
                                echo '<td class="tableLeftBorder tableCellCentered"></td>';
                                echo '<td class="tableCellCentered"></td>';
                                echo '<td class="tableRightBorder tableCellCentered"></td>';
                            }
                        }

                        echo '</tr>';
                        $k++;

                        /* Count the totals */
                        $failingSignAutotestBlockingConfCount = $failingSignAutotestBlockingConfCount + $arrayFailingSignAutotestBlockingConfCount[$i];
                        $failingSignAutotestInsignConfCount = $failingSignAutotestInsignConfCount + $arrayFailingSignAutotestInsignConfCount[$i];
                        $failingInsignAutotestBlockingConfCount = $failingInsignAutotestBlockingConfCount + $arrayFailingInsignAutotestBlockingConfCount[$i];
                        $failingInsignAutotestInsignConfCount = $failingInsignAutotestInsignConfCount + $arrayFailingInsignAutotestInsignConfCount[$i];
                        $arrayFailingAutotestFailedBuildsSum = $arrayFailingAutotestFailedBuildsSum + $arrayFailingAutotestFailedBuilds[$i];
                        $arrayFailingAutotestAllBuildsSum = $arrayFailingAutotestAllBuildsSum + $arrayFailingAutotestAllBuilds[$i];

                    }
                    if ($k > 12 AND !isset($_SESSION['failingAutotestsShowFullList'])) {     // List cut mode: By default show only n items in the list to leave room for possible other metrics boxes
                        $listCutMode = TRUE;
                        break;
                    }
                }         // Endif sorting order
            }             // Endfor Autotests
        }                 // Endfor sorting
        $printedAutotests = $k;

        /* Print Totals summary row */
        if ($listCutMode == FALSE) {
            $arrayFailingAutotestFailedPercentageSum = 100 * round($arrayFailingAutotestFailedBuildsSum / $arrayFailingAutotestAllBuildsSum, 2);
            echo '<tr>';
            echo '<td class="tableRightBorder tableTopBorder">total (' . $printedAutotests . ')</td>';
            echo '<td class="tableTopBorder tableCellCentered">' . $failingSignAutotestBlockingConfCount . '</td>';
            echo '<td class="tableRightBorder tableTopBorder tableCellCentered">' . $failingSignAutotestInsignConfCount . '</td>';
            echo '<td class="tableTopBorder tableCellCentered">' . $failingInsignAutotestBlockingConfCount . '</td>';
            echo '<td class="tableRightBorder tableTopBorder tableCellCentered">' . $failingInsignAutotestInsignConfCount . '</td>';
            if ($booPrintAllBuildsTitle) {
                if ($booPrintAllBuildsData) {
                    echo '<td class="tableTopBorder tableCellCentered">' . $arrayFailingAutotestFailedBuildsSum . '</td>';
                    echo '<td class="tableTopBorder tableCellCentered">' . $arrayFailingAutotestAllBuildsSum . '</td>';
                    echo '<td class="tableRightBorder tableTopBorder tableCellCentered">' . $arrayFailingAutotestFailedPercentageSum . '%</td>';
                } else {
                    echo '<td class="tableTopBorder tableCellCentered"></td>';
                    echo '<td class="tableTopBorder tableCellCentered"></td>';
                    echo '<td class="tableRightBorder tableTopBorder tableCellCentered"></td>';
                }
            }
            echo '</tr>';
        }
        echo '</table>';

        if ($round == 2 AND !isset($_SESSION['failingAutotestsShowFullList'])) {
            echo '<br/><a href="javascript:void(0);" onclick="filterAutotest(\'All\')">Show full list...</a><br/><br/>';  // List cut mode: If only first n items shown, add a link to see all
            $_SESSION['failingAutotestsShowFullList'] = TRUE;                                                             // List cut mode: After refreshing the metrics box, show all items instead (set below to return the default 'cut mode')
        }

    } else {
        echo '<br/>Filter values not ready or they are expired, please <a href="javascript:void(0);" onclick="reloadFilters()">reload</a> ...';
    }

    /* Elapsed time */
    if ($showElapsedTime) {
        $timeEnd = microtime(true);
        $time = round($timeEnd - $timeStart, 4);
        $timeDbConnect = round($timeConnect - $timeStart, 4);
        $timeLatest = round($timeLatestEnd - $timeLatestStart, 4);
        $timeLatestSelect = round($timeLatestSelectEnd - $timeLatestSelectStart, 4);
        $timeLatestCalculation = round($timeLatest - $timeLatestSelect, 4);
        if (isset($timeAllEnd)) {
            $timeAll = round($timeAllEnd - $timeAllStart, 4);
            $timeAllXml = round($timeAllXmlDirectoryCheckEnd - $timeAllStart, 4);
            $timeAllDb = round($timeAllDbFailedCheckEnd - $timeAllXmlDirectoryCheckEnd, 4);
            $timeAllCalculation = round($timeAll - $timeAllXml - $timeAllDb, 4);
        }
        echo "<div class=\"elapdedTime\">";
        echo "<ul><li>";
        echo "<b>Total time:</b>&nbsp $time s (round $round)<br>";
        echo "Latest builds: $timeLatest s
            (database connect time: $timeDbConnect s,
             database read time: $timeLatestSelect s,
             calculation: $timeLatestCalculation s)<br>";
        if (isset($timeAll))
            echo "All builds:&nbsp&nbsp&nbsp&nbsp&nbsp $timeAll s
            (database connect time: $timeDbConnect s,
             directory read time: $timeAllXml s,
             database read time: $timeAllDb s,
             calculation: $timeAllCalculation s)<br>";
        echo "</li></ul>";
        echo "</div>";
    }

}

/*************************************************************/
/* NESTED LEVEL 2: Autotest filtered                         */
/*************************************************************/

if ($autotest <> "All") {
    if ($round == 1)
        echo "<img src=\"images/ajax-loader.gif\" alt=\"loading\">&nbsp&nbsp";  // On the first round show the loading icon
    else
        echo '<a href="javascript:void(0);" class="imgLink" onclick="showMessageWindow(\'ci/msgautotestdashboardlevel2.html\')">
              <img src="images/info.png" alt="info"></a>&nbsp&nbsp';
    echo '<b>AUTOTEST DASHBOARD:</b> <a href="javascript:void(0);" onclick="filterAutotest(\'All\')">Select Autotest</a> -> ' . $autotest . '<br/><br/>';

    /* Get the data calculated on level 1 */
    $arrayFailingAutotestNames = array();
    $arrayFailingAutotestNames = $_SESSION['arrayAutotestName'];
    $autotestCount = count($arrayFailingAutotestNames);
    $arrayFailingAutotestFailedBuilds = array();
    $arrayFailingAutotestFailedBuilds = $_SESSION['arrayFailingAutotestFailedBuilds'];
    $arrayFailingAutotestAllBuilds = array();
    $arrayFailingAutotestAllBuilds = $_SESSION['arrayFailingAutotestAllBuilds'];
    $arrayFailingAutotestFailedPercentage = array();
    $arrayFailingAutotestFailedPercentage = $_SESSION['arrayFailingAutotestFailedPercentage'];
    $arrayAutotestName = array();
    $arrayAutotestName[] = $autotest;                                           // Save selected autotest into array for the readProjectTestResultDirectory function call below

    if(isset($_SESSION['arrayAutotestName'])) {
        foreach($_SESSION['arrayAutotestName'] as $key => $value) {
            /* Selected Autotest */
            if ($autotest == $value) {
                $timeAutotestHistoryStart = microtime(true);

                /* 1. Autotest history */

                /* Read Autotest history data from the database */
                $projectFilter = "";
                if ($project <> "All")
                    $projectFilter = "AND project=\"$project\"";
                $confFilter = "";
                if ($conf <> "All")
                    $confFilter = " AND cfg=\"$conf\"";
                $sql = "SELECT name, project, build_number, cfg, insignificant, timestamp
                        FROM test
                        WHERE name=\"$autotest\" $projectFilter $confFilter
                        ORDER BY project, build_number, cfg";                             // (Note: Timescale filter not used because it is very slow; Timescale checked instead when looping the data)
                $dbColumnTestName = 0;
                $dbColumnTestProject = 1;
                $dbColumnTestBuildNumber = 2;
                $dbColumnTestCfg = 3;
                $dbColumnTestInsignificant = 4;
                $dbColumnTestTimestamp = 5;
                if ($useMysqli) {
                    $result = mysqli_query($conn, $sql);
                    $numberOfRows = mysqli_num_rows($result);
                } else {
                    $selectdb="USE $db";
                    $result = mysql_query($selectdb) or die (mysql_error());
                    $result = mysql_query($sql) or die (mysql_error());
                    $numberOfRows = mysql_num_rows($result);
                }

                /* Save the Project, Conf and Build counts for the Autotest */
                $arrayFailingAutotestProjectNames = array();
                $arrayFailingAutotestProjectNames = $_SESSION['arrayProjectName'];
                $arrayFailingAutotestProjectConfNames = array();
                $arrayFailingAutotestProjectConfBuilds = array();
                $arrayFailingAutotestConfNames = array();
                $arrayFailingAutotestConfBuilds = array();
                $checkedProject = "";
                $checkedProjectCount = 0;
                for ($j=0; $j<$numberOfRows; $j++) {
                    if ($useMysqli)
                        $resultRow = mysqli_fetch_row($result);
                    else
                        $resultRow = mysql_fetch_row($result);
                    if ($resultRow[$dbColumnTestProject] <> $checkedProject) {                      // Clear Project specific Conf list when Project changes (the database list is in Project order)
                        $arrayFailingAutotestConfNames = array();
                        $arrayFailingAutotestConfBuilds = array();
                        $checkedProject = $resultRow[$dbColumnTestProject];
                        $checkedProjectCount++;
                    }
                    foreach($arrayFailingAutotestProjectNames as $projectKey => $projectValue) {    // Find the correct Project
                        if ($projectValue == $resultRow[$dbColumnTestProject]) {
                            foreach($_SESSION['arrayConfName'] as $confKey => $confValue) {         // Find the correct Configuration
                                if ($confValue == $resultRow[$dbColumnTestCfg]) {
                                    $arrayFailingAutotestConfNames[$confKey] = $confValue;
                                    $confString = ',' . $resultRow[$dbColumnTestBuildNumber]
                                        . '-' . $resultRow[$dbColumnTestInsignificant]
                                        . '-' . $resultRow[$dbColumnTestTimestamp] . ',';           // Format is ",buildNumber-testInsign" (where testInsign = 0/1); This will be used later for search usage when printing
                                    $arrayFailingAutotestConfBuilds[$confKey] = $arrayFailingAutotestConfBuilds[$confKey] . $confString;
                                    break;                                                          // Match found, skip the rest
                                }
                            }
                            $arrayFailingAutotestProjectConfNames[$projectKey] = $arrayFailingAutotestConfNames;     // Save Project specific Conf list (it uses the Project and Conf ids as saved in the initial loading of the page)
                            $arrayFailingAutotestProjectConfBuilds[$projectKey] = $arrayFailingAutotestConfBuilds;
                            break;                                                                  // Match found, skip the rest
                        }
                    }
                }

                if ($useMysqli)
                    mysqli_free_result($result);                                                    // Free result set

                /* Print the used filters */
                echo '<table>';
                echo '<tr><td>Autotest: </td><td class="tableCellBackgroundTitle">' . $autotest . '</td></tr>';
                if ($project <> "All")
                    echo '<tr><td>Project: </td><td class="tableCellBackgroundTitle">' . $project . '</td></tr>';
                if ($conf <> "All")
                    echo '<tr><td>Configuration: </td><td class="tableCellBackgroundTitle">' . $conf . '</td></tr>';
                if ($timescaleType == "Since")
                    echo '<tr><td>Since:</td><td class="tableCellBackgroundTitle">' . $timescaleValue . '</td></tr>';
                if ($project <> "All")
                    echo '<tr><td>Latest Build:</td><td>' . $latestProjectBuild . '</td></tr>';
                echo '</table>';

                /* Print Autotest history data */
                echo '<br/><br/><b>Result history by Project Configuration</b> (last ' . HISTORYBUILDCOUNT . ' Builds)<br/><br/>';
                echo '<table class="fontSmall">';
                echo '<tr class="tableCellAlignLeft">';
                echo '<th class="tableBottomBorder">Project';
                if ($project <> "All" AND $checkedProjectCount > 0)                                 // When a project filtered and some data found
                    echo ' - <a href="javascript:void(0);" onclick="filterProjectAutotest(\'All\''
                        . ',\'' . $autotest . '\')">see all</a>';                                   // ... add link to filter all projects for this autotest
                echo '</th>';
                echo '<th class="tableBottomBorder">Configuration</th>';
                echo '<th colspan="' . HISTORYBUILDCOUNT . '" class="tableBottomBorder tableSideBorder">Results in Builds';
                if ($timescaleType == "Since")
                    echo ' (since ' . $timescaleValue . ')';
                echo ' - see <a href="javascript:void(0);" onclick="showMessageWindow(\'ci/msgautotestresultdescription.html\')">notation</a>';
                echo '</th>';
                echo '</tr>';
                $arrayProjectBuildLatest = $_SESSION['arrayProjectBuildLatest'];
                $k = 0;
                $previousProject = "";
                foreach($arrayFailingAutotestProjectNames as $projectKey => $projectValue) {
                    foreach($_SESSION['arrayConfName'] as $confKey => $confValue) {
                        if ($arrayFailingAutotestProjectConfNames[$projectKey][$confKey] <> "") {
                            if ($k % 2 == 0)
                                echo '<tr>';
                            else
                                echo '<tr class="tableBackgroundColored">';
                            if ($projectValue == $previousProject) {                                // For better readability print the Project (and a line) only when it changes
                                echo '<td></td>';
                                echo '<td>' . $confValue . '</td>';
                            } else {
                                echo '<td class="tableTopBorder"><a href="javascript:void(0);" onclick="filterProjectAutotest(\''
                                    . $projectValue . '\'' . ',\'' . $autotest . '\')">' . $projectValue . '</a></td>';   // Link to filter the project and autotest
                                echo '<td class="tableTopBorder">' . $confValue . '</td>';
                            }
                            $previousProject = $projectValue;
                            $lastPrintedBuild = $arrayProjectBuildLatest[$projectKey];
                            $firstPrintedBuild = 1;
                            if ($lastPrintedBuild > HISTORYBUILDCOUNT)                              // Limit number of Builds printed (the last HISTORYBUILDCOUNT ones)
                                $firstPrintedBuild = $lastPrintedBuild - HISTORYBUILDCOUNT + 1;
                            if ($lastPrintedBuild <= HISTORYBUILDCOUNT) {                           // If latest Build number is less than the HISTORYBUILDCOUNT ...
                                for ($i=1; $i<=HISTORYBUILDCOUNT-$lastPrintedBuild; $i++) {
                                    if (HISTORYBUILDCOUNT - $lastPrintedBuild >= $i)
                                        echo '<td class="tableSingleBorder"></td>';                 // ... print empty cells to the left
                                }
                            }
                            for ($i=$firstPrintedBuild; $i<=$lastPrintedBuild; $i++) {              // Print the Builds
                                /* Check Configuration result and significance from database (both for failed and successful Autotests) */
                                $sql = "SELECT result, insignificant, timestamp
                                        FROM cfg
                                        WHERE project=\"$projectValue\" AND cfg=\"$confValue\" AND build_number=$i";     // Will return one row
                                $dbColumnCfgResult = 0;
                                $dbColumnCfgInsignificant = 1;
                                $dbColumnCfgTimestamp = 2;
                                if ($useMysqli) {
                                    $result2 = mysqli_query($conn, $sql);
                                    $resultRow2 = mysqli_fetch_row($result2);
                                } else {
                                    $selectdb="USE $db";
                                    $result2 = mysql_query($selectdb) or die (mysql_error());
                                    $result2 = mysql_query($sql) or die (mysql_error());
                                    $resultRow2 = mysql_fetch_row($result2);
                                }
                                $buildResult = "other";
                                if ($resultRow2[$dbColumnCfgResult] == "SUCCESS")
                                    $buildResult = "SUCCESS";
                                if ($resultRow2[$dbColumnCfgResult] == "FAILURE")
                                    $buildResult = "FAILURE";
                                $booBuildSign = FALSE;
                                if ($resultRow2[$dbColumnCfgInsignificant] == 0)
                                    $booBuildSign = TRUE;
                                $booBuildOutOfTimescale = FALSE;
                                if ($timescaleType == "Since")
                                    if ($resultRow2[$dbColumnCfgTimestamp] < $timescaleValue)
                                        $booBuildOutOfTimescale = TRUE;
                                /* Check Autotest result and significance from the array saved above */
                                $booSignAutotest = strpos(',' . $arrayFailingAutotestProjectConfBuilds[$projectKey][$confKey], ',' . strval($i) . '-0');
                                $booFailedAutotest = strpos(',' . $arrayFailingAutotestProjectConfBuilds[$projectKey][$confKey], ',' . strval($i) . '-');
                                $buildNumberString = createBuildNumberString($i);
                                /* Print the failed Build */
                                if ($booFailedAutotest) {
                                    if ($booSignAutotest) {
                                        if ($booBuildSign) {
                                            if ($booBuildOutOfTimescale) {
                                                // Dark red background (and bold) to indicate significant failure in blocking Conf, grey font color to indicate out of Timescale
                                                echo '<td class="tableSingleBorder tableCellCentered tableCellBackgroundRedDark fontColorGrey"><b>' . $i . '</b></td>';
                                            } else {
                                                // Dark red background (and bold) to indicate significant failure in blocking Conf; link to log file
                                                echo '<td class="tableSingleBorder tableCellCentered tableCellBackgroundRedDark"><b>
                                                    <a href="' . LOGFILEPATHCI . $projectValue . '/build_' . $buildNumberString
                                                    . '/' . $confValue . '/log.txt.gz" target="_blank">' . $i . '</a></b></td>';
                                            }
                                        } else {
                                            if ($booBuildOutOfTimescale) {
                                                // Red background to indicate significant failure in insignificant Conf, grey font color to indicate out of Timescale
                                                echo '<td class="tableSingleBorder tableCellCentered tableCellBackgroundRed fontColorGrey">' . $i . '</td>';
                                            } else {
                                                // Red background to indicate significant failure in insignificant Conf; link to log file
                                                echo '<td class="tableSingleBorder tableCellCentered tableCellBackgroundRed">
                                                    <a href="' . LOGFILEPATHCI . $projectValue . '/build_' . $buildNumberString
                                                    . '/' . $confValue . '/log.txt.gz" target="_blank">' . $i . '</a></td>';
                                            }
                                        }
                                    } else {
                                        if ($booBuildOutOfTimescale) {
                                            // Light red background to indicate insignificant failure, grey font color to indicate out of Timescale
                                            echo '<td class="tableSingleBorder tableCellCentered tableCellBackgroundRedLight fontColorGrey">' . $i . '</td>';
                                        } else {
                                            // Light red background to indicate insignificant failure; link to log file
                                            echo '<td class="tableSingleBorder tableCellCentered tableCellBackgroundRedLight">
                                                    <a href="' . LOGFILEPATHCI . $projectValue . '/build_' . $buildNumberString
                                                    . '/' . $confValue . '/log.txt.gz" target="_blank">' . $i . '</a></td>';
                                        }
                                    }
                                /* Print the successful or not run Build */
                                } else {
                                    if ($buildResult == "SUCCESS") {
                                        if ($booBuildOutOfTimescale) {
                                            // Green background to indicate success, grey font color to indicate out of Timescale
                                            echo '<td class="tableSingleBorder tableCellCentered tableCellBackgroundGreen fontColorGrey">' . $i . '</td>';
                                        } else {
                                            // Green background to indicate success; log file link
                                            echo '<td class="tableSingleBorder tableCellCentered tableCellBackgroundGreen">
                                                  <a href="' . LOGFILEPATHCI . $projectValue . '/build_' . $buildNumberString
                                                  . '/' . $confValue . '/log.txt.gz" target="_blank">' . $i . '</a></td>';
                                        }
                                    } else {       // It's not checked whether the failed Build has some failed Autotests or not i.e. to interpret if the Autotest here was SUCCESS or not run at all i.e. the Build was aborted
                                        if ($booBuildOutOfTimescale) {
                                            // White background to indicate not a failure, grey font color to indicate out of Timescale
                                            echo '<td class="tableSingleBorder tableCellCentered fontColorGrey">' . $i . '</td>';
                                        } else {
                                            // White background to indicate not a failure; log file link
                                            echo '<td class="tableSingleBorder tableCellCentered">
                                                  <a href="' . LOGFILEPATHCI . $projectValue . '/build_' . $buildNumberString
                                                  . '/' . $confValue . '/log.txt.gz" target="_blank">' . $i . '</a></td>';
                                        }
                                    }
                                }
                            }
                            echo '</tr>';
                            $k++;
                        }
                    }
                }
                echo '<tr class="tableTopBorder"><td></td><td></td><td colspan="' . HISTORYBUILDCOUNT . '"></td></tr>';    // Print bottom line to the end of the table
                echo '</table><br/>';
                if ($useMysqli)
                    mysqli_free_result($result2);                           // Free result set
                $timeAutotestHistoryEnd = microtime(true);

                /* 2. Autotest case data */
                $arrayTestcaseNames = array();
                $arrayTestcaseFailed = array();
                $arrayTestcaseAll = array();
                $arrayInvalidTestResultFiles = array();
                $arrayTestcaseConfs = array();
                $failingTestcaseCount = 0;
                $testcaseCount = 0;
                if ($round == 1) {
                    echo '<br/><b>Test cases <span class="loading"><span>.</span><span>.</span><span>.</span></span> </b><br/><br/>';
                } else {
                    echo '<br/><b>Test cases</b><br/><br/>';
                    if ($checkedProjectCount == 1) {                        // Data shown only for one project for performance reasons

                        /* Get the first available/filtered build in database */
                        $minBuildNumberInDatabase = $_SESSION['minBuildNumberInDatabase'];

                        /* Check Project directory (structure is e.g. QtBase_stable_Integration/build_03681/macx-ios-clang_OSX_10.8 */
                        $minBuildNumberToCheck = setMinBuildNumberToCheck($latestProjectBuild, $minBuildNumberInDatabase, $timescaleType);
                        $minBuildNumberInDirectory = readProjectTestResultDirectory(
                            CITESTRESULTSDIRECTORY, $checkedProject, $minBuildNumberToCheck, $conf, TRUE, $arrayAutotestName,
                            $arrayFailingAutotestBuildConfigurations, $arrayFailingAutotestAllBuilds,
                            $arrayTestcaseNames, $arrayTestcaseFailed, $arrayTestcaseAll, $arrayTestcaseConfs,
                            $failingTestcaseCount, $testcaseCount, $arrayInvalidTestResultFiles);

                        /* If test result files found */
                        if ($minBuildNumberInDirectory < MAXCIBUILDNUMBER) {

                            /* Calculate the failure percentage */
                            $maxCount = 0;
                            for ($k=0; $k<$testcaseCount; $k++) {
                                $arrayTestcaseFailedPercentage[$k] = calculatePercentage($arrayTestcaseFailed[$k], $arrayTestcaseAll[$k]);
                                if ($arrayTestcaseFailed[$k] > $maxCount)
                                    $maxCount = $arrayTestcaseFailed[$k];                                   // Save maxCount for sorting
                                if ($arrayTestcaseAll[$k] > $maxCount)
                                    $maxCount = $arrayTestcaseAll[$k];                                      // Save maxCount for sorting
                            }

                            /* Read the timestamp of the first build with detailed test result data */
                            if ($project <> "All") {
                                    $projectFilter = "WHERE project = \"$project\"";                        // Project is filtered here
                                    $buildFilter = " AND build_number = $minBuildNumberInDirectory";
                                    $from = "FROM ci";
                                    $sql = "SELECT min(timestamp)
                                            $from
                                            $projectFilter $buildFilter";
                                    $dbColumnCiTimestamp = 0;
                                    if ($useMysqli) {
                                        $result2 = mysqli_query($conn, $sql);
                                        $numberOfRows2 = mysqli_num_rows($result2);
                                    } else {
                                        $selectdb = "USE $db";
                                        $result2 = mysql_query($selectdb) or die (mysql_error());
                                        $result2 = mysql_query($sql) or die (mysql_error());
                                        $numberOfRows2 = mysql_num_rows($result2);
                                    }
                                    if ($useMysqli)
                                        $resultRow2 = mysqli_fetch_row($result2);
                                    else
                                        $resultRow2 = mysql_fetch_row($result2);
                                    $minBuildNumberInDirectoryTimestamp = $resultRow2[$dbColumnCiTimestamp];
                                    if ($useMysqli)
                                        mysqli_free_result($result2);                                       // Free result set
                            }

                            /* Print the test report info */
                            $buildCount = $latestProjectBuild - $minBuildNumberInDirectory + 1;
                            $testResultBuilds = $buildCount . ' builds since ' .$minBuildNumberInDirectory . ' ('
                                . substr($minBuildNumberInDirectoryTimestamp, 0, strpos($minBuildNumberInDirectoryTimestamp, " ")) . ')';
                            $testResultBuildsSeeMore = setSeeMoreNote($timescaleType, $timescaleValue);
                            $failingTestcasePercentage = calculatePercentage($failingTestcaseCount, $testcaseCount);
                            echo '<table>';
                            echo '<tr><td>Test Results from:</td><td>' . $testResultBuilds . $testResultBuildsSeeMore . '</td></tr>';
                            echo '</table>';
                            echo '<br><br>';

                            /* Set the default sorting to Failed % when displayed */
                            $sortBy = AUTOTESTSORTBYAUTOTESTFAILEDPERCENTAGE;

                            /* Print the test case table titles */
                            echo '<table class="fontSmall">';
                            echo '<tr>';
                            echo '<th></th>';
                            if ($timescaleType == "All" AND CITESTRESULTBUILDCOUNT == 1)
                                $buildData = '<th colspan="3" class="tableBottomBorder tableSideBorder">LATEST BUILD</th>';
                            if ($timescaleType == "All" AND CITESTRESULTBUILDCOUNT > 1)
                                $buildData = '<th colspan="3" class="tableBottomBorder tableSideBorder">ALL BUILDS (SINCE ' . $_SESSION['minBuildDate'] . ')</th>';
                            if ($timescaleType == "Since")
                                $buildData = '<th colspan="3" class="tableBottomBorder tableSideBorder">ALL BUILDS SINCE ' . $timescaleValue . '</th>';
                            echo $buildData;
                            if ($conf == "All") {                                               // Show list of configurations only when not filtered
                                echo '<th></th>';
                                echo $buildData;
                            }
                            echo '</tr>';

                            echo '<tr>';
                            echo '<th></th>';
                            $xmlBuildInfo = '<th colspan="3" class="tableBottomBorder tableSideBorder">Detailed Test Results';
                            if ($timescaleType == "Since" OR CITESTRESULTBUILDCOUNT > 1)
                                $xmlBuildInfo = $xmlBuildInfo . ' since Build ' . $minBuildNumberInDirectory;
                            $xmlBuildInfo = $xmlBuildInfo . '</th>';
                            echo $xmlBuildInfo;
                            if ($conf == "All") {                                               // Show list of configurations only when not filtered
                                echo '<th></th>';
                                echo $xmlBuildInfo;
                            }
                            echo '</tr>';

                            echo '<tr>';
                            echo '<th class="tableBottomBorder tableCellAlignLeft">Failed test cases</th>';
                            echo '<td class="tableBottomBorder tableLeftBorder tableCellCentered">Failed</td>';
                            echo '<td class="tableBottomBorder tableCellCentered">Total</td>';
                            echo '<td class="sortField tableBottomBorder tableRightBorder tableCellCentered">';
                            if ($sortBy == AUTOTESTSORTBYAUTOTESTFAILEDPERCENTAGE)
                                echo 'Failed % &diams;';                                        // Identify selected sorting
                            else
                                echo '<a href="javascript:void(0);" onclick="filterAutotest(\'' . $autotest . '\',' . AUTOTESTSORTBYAUTOTESTFAILEDPERCENTAGE . ')">
                                      Failed % <img src="images/sort-descending.png" alt="Sort" title="sort descending"></a>';
                            echo '</td>';
                            if ($conf == "All") {                                               // Show list of configurations only when not filtered
                                echo '<th class="tableBottomBorder tableCellAlignLeft">';
                                echo 'Configuration';
                                echo '<td class="tableBottomBorder tableLeftBorder tableCellCentered">Failed</td>';
                                echo '<td class="tableBottomBorder tableCellCentered">Total</td>';
                                echo '<td class="sortField tableBottomBorder tableRightBorder tableCellCentered">Failed %</td>';
                                echo '</th>';
                            }
                            echo '</tr>';

                            /* Print list of test cases */
                            $k = 0;
                            if ($maxCount < 100)
                                $maxCount = 100;                                                // Loop at least the percentage scale
                            for ($countOrder=$maxCount; $countOrder>=0; $countOrder--) {        // Sort the list by looping from the highest count
                                for ($i=0; $i<$testcaseCount; $i++) {                           // Loop the test cases
                                    if ($sortBy == AUTOTESTSORTBYAUTOTESTFAILEDPERCENTAGE)
                                        $sortFieldValue = $arrayTestcaseFailedPercentage[$i];
                                    else
                                        $sortFieldValue = $arrayTestcaseFailed[$i];
                                    if ($sortFieldValue == $countOrder) {                       // Print the ones that are next in the sorting order
                                        if ($arrayTestcaseFailed[$i]
                                            + $arrayTestcaseFailedPercentage[$i] > 0) {         // Skip if not any failures
                                            if ($k % 2 == 0)
                                                echo '<tr>';
                                            else
                                                echo '<tr class="tableBackgroundColored">';

                                            /* Test case name */
                                            echo '<td>' . $arrayTestcaseNames[$i] . '</td>';

                                            /* All Builds: Builds where failed */
                                            echo '<td class="tableLeftBorder tableCellCentered tableWidth2">';
                                            if ($arrayTestcaseFailed[$i] > 0)
                                                echo $arrayTestcaseFailed[$i];
                                            else
                                                echo '-';
                                            echo '</td>';

                                            /* All Builds: Builds where run (all) */
                                            echo '<td class="tableCellCentered tableWidth2">';
                                            if ($arrayTestcaseAll[$i] > 0)
                                                echo $arrayTestcaseAll[$i];
                                            else
                                                echo '-';
                                            echo '</td>';

                                            /* All Builds: Failed % */
                                            if ($arrayTestcaseFailedPercentage[$i] >= AUTOTESTFAILUREWARNINGLEVEL)
                                                echo '<td class="tableRightBorder tableCellCentered fontColorRed tableWidth1">';
                                            else
                                                echo '<td class="tableRightBorder tableCellCentered tableWidth2">';
                                            if ($arrayTestcaseFailedPercentage[$i] > 0)
                                                echo $arrayTestcaseFailedPercentage[$i] . '%';
                                            else
                                                echo '-';
                                            echo '</td>';

                                            /* All Builds: Configurations */
                                            if ($conf == "All") {                                       // Show list of configurations only when not filtered
                                                echo '<td class="tableTopBorder tableLeftBorder">';     // Configuration name
                                                echo '<table>';
                                                $m = 0;
                                                foreach ($arrayTestcaseConfs[$i] as $confKey => $testcaseConf)
                                                    if ($testcaseConf[FAILINGTESTCASECONFFAILED] > 0) {
                                                        if ($m == 0)
                                                            echo '<tr>';
                                                        else
                                                            echo '<tr class="tableTopBorder">';
                                                        echo '<td>' . $testcaseConf[FAILINGTESTCASECONFNAME] . '</td>';
                                                        echo '</tr>';
                                                        $m++;
                                                    }
                                                echo '</table>';
                                                echo '</td>';
                                                echo '<td class="tableTopBorder tableLeftBorder">';     // Failed testcases
                                                echo '<table class="tableWidth2">';
                                                $m = 0;
                                                foreach ($arrayTestcaseConfs[$i] as $confKey => $testcaseConf)
                                                    if ($testcaseConf[FAILINGTESTCASECONFFAILED] > 0) {
                                                        if ($m == 0)
                                                            echo '<tr>';
                                                        else
                                                            echo '<tr class="tableTopBorder">';
                                                        echo '<td class="tableCellCentered">' . $testcaseConf[FAILINGTESTCASECONFFAILED] . '</td>';
                                                        echo '</tr>';
                                                        $m++;
                                                    }
                                                echo '</table>';
                                                echo '</td>';
                                                echo '<td class="tableTopBorder">';                     // All testcases
                                                echo '<table class="tableWidth2">';
                                                $m = 0;
                                                foreach ($arrayTestcaseConfs[$i] as $confKey => $testcaseConf)
                                                    if ($testcaseConf[FAILINGTESTCASECONFFAILED] > 0) {
                                                        if ($m == 0)
                                                            echo '<tr>';
                                                        else
                                                            echo '<tr class="tableTopBorder">';
                                                        echo '<td class="tableCellCentered">' . $testcaseConf[FAILINGTESTCASECONFALL] . '</td>';
                                                        echo '</tr>';
                                                        $m++;
                                                    }
                                                echo '</table>';
                                                echo '</td>';
                                                echo '<td class="tableTopBorder tableRightBorder">';    // Failed %
                                                echo '<table class="tableWidth2">';
                                                $m = 0;
                                                foreach ($arrayTestcaseConfs[$i] as $confKey => $testcaseConf)
                                                    if ($testcaseConf[FAILINGTESTCASECONFFAILED] > 0) {
                                                        if ($m == 0)
                                                            echo '<tr>';
                                                        else
                                                            echo '<tr class="tableTopBorder">';
                                                        $testcaseConfFailedPercentage = calculatePercentage(
                                                            $testcaseConf[FAILINGTESTCASECONFFAILED],
                                                            $testcaseConf[FAILINGTESTCASECONFALL]);
                                                        if ($testcaseConfFailedPercentage >= AUTOTESTFAILUREWARNINGLEVEL)
                                                            echo '<td class="tableCellCentered fontColorRed">';
                                                        else
                                                            echo '<td class="tableCellCentered">';
                                                        echo $testcaseConfFailedPercentage . '%';
                                                        echo '</td>';
                                                        echo '</tr>';
                                                        $m++;
                                                    }
                                                echo '</table>';
                                                echo '</td>';
                                            }

                                            echo '</tr>';
                                            $k++;
                                        }
                                    } // Endif sorting order
                                } // Endfor Autotests
                            } // Endfor sorting
                            $printedTestcases = $k;

                            /* Print Totals summary row */
                            $arrayTestcaseFailedSum = 0;
                            $arrayTestcaseAllSum = 0;
                            $arrayTestcaseFailedPercentageSum = 0;
                            for ($i=0; $i<$testcaseCount; $i++) {                               // Loop the test cases
                                if ($arrayTestcaseFailed[$i] > 0) {
                                    $arrayTestcaseFailedSum = $arrayTestcaseFailedSum + $arrayTestcaseFailed[$i];
                                    $arrayTestcaseAllSum = $arrayTestcaseAllSum + $arrayTestcaseAll[$i];
                                }
                            }
                            $arrayTestcaseFailedPercentageSum = 100 * round($arrayTestcaseFailedSum / $arrayTestcaseAllSum, 2);
                            echo '<tr>';
                            echo '<td class="tableTopBorder">total (' . $failingTestcaseCount . ' failed / ' . $testcaseCount
                                . ' total = ' . $failingTestcasePercentage . '%)</td>';
                            echo '<td class="tableCellCentered tableLeftBorder tableTopBorder">' . $arrayTestcaseFailedSum . '</td>';
                            echo '<td class="tableCellCentered tableTopBorder">' . $arrayTestcaseAllSum . '</td>';
                            echo '<td class="tableRightBorder tableTopBorder tableCellCentered">' . $arrayTestcaseFailedPercentageSum . '%</td>';
                            if ($conf == "All")                                                 // Show list of configurations only when not filtered
                                echo '<td colspan="4" class="tableRightBorder tableTopBorder tableCellCentered"></td>';
                            echo '</tr>';
                            echo '</table>';

                            /* Print the list of files that couldn't be opened */
                            if (count($arrayInvalidTestResultFiles) > 0) {
                                echo '<br>';
                                echo '<table class="fontSmall fontColorGrey">';
                                echo '<tr>';
                                echo '<th class="tableCellAlignLeft">Total ' . count($arrayInvalidTestResultFiles)
                                    . ' test result files could not be opened (the xml format may be invalid)</th>';
                                echo '</tr>';
                                foreach ($arrayInvalidTestResultFiles as $invalidFile) {
                                    echo '<tr>';
                                    echo '<td>' . $invalidFile . '</td>';
                                    echo '</tr>';
                                }
                                echo '</table>';
                            }

                        } else {
                            echo '(not any test result files available)<br/><br/>';
                        }

                    } else {
                        echo '<i>Please select one of the Projects above to see the test case list...</i><br/><br/>';
                    }
                    $timeAutotestCaseEnd = microtime(true);
                }

                break;                                                  // Match found, skip the rest

            } // endif Selected Autotest
        } // endif foreach
    } else {
        echo '<br/>Filter values not ready or they are expired, please <a href="javascript:void(0);" onclick="reloadFilters()">reload</a> ...';
    }

    /* Elapsed time */
    if ($showElapsedTime) {
        $timeEnd = microtime(true);
        $timeDbConnect = round($timeConnect - $timeStart, 2);
        $timeHistory = round($timeAutotestHistoryEnd - $timeAutotestHistoryStart, 2);
        if (isset($timeAutotestCaseEnd))
            $timeCases = round($timeAutotestCaseEnd - $timeAutotestHistoryEnd, 2);
        $time = round($timeEnd - $timeStart, 2);
        echo "<div class=\"elapdedTime\">";
        echo "<ul><li>";
        echo "Total time: $time s (database connect time: $timeDbConnect s, history list time: $timeHistory s";
        if (isset($timeAutotestCaseEnd))
            echo ", test case list time: $timeCases s";
        echo ")</li></ul>";
        echo "</div>";
    }

}

/* Close connection to the server */
require(__DIR__.'/../connectionclose.php');

?>
