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
   Input:   $latestBuildNumber                       (integer) Latest build number in the project
            $minBuildNumberInDatabase                (integer) The first build number that is available in the database
            $timescaleType                           (string)  The timescale filter
   Return:  (integer) The lowest build number to be checked
*/
function setMinBuildNumberToCheck($latestBuildNumber, $minBuildNumberInDatabase, $timescaleType)
{
    if (($latestBuildNumber - $minBuildNumberInDatabase) > CITESTRESULTBUILDCOUNT)      // Read only CITESTRESULTBUILDCOUNT latest builds
        $minBuildNumber = $latestBuildNumber - CITESTRESULTBUILDCOUNT + 1;
    else                                                                                // ... or the first that is available in the database
        $minBuildNumber = $minBuildNumberInDatabase;
    if ($timescaleType <> "All")                                                        // ... but if timescale filter used, use that instead
        $minBuildNumber = $minBuildNumberInDatabase;
    return $minBuildNumber;
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
            $seeMoreNote = $seeMoreNote . 'or selected build for clarity and to optimize the performance.';
        else
            $seeMoreNote = $seeMoreNote . CITESTRESULTBUILDCOUNT . ' builds to optimize the performance.';
    } else {
        $seeMoreNote = $seeMoreNote . 'The list below includes the results checked from the builds since '
            . $timescaleValue . '.';
    }
    $seeMoreNote = $seeMoreNote . ' You can include more builds with the timescale filter in the filter box on the top of the page.';
    if ($timescaleType <> "All")
        $seeMoreNote = $seeMoreNote . ' To show only the latest or selected build please clear the timescale filter.';
    $seeMoreNote = $seeMoreNote . '</span></span> &raquo;';
    return $seeMoreNote;
}

/* Scan the test result directory for test result zip files and their xml result files
   Input:   $testResultDirectory                     (string)  Full path to the test result directory (containing the project directories)
            $project                                 (string)  Project name filtered as in the test result directory and database
            $buildCheckType                          (const)   CHECKBUILDSINCE to check all builds since $buildNumber, CHECKBUILDONE to check only the $buildNumber
            $buildNumber                             (integer) Minimum build number to be checked
            $conf                                    (string)  Configuration name filtered
            $booCheckTestcases                       (boolean) TRUE to open the test result files in the zip file and check the test cases, FALSE just to scan the test result file names in the zip
            $arrayFailingAutotestNames               (array)   List of autotest names as in the test result directory and database
   Output:  $arrayFailingAutotestAllBuilds           (array)   The count of builds (in all project configurations and in all builds within the timescale) of each autotest
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
define("CHECKBUILDSINCE", 0);
define("CHECKBUILDONE", 1);
function readProjectTestResultDirectory(
            $testResultDirectory, $project, $buildCheckType, $buildNumber, $conf, $booCheckTestcases, $arrayFailingAutotestNames,
            &$arrayFailingAutotestAllBuilds,
            &$arrayTestcaseNames, &$arrayTestcaseFailed, &$arrayTestcaseAll, &$arrayTestcaseConfs,
            &$failingTestcaseCount, &$testcaseCount, &$arrayInvalidTestResultFiles)
{
    $arrayFailingTestcaseConfNames = array();
    /* Count the number of autotests */
    $autotestCount = count($arrayFailingAutotestNames);
    /* Check Project directory (structure is e.g. "QtBase_stable_Integration/build_03681/macx-ios-clang_OSX_10.8" */
    $projectTestResultDirectory = $testResultDirectory . $project;
    $minBuildNumberWithTestResults = MAXCIBUILDNUMBER;                      // For saving the first build where test result xml files are available
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
            $buildNumberDirName = CIBUILDDIRECTORYPREFIX . createBuildNumberString($buildNumber);       // Convert "220" -> "build_00220"
            if (strlen($dirName) == strlen($buildNumberDirName))
                continue;                                                   // Skip the main build directory (to check only the configuration directories under it)
            if ($buildCheckType == CHECKBUILDSINCE) {                       // Check builds since the requested build ...
                if (strpos($dirName, CIBUILDDIRECTORYPREFIX) === 0 AND $dirName < $buildNumberDirName)
                    continue;                                               // Skip to next directory if not inside the build scope (calculated from time scope)
            } else {                                                        // ... or the requested build only
                if (strpos($dirName, CIBUILDDIRECTORYPREFIX) === 0 AND strpos($dirName, $buildNumberDirName) === FALSE)
                    continue;                                               // Skip to next directory if not the requested build
            }
            /* Continue if build belongs to the time scale */
            $handle = opendir($directory);
            while (($entry = readdir($handle)) !== FALSE) {                 // Check the results in zip file
                if ($entry == "." || $entry == "..")
                    continue;
                $configuration = substr($dirName, strlen($buildNumberDirName) + 1);                                     // Cut to e.g. "macx-ios-clang_OSX_10.8"
                if ($conf <> "All" AND !checkStringMatch($configuration, $conf))                                        // Skip if not the filtered configuration
                    continue;
                $dirNumber = (int)substr($dirName, strlen(CIBUILDDIRECTORYPREFIX), strlen(strval(MAXCIBUILDNUMBER)));   // Cut to e.g. "3681"
                if (!in_array($configuration, $arrayFailingTestcaseConfNames))                                          // If configuration name not listed yet ...
                    $arrayFailingTestcaseConfNames[] = $configuration;                                                  // ... save it
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
                                if ($minBuildNumberWithTestResults > $dirNumber)
                                    $minBuildNumberWithTestResults = $dirNumber;    // Save the lowest build where test result xml files are available
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
                                                        if (!in_array($testcaseFullName, $arrayTestcaseNames)) {     // If not yet listed ...
                                                            $arrayTestcaseNames[] = $testcaseFullName;               // Testcase name
                                                            $testcaseConfData = array(array());
                                                            $testcaseConfData[$confId][FAILINGTESTCASECONFNAME]
                                                                = $arrayFailingTestcaseConfNames[$confId];           // Configuration name for the testcase
                                                            if ($result['type'] == "fail" OR $result['type'] == "xpass") {  // The xpass is considered a fail in the test system
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
                                                                    if ($result['type'] == "fail" OR $result['type'] == "xpass") {  // The xpass is considered a fail in the test system
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
        $arrayAllTestcaseNames = array_unique($arrayAllTestcaseNames);
        $testcaseCount = count($arrayTestcaseNames);
        foreach ($arrayTestcaseNames as $key => $testcaseName) {
            if ($arrayTestcaseFailed[$key] > 0)
                $failingTestcaseCount++;
        }
        sort($arrayInvalidTestResultFiles);

    } // endif file_exists()
    return $minBuildNumberWithTestResults;
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
$arrayFilter = explode(FILTERVALUESEPARATOR, $arrayFilters[FILTERCIPROJECT]);
$ciProject = $arrayFilter[1];
$arrayFilter = explode(FILTERVALUESEPARATOR, $arrayFilters[FILTERCIBRANCH]);
$ciBranch = $arrayFilter[1];
$arrayFilter = explode(FILTERVALUESEPARATOR, $arrayFilters[FILTERCIPLATFORM]);
$ciPlatform = $arrayFilter[1];
$arrayFilter = explode(FILTERVALUESEPARATOR, $arrayFilters[FILTERCONF]);
$conf = $arrayFilter[1];
$arrayFilter = explode(FILTERVALUESEPARATOR, $arrayFilters[FILTERAUTOTEST]);
$autotest = $arrayFilter[1];
$arrayFilter = explode(FILTERVALUESEPARATOR, $arrayFilters[FILTERBUILD]);
$build = $arrayFilter[1];
$arrayFilter = explode(FILTERVALUESEPARATOR, $arrayFilters[FILTERTIMESCALETYPE]);
$timescaleType = $arrayFilter[1];
$arrayFilter = explode(FILTERVALUESEPARATOR, $arrayFilters[FILTERTIMESCALEVALUE]);
$timescaleValue = $arrayFilter[1];
$arrayFilter = explode(FILTERVALUESEPARATOR, $arrayFilters[FILTERSORTBY]);
$sortBy = $arrayFilter[1];
$arrayFilter = explode(FILTERVALUESEPARATOR, $arrayFilters[FILTERSHOWALL]);
$showAll = $arrayFilter[1];

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

/* Platform filter definitions */
if ($ciPlatform == "All")
    $ciPlatform = 0;
$ciPlatform = (int)$ciPlatform;
$ciPlatformName = $arrayPlatform[$ciPlatform][0];
$ciPlatformFilter = $arrayPlatform[$ciPlatform][1];
$ciPlatformFilterSql = str_replace('*', '%', $arrayPlatform[$ciPlatform][1]);       // Change the format for MySQL (wildcard '*' -> '%')

/* Check the the latest/selected build number for the Project */
$buildNumber = MAXCIBUILDNUMBER;
if ($project <> "All") {
    foreach ($_SESSION['arrayProjectName'] as $projectKey => $projectValue) {
        if ($project == $projectValue)
            $latestBuildNumber = $_SESSION['arrayProjectBuildLatest'][$projectKey];
            $minBuildNumberWithTestResults = $latestBuildNumber;                    // Initialize, to be calculated later from test results
    }
    $buildNumber = $latestBuildNumber - $build;                                     // Selected build
}

/*************************************************************/
/* NESTED LEVEL 1: No autotest filtering done (default view) */
/*************************************************************/

if ($autotest == "All") {
    echo '<div class="metricsBoxHeader">';
    echo '<div class="metricsBoxHeaderIcon">';
    if ($round == 1)
        echo "<img src=\"images/ajax-loader.gif\" alt=\"loading\">&nbsp&nbsp";      // On the first round show the loading icon
    else
        echo '<a href="javascript:void(0);" class="imgLink" onclick="showMessageWindow(\'ci/msgautotestdashboardlevel1.html\')">
              <img src="images/info.png" alt="info"></a>&nbsp&nbsp';
    echo '</div>';
    echo '<div class="metricsBoxHeaderText">';
    echo '<b>AUTOTEST DASHBOARD:</b> Select Autotest';
    echo '</div>';
    echo '</div>';

    if (isset($_SESSION['arrayAutotestName'])) {

        /* Get all (failing) Autotest names and required Project data */
        $arrayFailingAutotestNames = array();
        $arrayFailingAutotestNames = $_SESSION['arrayAutotestName'];
        $autotestCount = count($arrayFailingAutotestNames);
        $arrayProjectName = $_SESSION['arrayProjectName'];
        $arrayProjectBuildLatest = $_SESSION['arrayProjectBuildLatest'];
        $arrayProjectBuildLatestTimestamp = $_SESSION['arrayProjectBuildLatestTimestamp'];

        /* Arrays for number and names Configurations for each Autotest in the latest/selected Build (categorised as significant/insignificant) */
        define("SIGNAUTOTESTBLOCKINGCONF", 0);
        $arrayFailingSignAutotestBlockingConfCount = array();                       // Each Conf counted only once (even if in different Project)
        $arrayFailingSignAutotestBlockingConfCountTotal = array();                  // Each Conf counted separately for each Project
        $arrayFailingSignAutotestBlockingConfNames = array();                       // Each Conf listed only once but with count
        $arrayFailingSignAutotestBlockingConfProjects = array();                    // Note: Data inserted into table format <tr><td></td><td></td></tr> without the <table></table>
        define("SIGNAUTOTESTINSIGNCONF", 1);
        $arrayFailingSignAutotestInsignConfCount = array();
        $arrayFailingSignAutotestInsignConfCountTotal = array();
        $arrayFailingSignAutotestInsignConfNames = array();
        $arrayFailingSignAutotestInsignConfProjects = array();
        define("INSIGNAUTOTESTBLOCKINGCONF", 2);
        $arrayFailingInsignAutotestBlockingConfCount = array();
        $arrayFailingInsignAutotestBlockingConfCountTotal = array();
        $arrayFailingInsignAutotestBlockingConfNames = array();
        $arrayFailingInsignAutotestBlockingConfProjects = array();
        define("INSIGNAUTOTESTINSIGNCONF", 3);
        $arrayFailingInsignAutotestInsignConfCount = array();
        $arrayFailingInsignAutotestInsignConfCountTotal = array();
        $arrayFailingInsignAutotestInsignConfNames = array();
        $arrayFailingInsignAutotestInsignConfProjects = array();

        /* Step 1: Read failing Autotests for the latest/selected Build (for each Project and Configuration) */
        $timeLatestStart = microtime(true);
        $maxCount = 0;                                                              // Max count of Autotests in any category (used for sorting the lists)
        $latestAutotests = 0;                                                       // Total count of Autotests in any category (used to identify if any was found)
        $projectFilter = "";
        $projectFilterLatest = "";
        $buildFilter = "";
        $confFilter = "";
        $confFilterLatest = "";
        if ($project <> "All") {                                                    // Project filtering
            $projectFilterLatest = "test_latest.project=\"$project\"";
            $projectFilter = "test.project=\"$project\"";
            $buildFilter = "AND test.build_number=$buildNumber";
        } else {
            if ($ciProject <> "All") {                                              // Filter with Project name (starting with it)
                $projectFilterLatest = 'test_latest.project LIKE "' . $ciProject . '_%"';
                $projectFilter = 'test.project LIKE "' . $ciProject . '_%"';
            }
            if ($ciBranch <> "All") {                                               // Filter with Project branch (in the middle)
                $projectFilterLatest = 'test_latest.project LIKE "%_' . $ciBranch . '_%"';
                $projectFilter = 'test.project LIKE "%_' . $ciBranch . '_%"';
            }
            if ($ciProject <> "All" AND $ciBranch <> "All") {                       // Filter with Project name and branch (starting with it)
                $projectFilterLatest = 'test_latest.project LIKE "' . $ciProject . '_' . $ciBranch . '_%"';
                $projectFilter = 'test.project LIKE "' . $ciProject . '_' . $ciBranch . '_%"';
            }
        }
        if ($ciPlatform <> 0) {                                                     // Filter with Platform
            $confFilterLatest = 'AND test_latest.cfg LIKE "' . $ciPlatformFilterSql . '"';
            $confFilter = 'AND test.cfg LIKE "' . $ciPlatformFilterSql . '"';
        }
        if ($conf <> "All") {                                                       // Filter with Conf (overwrite possible Platform filter)
            $confFilterLatest = "AND test_latest.cfg=\"$conf\"";
            $confFilter = "AND test.cfg=\"$conf\"";
        }
        if ($build == 0)                                // Show the latest build ...
            $sql = cleanSqlString(
                   "SELECT name, test_latest.project, test_latest.insignificant, test_latest.timestamp, cfg_latest.cfg, cfg_latest.insignificant
                    FROM test_latest left join cfg_latest on (test_latest.project = cfg_latest.project AND
                                                              test_latest.cfg = cfg_latest.cfg AND
                                                              test_latest.build_number = cfg_latest.build_number)
                    WHERE $projectFilterLatest $confFilterLatest");
        else                                            // ... or the selected build
            $sql = cleanSqlString(
                   "SELECT name, test.project, test.insignificant, test.timestamp, cfg.cfg, cfg.insignificant
                    FROM test left join cfg on (test.project = cfg.project AND
                                                test.cfg = cfg.cfg AND
                                                test.build_number = cfg.build_number)
                    WHERE $projectFilter $buildFilter $confFilter");
        $dbColumnTestName = 0;
        $dbColumnTestProject = 1;
        $dbColumnTestInsignificant = 2;
        $dbColumnTestTimestamp = 3;
        $dbColumnCfgCfg = 4;
        $dbColumnCfgInsignificant = 5;
        $timeLatestSelectStart = microtime(true);
        if ($useMysqli) {
            $result = mysqli_query($conn, $sql);
            $numberOfRows = mysqli_num_rows($result);
        } else {
            $result = mysql_query($sql) or die (mysql_error());
            $numberOfRows = mysql_num_rows($result);
        }
        $timeLatestSelectEnd = microtime(true);
        for ($j=0; $j<$numberOfRows; $j++) {                                        // Loop the queried Autotests
            if ($useMysqli)
                $resultRow = mysqli_fetch_row($result);
            else
                $resultRow = mysql_fetch_row($result);
            if ($project == "All" AND $timescaleType == "Since") {                  // When all Projects shown and Timescale filtered
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
            $projectValue = $resultRow[$dbColumnTestProject];
            for ($k=0; $k<$autotestCount; $k++) {                                   // Loop all the available Autotests to collect data per one autotest
                if ($arrayFailingAutotestNames[$k] == $resultRow[$dbColumnTestName]) {
                    switch ($autotestFailureCategory) {
                        case SIGNAUTOTESTBLOCKINGCONF:
                            $arrayFailingSignAutotestBlockingConfCountTotal[$k]++;
                            if (!strpos($arrayFailingSignAutotestBlockingConfNames[$k],$resultRow[$dbColumnCfgCfg])) {   // Each Conf to be listed only once
                                $arrayFailingSignAutotestBlockingConfCount[$k]++;
                                $latestAutotests++;
                                $arrayFailingSignAutotestBlockingConfNames[$k]
                                    = $arrayFailingSignAutotestBlockingConfNames[$k] . '<br>' . $resultRow[$dbColumnCfgCfg];
                                if ($arrayFailingSignAutotestBlockingConfCount[$k] > $maxCount)
                                    $maxCount = $arrayFailingSignAutotestBlockingConfCount[$k];
                            } else {                                                                                     // Indicate if same Conf in several Projects
                                $arrayFailingSignAutotestBlockingConfNames[$k] =
                                    str_replace($resultRow[$dbColumnCfgCfg],
                                                $resultRow[$dbColumnCfgCfg] . ' (several)',
                                                $arrayFailingSignAutotestBlockingConfNames[$k]);
                                $arrayFailingSignAutotestBlockingConfNames[$k] =
                                    str_replace('(several) (several)', '(several)',
                                                $arrayFailingSignAutotestBlockingConfNames[$k]);
                            }
                            $arrayFailingSignAutotestBlockingConfProjects[$k]
                                = $arrayFailingSignAutotestBlockingConfProjects[$k]
                                . '<tr><td>' . $projectValue . '</td><td>' . $resultRow[$dbColumnCfgCfg] . '</td></tr>'; // List Projects for each Conf (i.e. one Project or Conf may appear several times)
                            break;
                        case SIGNAUTOTESTINSIGNCONF:
                            $arrayFailingSignAutotestInsignConfCountTotal[$k]++;
                            if (!strpos($arrayFailingSignAutotestInsignConfNames[$k],$resultRow[$dbColumnCfgCfg])) {     // Each Conf to be listed only once
                                $arrayFailingSignAutotestInsignConfCount[$k]++;
                                $latestAutotests++;
                                $arrayFailingSignAutotestInsignConfNames[$k]
                                    = $arrayFailingSignAutotestInsignConfNames[$k] . '<br>' . $resultRow[$dbColumnCfgCfg];
                                if ($arrayFailingSignAutotestInsignConfCount[$k] > $maxCount)
                                    $maxCount = $arrayFailingSignAutotestInsignConfCount[$k];
                            } else {                                                                                     // Indicate if same Conf in several Projects
                                $arrayFailingSignAutotestInsignConfNames[$k] =
                                    str_replace($resultRow[$dbColumnCfgCfg],
                                                $resultRow[$dbColumnCfgCfg] . ' (several)',
                                                $arrayFailingSignAutotestInsignConfNames[$k]);
                                $arrayFailingSignAutotestInsignConfNames[$k] =
                                    str_replace('(several) (several)', '(several)',
                                                $arrayFailingSignAutotestInsignConfNames[$k]);
                            }
                            $arrayFailingSignAutotestInsignConfProjects[$k]
                                = $arrayFailingSignAutotestInsignConfProjects[$k]
                                . '<tr><td>' . $projectValue . '</td><td>' . $resultRow[$dbColumnCfgCfg] . '</td></tr>'; // List Projects for each Conf (i.e. one Project or Conf may appear several times)
                            break;
                        case INSIGNAUTOTESTBLOCKINGCONF:
                            $arrayFailingInsignAutotestBlockingConfCountTotal[$k]++;
                            if (!strpos($arrayFailingInsignAutotestBlockingConfNames[$k],$resultRow[$dbColumnCfgCfg])) { // Each Conf to be listed only once
                                $arrayFailingInsignAutotestBlockingConfCount[$k]++;
                                $latestAutotests++;
                                $arrayFailingInsignAutotestBlockingConfNames[$k]
                                    = $arrayFailingInsignAutotestBlockingConfNames[$k] . '<br>' . $resultRow[$dbColumnCfgCfg];
                                if ($arrayFailingInsignAutotestBlockingConfCount[$k] > $maxCount)
                                    $maxCount = $arrayFailingInsignAutotestBlockingConfCount[$k];
                            } else {                                                                                     // Indicate if same Conf in several Projects
                                $arrayFailingInsignAutotestBlockingConfNames[$k] =
                                    str_replace($resultRow[$dbColumnCfgCfg],
                                                $resultRow[$dbColumnCfgCfg] . ' (several)',
                                                $arrayFailingInsignAutotestBlockingConfNames[$k]);
                                $arrayFailingInsignAutotestBlockingConfNames[$k] =
                                    str_replace('(several) (several)', '(several)',
                                                $arrayFailingInsignAutotestBlockingConfNames[$k]);
                            }
                            $arrayFailingInsignAutotestBlockingConfProjects[$k]
                                = $arrayFailingInsignAutotestBlockingConfProjects[$k]
                                . '<tr><td>' . $projectValue . '</td><td>' . $resultRow[$dbColumnCfgCfg] . '</td></tr>'; // List Projects for each Conf (i.e. one Project or Conf may appear several times)
                            break;
                        case INSIGNAUTOTESTINSIGNCONF:
                            $arrayFailingInsignAutotestInsignConfCountTotal[$k]++;
                            if (!strpos($arrayFailingInsignAutotestInsignConfNames[$k],$resultRow[$dbColumnCfgCfg])) {   // Each Conf to be listed only once
                                $arrayFailingInsignAutotestInsignConfCount[$k]++;
                                $latestAutotests++;
                                $arrayFailingInsignAutotestInsignConfNames[$k]
                                    = $arrayFailingInsignAutotestInsignConfNames[$k] . '<br>' . $resultRow[$dbColumnCfgCfg];
                                if ($arrayFailingInsignAutotestInsignConfCount[$k] > $maxCount)
                                    $maxCount = $arrayFailingInsignAutotestInsignConfCount[$k];
                            } else {                                                                                     // Indicate if same Conf in several Projects
                                $arrayFailingInsignAutotestInsignConfNames[$k] =
                                    str_replace($resultRow[$dbColumnCfgCfg],
                                                $resultRow[$dbColumnCfgCfg] . ' (several)',
                                                $arrayFailingInsignAutotestInsignConfNames[$k]);
                                $arrayFailingInsignAutotestInsignConfNames[$k] =
                                    str_replace('(several) (several)', '(several)',
                                                $arrayFailingInsignAutotestInsignConfNames[$k]);
                            }
                            $arrayFailingInsignAutotestInsignConfProjects[$k]
                                = $arrayFailingInsignAutotestInsignConfProjects[$k]
                                . '<tr><td>' . $projectValue . '</td><td>' . $resultRow[$dbColumnCfgCfg] . '</td></tr>'; // List Projects for each Conf (i.e. one Project or Conf may appear several times)
                            break;
                    }
                    break;                                                          // Match found, skip the rest
                }
            }            // Endfor all available Autotests
        }                // Endfor queried Autotests

        /* Save data to session variables to be able to use them in nested level 2 below */
        $_SESSION['arrayFailingSignAutotestBlockingConfCount'] = $arrayFailingSignAutotestBlockingConfCount;
        $_SESSION['arrayFailingSignAutotestBlockingConfCountTotal'] = $arrayFailingSignAutotestBlockingConfCountTotal;
        $_SESSION['arrayFailingSignAutotestBlockingConfNames'] = $arrayFailingSignAutotestBlockingConfNames;
        $_SESSION['arrayFailingSignAutotestBlockingConfProjects'] = $arrayFailingSignAutotestBlockingConfProjects;
        $_SESSION['arrayFailingSignAutotestInsignConfCount'] = $arrayFailingSignAutotestInsignConfCount;
        $_SESSION['arrayFailingSignAutotestInsignConfCountTotal'] = $arrayFailingSignAutotestInsignConfCountTotal;
        $_SESSION['arrayFailingSignAutotestInsignConfNames'] = $arrayFailingSignAutotestInsignConfNames;
        $_SESSION['arrayFailingSignAutotestInsignConfProjects'] = $arrayFailingSignAutotestInsignConfProjects;
        $_SESSION['arrayFailingInsignAutotestBlockingConfCount'] = $arrayFailingInsignAutotestBlockingConfCount;
        $_SESSION['arrayFailingInsignAutotestBlockingConfCountTotal'] = $arrayFailingInsignAutotestBlockingConfCountTotal;
        $_SESSION['arrayFailingInsignAutotestBlockingConfNames'] = $arrayFailingInsignAutotestBlockingConfNames;
        $_SESSION['arrayFailingInsignAutotestBlockingConfProjects'] = $arrayFailingInsignAutotestBlockingConfProjects;
        $_SESSION['arrayFailingInsignAutotestInsignConfCount'] = $arrayFailingInsignAutotestInsignConfCount;
        $_SESSION['arrayFailingInsignAutotestInsignConfCountTotal'] = $arrayFailingInsignAutotestInsignConfCountTotal;
        $_SESSION['arrayFailingInsignAutotestInsignConfNames'] = $arrayFailingInsignAutotestInsignConfNames;
        $_SESSION['arrayFailingInsignAutotestInsignConfProjects'] = $arrayFailingInsignAutotestInsignConfProjects;

        if ($useMysqli) {
            mysqli_free_result($result);
        }
        $timeLatestEnd = microtime(true);

        /* Step 2: Read detailed Autotest result data with possible timescale filtering (ONLY ON SECOND ROUND) */
        $timeAllStart = microtime(true);
        $arrayFailingAutotestFailedBuilds = array();
        $arrayFailingAutotestAllBuilds = array();
        $arrayFailingAutotestRerunBuilds = array();
        $arrayFailingAutotestFailedPercentage = array();

        /* Step 2.1: Read all autotests for all Projects (Project not filtered) */
        if ($project == "All") {
            $booPrintDetailedResultsTitle = TRUE;                                       // Titles printed always
            $booPrintDetailedResultsData = FALSE;
            if ($timescaleValue == $_SESSION['maxBuildDate'] AND isset($_SESSION['previousTimescaleValue']))
                unset($_SESSION['previousTimescaleValue']);                             // Clear the session variable if using the default date (to detect the need to load from database when setting timescale to Since)
            if ($round == 2)
                $booPrintDetailedResultsData = TRUE;                                    // Data printed only on 2nd round
            if ($booPrintDetailedResultsData) {
                $timeAllDbStart = microtime(true);

                /* Performance optimization: Check when the test results need to be (re)loaded from the database (which takes time) */
                if (isset($_SESSION['previousCiProject']))
                    $previousCiProject = $_SESSION['previousCiProject'];
                else
                    $previousCiProject = "NA";
                if (isset($_SESSION['previousCiBranch']))
                    $previousCiBranch = $_SESSION['previousCiBranch'];
                else
                    $previousCiBranch = "NA";
                if (isset($_SESSION['previousCiPlatform']))
                    $previousCiPlatform = $_SESSION['previousCiPlatform'];
                else
                    $previousCiPlatform = "NA";
                if (isset($_SESSION['previousConfiguration']))
                    $previousConfiguration = $_SESSION['previousConfiguration'];
                else
                    $previousConfiguration = "NA";
                if (isset($_SESSION['previousTimescaleType']))
                    $previousTimescaleType = $_SESSION['previousTimescaleType'];
                else
                    $previousTimescaleType = "NA";
                if (isset($_SESSION['previousTimescaleValue']))
                    $previousTimescaleValue = $_SESSION['previousTimescaleValue'];
                else
                    $previousTimescaleValue = "NA";
                $booReloadTestResultsAll = FALSE;
                $booReloadTestResultsFiltered = FALSE;
                $projectFilter = "";
                $confFilter = "";
                $timescaleFilter = "";
                $where = "";
                if ($timescaleType == "All")
                    $from = "all_test_latest";
                else
                    $from = "all_test";
                if ($ciProject == "All" AND $ciBranch == "All" AND $ciPlatform == 0 AND
                    $conf == "All" AND $timescaleType == "All") {                       // If no filters selected (only either of the $booReloadTestResultsAll/Filtered can be TRUE at a time)
                    if (!isset($_SESSION['arrayFailingAutotestFailedBuildsAll'])) {     // All data loaded only once per session
                        $booReloadTestResultsAll = TRUE;
                    }
                } else {
                    if ($ciProject <> $previousCiProject OR
                        $ciBranch <> $previousCiBranch OR
                        $ciPlatform <> $previousCiPlatform OR
                        $conf <> $previousConfiguration OR
                        $timescaleType <> $previousTimescaleType OR
                        $timescaleValue <> $previousTimescaleValue) {                   // Filtered data loaded when Project name or branch or Configuration or Timescale changed
                        $booReloadTestResultsFiltered = TRUE;
                        $where = "WHERE ";
                        if ($timescaleType <> "All")
                            $timescaleFilter = " AND timestamp>=\"$timescaleValue\"";
                        if ($ciProject <> "All")                                        // Filter with Project name (starting with it)
                            $projectFilter = 'project LIKE "' . $ciProject . '_%"';
                        if ($ciBranch <> "All")                                         // Filter with Project branch (in the middle)
                            $projectFilter = 'project LIKE "%_' . $ciBranch . '_%"';
                        if ($ciProject <> "All" AND $ciBranch <> "All")                 // Filter with Project name and branch (starting with it)
                            $projectFilter = 'project LIKE "' . $ciProject . '_' . $ciBranch . '_%"';
                        if ($ciPlatform <> 0)                                           // Filter with Platform
                            $confFilter = 'AND cfg LIKE "' . $ciPlatformFilterSql . '"';
                        if ($conf <> "All")                                             // Filter with Conf (overwrite possible Platform filter)
                            $confFilter = "AND cfg=\"$conf\"";
                    }
                }

                /* Get total and failed build counts for each autotest (only one of all/configuration/timescale loading done at a time) */
                if ($showAll == "show") {                                               // If selected to show the data (performance optimization)
                    if ($booReloadTestResultsAll OR $booReloadTestResultsFiltered) {    // Load data from the database ...
                        $sql = cleanSqlString(
                               "SELECT name, passed, failed, skipped, runs
                                FROM $from
                                $where $projectFilter $confFilter $timescaleFilter");   // Read all data and save to session variables for faster filtering after initial loading
                        $dbColumnTestName = 0;
                        $dbColumnTestPassed = 1;
                        $dbColumnTestFailed = 2;
                        $dbColumnTestSkipped = 3;
                        $dbColumnTestRuns = 4;
                        if ($useMysqli) {
                            $result2 = mysqli_query($conn, $sql);
                            $numberOfRows2 = mysqli_num_rows($result2);
                        } else {
                            $result2 = mysql_query($sql) or die (mysql_error());
                            $numberOfRows2 = mysql_num_rows($result2);
                        }
                        for ($j=0; $j<$numberOfRows2; $j++) {
                            if ($useMysqli)
                                $resultRow2 = mysqli_fetch_row($result2);
                            else
                                $resultRow2 = mysql_fetch_row($result2);
                            for ($k=0; $k<$autotestCount; $k++) {                       // Loop all the available Autotests to collect data per one autotest
                                if ($arrayFailingAutotestNames[$k] == $resultRow2[$dbColumnTestName]) {
                                    $arrayFailingAutotestAllBuilds[$k]++;               // a) Count the number of Autotests
                                    if (checkAutotestFailed($resultRow2[$dbColumnTestPassed],
                                                            $resultRow2[$dbColumnTestFailed],
                                                            $resultRow2[$dbColumnTestSkipped]))  // b) Count the number of failed Autotests in a Project (identified by case results)
                                        $arrayFailingAutotestFailedBuilds[$k]++;
                                    if ($resultRow2[$dbColumnTestRuns] > 1)             // c) Count the number of rerun Autotests (not the number of reruns)
                                        $arrayFailingAutotestRerunBuilds[$k]++;
                                }
                            }
                        }
                        if ($useMysqli)
                            mysqli_free_result($result2);
                    } else {                                                            // ... otherwise use the session variables
                        if ($conf == "All" AND $timescaleType == "All") {               // Use the all data when no filters selected
                            $arrayFailingAutotestFailedBuilds = $_SESSION['arrayFailingAutotestFailedBuildsAll'];
                            $arrayFailingAutotestAllBuilds = $_SESSION['arrayFailingAutotestAllBuildsAll'];
                            $arrayFailingAutotestRerunBuilds = $_SESSION['arrayFailingAutotestRerunBuildsAll'];
                        } else {                                                        // Use the filtered data when a filter used
                            $arrayFailingAutotestFailedBuilds = $_SESSION['arrayFailingAutotestFailedBuildsFiltered'];
                            $arrayFailingAutotestAllBuilds = $_SESSION['arrayFailingAutotestAllBuildsFiltered'];
                            $arrayFailingAutotestRerunBuilds = $_SESSION['arrayFailingAutotestRerunBuildsFiltered'];
                        }
                    }
                }

                /* Save the calculated data for for returning from Level 2 to Level 1 (so that it would not be needed to read the data from database again) */
                if ($showAll == "show") {                                               // Save session variables only if selected to show the data
                    if ($booReloadTestResultsAll) {
                        $_SESSION['arrayFailingAutotestFailedBuildsAll'] = $arrayFailingAutotestFailedBuilds;
                        $_SESSION['arrayFailingAutotestAllBuildsAll'] = $arrayFailingAutotestAllBuilds;
                        $_SESSION['arrayFailingAutotestRerunBuildsAll'] = $arrayFailingAutotestRerunBuilds;
                    }
                    if ($booReloadTestResultsFiltered) {
                        $_SESSION['arrayFailingAutotestFailedBuildsFiltered'] = $arrayFailingAutotestFailedBuilds;
                        $_SESSION['arrayFailingAutotestAllBuildsFiltered'] = $arrayFailingAutotestAllBuilds;
                        $_SESSION['arrayFailingAutotestRerunBuildsFiltered'] = $arrayFailingAutotestRerunBuilds;
                    }
                    $_SESSION['previousCiProject'] = $ciProject;
                    $_SESSION['previousCiBranch'] = $ciBranch;
                    $_SESSION['previousCiPlatform'] = $ciPlatform;
                    $_SESSION['previousConfiguration'] = $conf;
                    $_SESSION['previousTimescaleType'] = $timescaleType;
                    $_SESSION['previousTimescaleValue'] = $timescaleValue;
                }

                $timeAllDbEnd = microtime(true);
            } // endif $booPrintDetailedResultsData

        /* Step 2.2: Read autotests for a filtered Project */
        } else {
            $booPrintDetailedResultsTitle = TRUE;                                   // Titles printed always
            $booPrintDetailedResultsData = FALSE;
            if (isset($_SESSION['previousProject']) AND $project == "All")
                unset($_SESSION['previousProject']);                                // Clear the session variable if Project filter cleared
            if ($round == 2)
                $booPrintDetailedResultsData = TRUE;                                // Data printed only on 2nd round
            if ($booPrintDetailedResultsData) {
                $timeAllDbStart = microtime(true);

                /* Get the first available build number or the first after the selected time scale */
                $minBuildNumberInDatabase = MAXCIBUILDNUMBER;
                $projectFilter = "project=\"$project\"";                            // Project is filtered here
                $timescaleFilter = "";
                if ($timescaleType <> "All")
                        $timescaleFilter = "AND timestamp>=\"$timescaleValue\"";
                $sql = cleanSqlString(
                       "SELECT MIN(build_number)
                        FROM ci
                        WHERE $projectFilter $timescaleFilter");
                $dbColumnCiBuildNumber = 0;
                if ($useMysqli) {
                    $result2 = mysqli_query($conn, $sql);
                    $resultRow2 = mysqli_fetch_row($result2);
                } else {
                    $result2 = mysql_query($sql) or die (mysql_error());
                    $resultRow2 = mysql_fetch_row($result2);
                }
                if ($resultRow2[$dbColumnCiBuildNumber] <> NULL AND $resultRow2[$dbColumnCiBuildNumber] <> "")
                    $minBuildNumberInDatabase = $resultRow2[$dbColumnCiBuildNumber];
                if ($useMysqli)
                    mysqli_free_result($result2);
                $_SESSION['minBuildNumberInDatabase'] = $minBuildNumberInDatabase;  // Save for level 2

                /* Get the first build with detailed test result data */
                $projectFilter = "";                                                // NOTE: The same filters are used also on the step below (therefore calculated here for any filter selections)
                $buildFilter = "";
                $confFilter = "";
                $from = "all_test_latest";
                $projectFilter = "project=\"$project\"";                            // Project is filtered here
                if ($ciPlatform <> 0)                                               // Filter with Platform
                    $confFilter = 'AND cfg LIKE "' . $ciPlatformFilterSql . '"';
                if ($conf <> "All")                                                 // Filter with Conf (overwrite possible Platform filter)
                    $confFilter = "AND cfg=\"$conf\"";
                if ($timescaleType == "All") {                                      // If timescale not filtered read only the latest/selected build
                    $buildFilter = "AND build_number = $buildNumber";
                    if ($build > 0)                                                 // If other than the latest build
                        $from = "all_test";
                } else {                                                            // If timescale filtered read all the available builds since the date
                    $from = "all_test";
                    $buildFilter = "AND build_number >= $minBuildNumberInDatabase";
                }
                $sql = cleanSqlString(
                       "SELECT MIN(build_number)
                        FROM $from
                        WHERE $projectFilter $buildFilter $confFilter");
                $dbColumnTestBuildNumber = 0;
                if ($useMysqli) {
                    $result2 = mysqli_query($conn, $sql);
                    $resultRow2 = mysqli_fetch_row($result2);
                } else {
                    $result2 = mysql_query($sql) or die (mysql_error());
                    $numberOfRows2 = mysql_num_rows($result2);
                    $resultRow2 = mysql_fetch_row($result2);
                }
                if ($resultRow2[$dbColumnTestBuildNumber] <> NULL AND $resultRow2[$dbColumnTestBuildNumber] <> "")
                    $minBuildNumberWithTestResults = $resultRow2[$dbColumnTestBuildNumber]; // Replace the default value set earlier
                if ($useMysqli)
                    mysqli_free_result($result2);

                /* Get total and failed build counts for each autotest (using the same filters as above) */
                if (isset($_SESSION['previousProject'])) {
                    $previousCiProject = $_SESSION['previousCiProject'];
                    $previousCiBranch = $_SESSION['previousCiBranch'];
                    $previousCiPlatform = $_SESSION['previousCiPlatform'];
                    $previousProject = $_SESSION['previousProject'];
                    $previousConfiguration = $_SESSION['previousConfiguration'];
                    $previousBuild = $_SESSION['previousBuild'];
                    $previousTimescaleType = $_SESSION['previousTimescaleType'];
                    $previousTimescaleValue = $_SESSION['previousTimescaleValue'];
                } else {
                    $previousCiProject = "NA";
                    $previousCiBranch = "NA";
                    $previousCiPlatform = "NA";
                    $previousProject = "NA";
                    $previousConfiguration = "NA";
                    $previousBuild = "NA";
                    $previousTimescaleType = "NA";
                    $previousTimescaleValue = "NA";
                }
                $booReloadTestResults = TRUE;                                       // Performance optimization: Check when the test results need to be (re)loaded from the zip files (which takes time)
                if ($ciProject == $previousCiProject AND
                    $ciBranch == $previousCiBranch AND
                    $ciPlatform == $previousCiPlatform AND
                    $project == $previousProject AND
                    $conf == $previousConfiguration AND
                    $timescaleType == $previousTimescaleType AND
                    $timescaleValue == $previousTimescaleValue)
                    $booReloadTestResults = FALSE;                                  // No need to reload the test results if project and other filters not changed
                if ($timescaleType == "All" AND $build <> $previousBuild)
                    $booReloadTestResults = TRUE;                                   // Reload if build changed when timescale not filtered (then the test results are shown for selected build)
                if (!$booReloadTestResults) {                                       // Use the session variables ...
                    $arrayFailingAutotestFailedBuilds = $_SESSION['arrayFailingAutotestFailedBuilds'];
                    $arrayFailingAutotestAllBuilds = $_SESSION['arrayFailingAutotestAllBuilds'];
                    $arrayFailingAutotestRerunBuilds = $_SESSION['arrayFailingAutotestRerunBuilds'];
                    $minBuildNumberWithTestResults = $_SESSION['minBuildNumberWithTestResults'];
                } else {                                                            // ... otherwise read the data from the database
                    $sql = cleanSqlString(
                           "SELECT name, passed, failed, skipped, runs
                            FROM $from
                            WHERE $projectFilter $buildFilter $confFilter");        // Use the same filters as above
                    $dbColumnTestName = 0;
                    $dbColumnTestPassed = 1;
                    $dbColumnTestFailed = 2;
                    $dbColumnTestSkipped = 3;
                    $dbColumnTestRuns = 4;
                    if ($useMysqli) {
                        $result2 = mysqli_query($conn, $sql);
                        $numberOfRows2 = mysqli_num_rows($result2);
                    } else {
                        $result2 = mysql_query($sql) or die (mysql_error());
                        $numberOfRows2 = mysql_num_rows($result2);
                    }
                    for ($j=0; $j<$numberOfRows2; $j++) {
                        if ($useMysqli)
                            $resultRow2 = mysqli_fetch_row($result2);
                        else
                            $resultRow2 = mysql_fetch_row($result2);
                        for ($k=0; $k<$autotestCount; $k++) {                       // Loop all the available Autotests to collect data per one autotest
                            if ($arrayFailingAutotestNames[$k] == $resultRow2[$dbColumnTestName]) {
                                $arrayFailingAutotestAllBuilds[$k]++;               // Count the number of Autotests
                                if (checkAutotestFailed($resultRow2[$dbColumnTestPassed],
                                                        $resultRow2[$dbColumnTestFailed],
                                                        $resultRow2[$dbColumnTestSkipped]))  // b) Count the number of failed Autotests in a Project (identified by case results)
                                    $arrayFailingAutotestFailedBuilds[$k]++;
                                if ($resultRow2[$dbColumnTestRuns] > 1)             // Count the number of rerun Autotests (not the number of reruns)
                                    $arrayFailingAutotestRerunBuilds[$k]++;
                            }
                        }
                    }
                    if ($useMysqli)
                        mysqli_free_result($result2);
                }

                /* Save the calculated data for level 2 and for returning from Level 2 to Level 1 (so that it would not be needed to read the data from database again) */
                $_SESSION['arrayFailingAutotestFailedBuilds'] = $arrayFailingAutotestFailedBuilds;
                $_SESSION['arrayFailingAutotestAllBuilds'] = $arrayFailingAutotestAllBuilds;
                $_SESSION['arrayFailingAutotestRerunBuilds'] = $arrayFailingAutotestRerunBuilds;
                $_SESSION['minBuildNumberWithTestResults'] = $minBuildNumberWithTestResults;
                $_SESSION['previousCiProject'] = $ciProject;
                $_SESSION['previousCiBranch'] = $ciBranch;
                $_SESSION['previousCiPlatform'] = $ciPlatform;
                $_SESSION['previousProject'] = $project;
                $_SESSION['previousConfiguration'] = $conf;
                $_SESSION['previousBuild'] = $build;
                $_SESSION['previousTimescaleType'] = $timescaleType;
                $_SESSION['previousTimescaleValue'] = $timescaleValue;

                $timeAllDbEnd = microtime(true);
            } // endif $booPrintDetailedResultsData

            /* Read the timestamp of the latest/selected build and the first build with detailed test result data */
            for ($k=0; $k<=1; $k++) {                                               // Run twice: 0 = latest/selected build, 1 = first build with detailed test result data
                $projectFilter = "project = \"$project\"";                          // Project is filtered here
                if ($k == 0) {
                    if ($build == 0) {                                              // Show the latest build ...
                        $from = "ci_latest";
                        $buildFilter = "";
                    } else {                                                        // ... or the selected build
                        $from = "ci";
                        $buildFilter = "AND build_number = $buildNumber";
                    }
                } else {
                    $from = "ci";
                    $buildFilter = "AND build_number = $minBuildNumberWithTestResults";
                }
                $sql = cleanSqlString(
                       "SELECT MIN(timestamp)
                        FROM $from
                        WHERE $projectFilter $buildFilter");
                $dbColumnCiTimestamp = 0;
                if ($useMysqli) {
                    $result2 = mysqli_query($conn, $sql);
                    $numberOfRows2 = mysqli_num_rows($result2);
                } else {
                    $result2 = mysql_query($sql) or die (mysql_error());
                    $numberOfRows2 = mysql_num_rows($result2);
                }
                if ($useMysqli)
                    $resultRow2 = mysqli_fetch_row($result2);
                else
                    $resultRow2 = mysql_fetch_row($result2);
                if ($k == 0)
                    $buildTimestamp = $resultRow2[$dbColumnCiTimestamp];
                else
                    $minBuildNumberWithTestResultsTimestamp = $resultRow2[$dbColumnCiTimestamp];
                if ($useMysqli)
                    mysqli_free_result($result2);
            }

        } // End of step 2.2
        $timeAllEnd = microtime(true);

        /* Calculate the failure percentage */
        for ($k=0; $k<$autotestCount; $k++)
            $arrayFailingAutotestFailedPercentage[$k]
                = calculatePercentage($arrayFailingAutotestFailedBuilds[$k], $arrayFailingAutotestAllBuilds[$k]);   // Must be rounded to integer for sorting to work

        /* Print the used filters */
        if ($project <> "All" OR $ciProject <> "All" OR $ciBranch <> "All" OR $ciPlatform <> 0 OR
            $conf <> "All" OR $timescaleType <> "All") {
            echo '<table>';
            if ($project <> "All") {
                echo '<tr><td>Project:</td><td class="tableCellBackgroundTitle">' . $project . '</td></tr>';
            } else {
                if ($ciProject <> "All")
                    echo '<tr><td>Project:</td><td class="tableCellBackgroundTitle">' . $ciProject . '</td></tr>';
                if ($ciBranch <> "All")
                    echo '<tr><td>Branch:</td><td class="tableCellBackgroundTitle">' . $ciBranch . '</td></tr>';
            }
            if ($ciPlatform <> 0 AND $conf == "All") {
                echo '<tr><td>Platform:</td><td class="tableCellBackgroundTitle">' . $ciPlatformName . '</td></tr>';
                echo '<tr><td>Configuration:</td><td class="tableCellBackgroundTitle fontColorGrey">' . $ciPlatformFilter . '</td></tr>';
            }
            if ($conf <> "All")
                echo '<tr><td>Configuration:</td><td class="tableCellBackgroundTitle">' . $conf . '</td></tr>';
            if ($timescaleType == "Since")
                echo '<tr><td>Since:</td><td class="timescaleSince">' . $timescaleValue . '</td></tr>';
            if ($project <> "All")
                echo '<tr><td>Build:</td><td>' . $buildNumber . ' ('
                    . substr($buildTimestamp, 0, strpos($buildTimestamp, " ")) . ')</td></tr>';
            if ($booPrintDetailedResultsData) {
                if ($minBuildNumberWithTestResults == MAXCIBUILDNUMBER) {
                    $testResultBuilds = '(not any test result files available)';
                    $testResultBuildsSeeMore = '';
                } else {
                    $testResultBuilds = $minBuildNumberWithTestResults . ' ('
                        . substr($minBuildNumberWithTestResultsTimestamp, 0, strpos($minBuildNumberWithTestResultsTimestamp, " ")) . ')';
                    if ($timescaleType == "Since")
                        $testResultBuilds = $testResultBuilds . ' onwards';
                    $testResultBuildsSeeMore = setSeeMoreNote($timescaleType, $timescaleValue);
                }
                if ($project <> "All")
                    echo '<tr><td>Test Results:</td><td>' . $testResultBuilds . $testResultBuildsSeeMore . '</td></tr>';
            }
            echo '</table>';
        }
        echo '<div class="metricsTitle">';
        echo '<b>Failed Autotests</b>';
        echo '</div>';

        /* Set the default sorting */
        if ($booPrintDetailedResultsData AND ($showAll == "show" OR $project <> "All")) {       // Sort by Failed % on level 1 if results printed or always on level 2
            if ($sortBy == AUTOTESTSORTBYNOTSET) {
                if ($minBuildNumberWithTestResults == MAXCIBUILDNUMBER)
                    $sortBy = AUTOTESTSORTBYSIGNAUTOTESTBLOCKINGCONF;
                else
                    $sortBy = AUTOTESTSORTBYAUTOTESTFAILEDPERCENTAGE;                           // Sorting based the Failed % when all builds data available and shown
            }
        } else {
            if ($sortBy == AUTOTESTSORTBYNOTSET)
                $sortBy = AUTOTESTSORTBYSIGNAUTOTESTBLOCKINGCONF;                               // Default sorting is the significant blocking when only the latest/selected build shown
        }

        /* Print the titles */
        echo '<table class="fontSmall">';
        echo '<tr>';                                                                            // First row
        echo '<th></th>';
        if ($timescaleType == "All" AND CITESTRESULTBUILDCOUNT == 1) {                          // Timescale not filtered (and the dashboard is configured to show just the latest build)
            if ($booPrintDetailedResultsTitle)
                echo '<td colspan="8" ';
            else
                echo '<td colspan="4" ';
            if ($project == "All") {                                                            // When Project not filtered the list shows latest builds from any date
                echo 'class="tableBottomBorder tableSideBorder tableCellCentered">';
                echo '<b>LATEST PROJECT BUILDS</b></td>';
            } else {                                                                            // When Project filtered the list shows latest/selected build only
                echo 'class="tableBottomBorder tableSideBorder tableCellCentered tableCellBuildSelected">';
                if ($build == 0)
                    echo 'LATEST BUILD</td>';
                else
                    echo 'BUILD ' . $buildNumber . '</td>';
            }
        }
        if ($timescaleType == "All" AND CITESTRESULTBUILDCOUNT > 1) {                           // Timescale not filtered (and the dashboard is configured to show n latest builds)
            echo '<td colspan="4" ';
            if ($project == "All") {                                                            // When Project not filtered the list shows latest builds from any date
                echo 'class="tableBottomBorder tableSideBorder tableCellCentered">';
                echo '<b>LATEST PROJECT BUILDS</b></td>';
            } else {                                                                            // When Project filtered the list shows the latest/selected build only
                echo 'class="tableBottomBorder tableSideBorder tableCellCentered tableCellBuildSelected">';
                if ($build == 0)
                    echo 'LATEST BUILD</td>';
                else
                    echo 'BUILD ' . $buildNumber . '</td>';
            }
            if ($booPrintDetailedResultsTitle) {
                echo '<td colspan="4" class="tableBottomBorder tableSideBorder tableCellCentered timescaleAll">';
                echo '<b>ALL BUILDS</b></td>';
            }
        }
        if ($timescaleType == "Since") {                                                        // Timescale filtered
            echo '<td colspan="4" ';
            if ($project == "All") {                                                            // When Project not filtered the list shows latest builds since selected date
                echo 'class="tableBottomBorder tableSideBorder tableCellCentered">';
                echo '<b>LATEST PROJECT BUILDS SINCE ' . $timescaleValue . '</b></td>';
            } else {                                                                            // When Project filtered the list shows the latest/selected build only
                echo 'class="tableBottomBorder tableSideBorder tableCellCentered tableCellBuildSelected">';
                if ($build == 0)
                    echo 'LATEST BUILD</td>';
                else
                    echo 'BUILD ' . $buildNumber . '</td>';
            }
            if ($booPrintDetailedResultsTitle) {
                echo '<td colspan="4" class="tableBottomBorder tableSideBorder tableCellCentered timescaleSince">';
                echo 'ALL BUILDS SINCE ' . $timescaleValue . '</td>';
            }
        }
        echo '</tr>';
        echo '<tr>';                                                                            // Second row
        echo '<th></th>';
        echo '<th colspan="2" class="tableBottomBorder tableSideBorder">Failed Significant Autotests</th>';
        echo '<th colspan="2" class="tableBottomBorder tableSideBorder">Failed Insignificant Autotests</th>';
        if ($booPrintDetailedResultsTitle) {
            if ($round == 1) {
                $xmlBuildInfo = 'Detailed Test Results <span class="loading"><span>.</span><span>.</span><span>.</span></span>';
            } else {
                if ($minBuildNumberWithTestResults == MAXCIBUILDNUMBER) {         // No builds found with all test results
                    $xmlBuildInfo = "(not any Builds with test result files)";
                } else {
                    if ($project == "All") {
                        $showAllLink = '<a href="javascript:void(0);" onclick="toggleAutotestShowAll(\'' . $showAll . '\')" ';
                        if ($showAll == "show") {                                 // To select if the all data is shown or not
                            $showAllLink = $showAllLink . 'title="Hide displaying the all builds data when project is not filtered. &#10;This may be practical for faster display updates when using the &#10;timescale filter.">hide</a> ';
                            $booShowAll = TRUE;
                        } else {
                            $showAllLink = $showAllLink . 'title="Show the all builds data when project is not filtered. &#10;Note: This may slow down the display updates when &#10;using the timescale filter.">show</a> ';
                            $booShowAll = FALSE;
                        }
                    } else {
                        $showAllLink = '';
                        $booShowAll = TRUE;
                    }
                    if ($timescaleType == "All" AND CITESTRESULTBUILDCOUNT == 1)
                        $xmlBuildInfo = "Detailed Test Results";
                    else
                        if ($project == "All")
                            $xmlBuildInfo = "Detailed Test Results";
                        else
                            $xmlBuildInfo = "Detailed Test Results since Build $minBuildNumberWithTestResults";
                }
            }
            echo '<th colspan="4" class="tableBottomBorder tableSideBorder">' . $showAllLink . $xmlBuildInfo . '</th>';
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
        if ($booPrintDetailedResultsTitle) {
            echo '<td class="tableBottomBorder tableLeftBorder tableCellCentered">Builds where<br>failed</td>';
            echo '<td class="tableBottomBorder tableCellCentered">Builds where<br>run</td>';
            echo '<td class="sortField tableBottomBorder tableCellCentered">';
            if ($sortBy == AUTOTESTSORTBYAUTOTESTFAILEDPERCENTAGE)
                echo 'Failed %<br>&diams;';                                       // Identify selected sorting
            else
                echo '<a href="javascript:void(0);" onclick="filterAutotest(\'All\',' . AUTOTESTSORTBYAUTOTESTFAILEDPERCENTAGE . ')">
                      Failed %<br><img src="images/sort-descending.png" alt="Sort" title="sort descending"></a>';
            echo '</td>';
            echo '<td class="tableBottomBorder tableRightBorder tableCellCentered">Builds where<br>rerun (flaky)</td>';
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
        $arrayFailingAutotestRerunBuildsSum = 0;
        $arrayFailingAutotestFailedPercentageSum = 0;
        if ($maxCount < 100)
            $maxCount = 100;                                                            // Loop at least the percentage scale
        for ($countOrder=$maxCount; $countOrder>=0; $countOrder--) {                    // Sort the list by looping from the highest count
            for ($i=0; $i<$autotestCount; $i++) {                                       // Loop the Autotests
                switch ($sortBy) {                                                      // Check the next value to print in sorting
                    case AUTOTESTSORTBYSIGNAUTOTESTBLOCKINGCONF:
                        $sortFieldValue = $arrayFailingSignAutotestBlockingConfCountTotal[$i];
                        break;
                    case AUTOTESTSORTBYSIGNAUTOTESTINSIGNCONF:
                        $sortFieldValue = $arrayFailingSignAutotestInsignConfCountTotal[$i];
                        break;
                    case AUTOTESTSORTBYINSIGNAUTOTESTBLOCKINGCONF:
                        $sortFieldValue = $arrayFailingInsignAutotestBlockingConfCountTotal[$i];
                        break;
                    case AUTOTESTSORTBYINSIGNAUTOTESTINSIGNGCONF:
                        $sortFieldValue = $arrayFailingInsignAutotestInsignConfCountTotal[$i];
                        break;
                    case AUTOTESTSORTBYAUTOTESTFAILEDPERCENTAGE:
                        $sortFieldValue = $arrayFailingAutotestFailedPercentage[$i];
                        break;
                }
                if ($sortFieldValue == $countOrder) {                                   // Print the ones that are next in the sorting order
                    if ($arrayFailingSignAutotestBlockingConfCountTotal[$i]
                        + $arrayFailingSignAutotestInsignConfCountTotal[$i]
                        + $arrayFailingInsignAutotestBlockingConfCountTotal[$i]
                        + $arrayFailingInsignAutotestInsignConfCountTotal[$i]
                        + $arrayFailingAutotestFailedBuilds[$i] > 0) {                  // Don't print if not any failures in Latest/All Builds
                        if ($k % 2 == 0)
                            echo '<tr>';
                        else
                            echo '<tr class="tableBackgroundColored">';

                        /* Autotest name */
                        echo '<td><a href="javascript:void(0);" onclick="filterAutotest(\'' . $arrayFailingAutotestNames[$i]
                            . '\')">' . $arrayFailingAutotestNames[$i] . '</a></td>';

                        /* Latest/Selected Build: Significant Autotests in blocking Configuration (with names as a popup) */
                        if ($arrayFailingSignAutotestBlockingConfCountTotal[$i] > 0) {
                            echo '<td class="tableLeftBorder tableCellCentered fontColorRed"><span class="popupMessage">'
                                . $arrayFailingSignAutotestBlockingConfCountTotal[$i]
                                . '<span><b>' . $arrayFailingAutotestNames[$i] . ':</b><br>'
                                . substr($arrayFailingSignAutotestBlockingConfNames[$i],strlen('<br>'))
                                . '</span></span></td>';                                // Skip leading '<br>' set above
                        } else {
                            echo '<td class="tableLeftBorder tableCellCentered">-</td>';
                        }

                        /* Latest/Selected Build: Significant Autotests in insignificant Configuration (with names as a popup) */
                        if ($arrayFailingSignAutotestInsignConfCountTotal[$i] > 0)
                            echo '<td class="tableCellCentered"><span class="popupMessage">'
                                . $arrayFailingSignAutotestInsignConfCountTotal[$i]
                                . '<span><b>' . $arrayFailingAutotestNames[$i] . ':</b><br>'
                                . substr($arrayFailingSignAutotestInsignConfNames[$i],strlen('<br>'))
                                . '</span></span></td>';                                // Skip leading '<br>' set above
                        else
                            echo '<td class="tableCellCentered">-</td>';

                        /* Latest/Selected Build: Insignificant Autotests in blocking Configuration (with names as a popup) */
                        if ($arrayFailingInsignAutotestBlockingConfCountTotal[$i] > 0)
                            echo '<td class="tableLeftBorder tableCellCentered"><span class="popupMessage">'
                                . $arrayFailingInsignAutotestBlockingConfCountTotal[$i]
                                . '<span><b>' . $arrayFailingAutotestNames[$i] . ':</b><br>'
                                . substr($arrayFailingInsignAutotestBlockingConfNames[$i],strlen('<br>'))
                                . '</span></span></td>';                                // Skip leading '<br>' set above
                        else
                            echo '<td class="tableLeftBorder tableCellCentered">-</td>';

                        /* Latest/Selected Build: Insignificant Autotests in insignificant Configuration (with names as a popup) */
                        if ($arrayFailingInsignAutotestInsignConfCountTotal[$i] > 0)
                            echo '<td class="tableRightBorder tableCellCentered"><span class="popupMessage">'
                                . $arrayFailingInsignAutotestInsignConfCountTotal[$i]
                                . '<span><b>' . $arrayFailingAutotestNames[$i] . ':</b><br>'
                                . substr($arrayFailingInsignAutotestInsignConfNames[$i],strlen('<br>'))
                                . '</span></span></td>';                                // Skip leading '<br>' set above
                        else
                            echo '<td class="tableRightBorder tableCellCentered">-</td>';

                        /* Detailed test results */
                        if ($booPrintDetailedResultsTitle) {
                            if ($booPrintDetailedResultsData AND $booShowAll) {

                                /* Detailed test results: Builds where failed */
                                if ($arrayFailingAutotestFailedBuilds[$i] > 0)
                                    echo '<td class="tableLeftBorder tableCellCentered">'
                                        . $arrayFailingAutotestFailedBuilds[$i] . '</td>';
                                else
                                    echo '<td class="tableLeftBorder tableCellCentered">-</td>';

                                /* Detailed test results: Builds where run (all) */
                                if ($arrayFailingAutotestAllBuilds[$i] > 0)
                                    echo '<td class="tableCellCentered">'
                                        . $arrayFailingAutotestAllBuilds[$i] . '</td>';
                                else
                                    if ($booTestResultDirectory)
                                        echo '<td class="tableCellCentered">-</td>';
                                    else
                                        echo '<td class="tableCellCentered">(n/a)</td>';

                                /* Detailed test results: Failed % */
                                if ($arrayFailingAutotestFailedPercentage[$i] > 0) {
                                    if ($arrayFailingAutotestFailedPercentage[$i] >= AUTOTESTFAILUREWARNINGLEVEL)
                                        echo '<td class="tableCellCentered fontColorRed">';
                                    else
                                        echo '<td class="tableCellCentered">';
                                    echo $arrayFailingAutotestFailedPercentage[$i] . '%</td>';
                                } else {
                                    echo '<td class="tableCellCentered">-</td>';
                                }

                                /* Detailed test results: Builds where rerun (flaky) */
                                if ($arrayFailingAutotestRerunBuilds[$i] > 0)
                                    echo '<td class="tableRightBorder tableCellCentered">'
                                        . $arrayFailingAutotestRerunBuilds[$i] . '</td>';
                                else
                                    echo '<td class="tableRightBorder tableCellCentered">-</td>';

                            } else {
                                echo '<td class="tableLeftBorder tableCellCentered"></td>';
                                echo '<td class="tableCellCentered"></td>';
                                echo '<td class="tableCellCentered"></td>';
                                echo '<td class="tableRightBorder tableCellCentered"></td>';
                            }
                        }

                        echo '</tr>';
                        $k++;

                        /* Count the totals */
                        $failingSignAutotestBlockingConfCount = $failingSignAutotestBlockingConfCount + $arrayFailingSignAutotestBlockingConfCountTotal[$i];
                        $failingSignAutotestInsignConfCount = $failingSignAutotestInsignConfCount + $arrayFailingSignAutotestInsignConfCountTotal[$i];
                        $failingInsignAutotestBlockingConfCount = $failingInsignAutotestBlockingConfCount + $arrayFailingInsignAutotestBlockingConfCountTotal[$i];
                        $failingInsignAutotestInsignConfCount = $failingInsignAutotestInsignConfCount + $arrayFailingInsignAutotestInsignConfCountTotal[$i];
                        $arrayFailingAutotestFailedBuildsSum = $arrayFailingAutotestFailedBuildsSum + $arrayFailingAutotestFailedBuilds[$i];
                        $arrayFailingAutotestAllBuildsSum = $arrayFailingAutotestAllBuildsSum + $arrayFailingAutotestAllBuilds[$i];
                        $arrayFailingAutotestRerunBuildsSum = $arrayFailingAutotestRerunBuildsSum + $arrayFailingAutotestRerunBuilds[$i];

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
            $arrayFailingAutotestFailedPercentageSum = calculatePercentage($arrayFailingAutotestFailedBuildsSum, $arrayFailingAutotestAllBuildsSum);
            echo '<tr>';
            echo '<td class="tableRightBorder tableTopBorder">total (' . $printedAutotests . ')</td>';
            echo '<td class="tableTopBorder tableCellCentered">' . $failingSignAutotestBlockingConfCount . '</td>';
            echo '<td class="tableRightBorder tableTopBorder tableCellCentered">' . $failingSignAutotestInsignConfCount . '</td>';
            echo '<td class="tableTopBorder tableCellCentered">' . $failingInsignAutotestBlockingConfCount . '</td>';
            echo '<td class="tableRightBorder tableTopBorder tableCellCentered">' . $failingInsignAutotestInsignConfCount . '</td>';
            if ($booPrintDetailedResultsTitle) {
                if ($booPrintDetailedResultsData AND $booShowAll) {
                    echo '<td class="tableTopBorder tableCellCentered">' . $arrayFailingAutotestFailedBuildsSum . '</td>';
                    echo '<td class="tableTopBorder tableCellCentered">' . $arrayFailingAutotestAllBuildsSum . '</td>';
                    echo '<td class="tableTopBorder tableCellCentered">' . $arrayFailingAutotestFailedPercentageSum . '%</td>';
                    echo '<td class="tableRightBorder tableTopBorder tableCellCentered">' . $arrayFailingAutotestRerunBuildsSum . '</td>';
                } else {
                    echo '<td class="tableTopBorder tableCellCentered"></td>';
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
        if (isset($timeAllDbEnd)) {
            $timeAll = round($timeAllEnd - $timeAllStart, 4);
            $timeAllSelect = round($timeAllDbEnd - $timeAllDbStart, 4);
            $timeAllCalculation = round($timeAll - $timeAllDb, 4);
        }
        echo "<div class=\"elapdedTime\">";
        echo "<ul><li>";
        echo "<b>Total time:</b>&nbsp $time s (round $round)<br>";
        echo "Latest builds: $timeLatest s
            (database connect time: $timeDbConnect s,
             database read time: $timeLatestSelect s,
             calculation: $timeLatestCalculation s)<br>";
        if (isset($timeAllDbEnd))
            echo "All builds:&nbsp&nbsp&nbsp&nbsp&nbsp $timeAll s
            (database connect time: $timeDbConnect s,
             database read time: $timeAllSelect s,
             calculation: $timeAllCalculation s)<br>";
        echo "</li></ul>";
        echo "</div>";
    }

}

/*************************************************************/
/* NESTED LEVEL 2: Autotest filtered                         */
/*************************************************************/

if ($autotest <> "All") {
    echo '<div class="metricsBoxHeader">';
    echo '<div class="metricsBoxHeaderIcon">';
    if ($round == 1)
        echo "<img src=\"images/ajax-loader.gif\" alt=\"loading\">&nbsp&nbsp";  // On the first round show the loading icon
    else
        echo '<a href="javascript:void(0);" class="imgLink" onclick="showMessageWindow(\'ci/msgautotestdashboardlevel2.html\')">
              <img src="images/info.png" alt="info"></a>&nbsp&nbsp';
    echo '</div>';
    echo '<div class="metricsBoxHeaderText">';
    echo '<b>AUTOTEST DASHBOARD:</b> <a href="javascript:void(0);" onclick="filterAutotest(\'All\')">Select Autotest</a> -> ' . $autotest;
    echo '</div>';
    echo '</div>';

    /* Get the data calculated on level 1 */
    $arrayFailingAutotestNames = array();
    $arrayFailingAutotestNames = $_SESSION['arrayAutotestName'];
    $arrayFailingAutotestAllBuilds = array();
    $arrayFailingAutotestAllBuilds = $_SESSION['arrayFailingAutotestAllBuilds'];
    $arrayAutotestName = array();
    $arrayAutotestName[] = $autotest;                                           // Save selected autotest into array for the readProjectTestResultDirectory function call below
    $arrayProjectBuildLatest = array();
    $arrayProjectBuildLatest = $_SESSION['arrayProjectBuildLatest'];
    $arrayFailingSignAutotestBlockingConfProjects = array();
    $arrayFailingSignAutotestInsignConfProjects = array();
    $arrayFailingInsignAutotestBlockingConfProjects = array();
    $arrayFailingInsignAutotestInsignConfProjects = array();
    $arrayFailingSignAutotestBlockingConfProjects = $_SESSION['arrayFailingSignAutotestBlockingConfProjects'];
    $arrayFailingSignAutotestInsignConfProjects = $_SESSION['arrayFailingSignAutotestInsignConfProjects'];
    $arrayFailingInsignAutotestBlockingConfProjects = $_SESSION['arrayFailingInsignAutotestBlockingConfProjects'];
    $arrayFailingInsignAutotestInsignConfProjects = $_SESSION['arrayFailingInsignAutotestInsignConfProjects'];

    if (isset($_SESSION['arrayAutotestName'])) {
        foreach ($arrayFailingAutotestNames as $key => $value) {
            /* Selected Autotest */
            if ($autotest == $value) {
                $timeAutotestHistoryStart = microtime(true);

                if ($project <> "All") {
                    /* Read the timestamp of the latest/selected build */
                    $projectFilter = "project = \"$project\"";                          // Project is filtered here
                    if ($build == 0) {                                                  // Show the latest build ...
                        $from = "ci_latest";
                        $buildFilter = "";
                    } else {                                                            // ... or the selected build
                        $from = "ci";
                        $buildFilter = "AND build_number = $buildNumber";
                    }
                    $sql = cleanSqlString(
                           "SELECT timestamp
                            FROM $from
                            WHERE $projectFilter $buildFilter");
                    $dbColumnCiTimestamp = 0;
                    if ($useMysqli) {
                        $result2 = mysqli_query($conn, $sql);
                        $resultRow2 = mysqli_fetch_row($result2);
                    } else {
                        $result2 = mysql_query($sql) or die (mysql_error());
                        $resultRow2 = mysql_fetch_row($result2);
                    }
                    $buildTimestamp = $resultRow2[$dbColumnCiTimestamp];
                    if ($useMysqli)
                        mysqli_free_result($result2);
                    /* Read the timestamp of the first build inside the selected timescale */
                    $firstTimescaleBuild = MAXCIBUILDNUMBER;
                    if ($timescaleType <> "All") {
                        $from = "ci";
                        $timescaleFilter = "AND timestamp >= \"$timescaleValue\"";
                        $sql = cleanSqlString(
                               "SELECT MIN(build_number)
                                FROM $from
                                WHERE $projectFilter $timescaleFilter");
                        $dbColumnCiBuildNumber = 0;
                        if ($useMysqli) {
                            $result2 = mysqli_query($conn, $sql);
                            $resultRow2 = mysqli_fetch_row($result2);
                        } else {
                            $result2 = mysql_query($sql) or die (mysql_error());
                            $resultRow2 = mysql_fetch_row($result2);
                        }
                        if ($resultRow2[$dbColumnCiBuildNumber] <> NULL AND $resultRow2[$dbColumnCiBuildNumber] <> "")
                            $firstTimescaleBuild = $resultRow2[$dbColumnCiBuildNumber];
                        if ($useMysqli)
                            mysqli_free_result($result2);
                    }
                }

                /* Print the used filters */
                echo '<table>';
                echo '<tr><td>Autotest:</td><td class="tableCellBackgroundTitle">' . $autotest . '</td></tr>';
                if ($project <> "All") {
                    echo '<tr><td>Project:</td><td class="tableCellBackgroundTitle">' . $project . '</td></tr>';
                } else {
                    if ($ciProject <> "All")
                        echo '<tr><td>Project:</td><td class="tableCellBackgroundTitle">' . $ciProject . '</td></tr>';
                    if ($ciBranch <> "All")
                        echo '<tr><td>Branch:</td><td class="tableCellBackgroundTitle">' . $ciBranch . '</td></tr>';
                }
                if ($ciPlatform <> 0 AND $conf == "All") {
                    echo '<tr><td>Platform:</td><td class="tableCellBackgroundTitle">' . $ciPlatformName . '</td></tr>';
                    echo '<tr><td>Configuration:</td><td class="tableCellBackgroundTitle fontColorGrey">' . $ciPlatformFilter . '</td></tr>';
                }
                if ($conf <> "All")
                    echo '<tr><td>Configuration:</td><td class="tableCellBackgroundTitle">' . $conf . '</td></tr>';
                if ($timescaleType == "Since")
                    echo '<tr><td>Since:</td><td class="timescaleSince">' . $timescaleValue . '</td></tr>';
                if ($project <> "All")
                    echo '<tr><td>Build:</td><td>' . $buildNumber . ' ('
                        . substr($buildTimestamp, 0, strpos($buildTimestamp, " ")) . ')</td></tr>';
               echo '</table>';

                /* 1. Result summary in the latest/selected Build */

                echo '<div class="metricsTitle">';
                if ($project == "All") {
                    echo '<b>Failure summary in latest Builds</b>';
                } else {
                    if ($build == 0)
                        echo '<b>Failure summary in latest Build</b>';
                    else
                        echo '<b>Failure summary in Build ' . $buildNumber . '</b>';
                }
                echo '</div>';
                echo '<table class="fontSmall">';
                echo '<tr><td colspan="2" class="tableCellBackgroundRedDark"><b>1) Significant Autotest in Blocking Configuration</b></td></tr>';
                if ($arrayFailingSignAutotestBlockingConfProjects[$key] <> "")
                    echo $arrayFailingSignAutotestBlockingConfProjects[$key];
                else
                    echo '<tr><td>-</td><td>-</td></tr>';
                echo '<tr><td colspan="2" class="tableCellBackgroundRed"><b>2) Significant Autotest in Insignificant Configuration</b></td></tr>';
                if ($arrayFailingSignAutotestInsignConfProjects[$key] <> "")
                    echo $arrayFailingSignAutotestInsignConfProjects[$key];
                else
                    echo '<tr><td>-</td><td>-</td></tr>';
                echo '<tr><td colspan="2" class="tableCellBackgroundRedLight"><b>3) Insignificant Autotest in Blocking Configuration</b></td></tr>';
                if ($arrayFailingInsignAutotestBlockingConfProjects[$key] <> "")
                    echo $arrayFailingInsignAutotestBlockingConfProjects[$key];
                else
                    echo '<tr><td>-</td><td>-</td></tr>';
                echo '<tr><td colspan="2" class="tableCellBackgroundRedLight"><b>4) Insignificant Autotest in Insignificant Configuration</b></td></tr>';
                if ($arrayFailingInsignAutotestInsignConfProjects[$key] <> "")
                    echo $arrayFailingInsignAutotestInsignConfProjects[$key];
                else
                    echo '<tr><td>-</td><td>-</td></tr>';
                echo '</table><br/>';

                /* 2. Autotest history */

                /* Read Autotest history data from the database */
                $projectFilter = "";
                if ($project <> "All") {
                    $projectFilter = "AND project=\"$project\"";
                } else {
                    if ($ciProject <> "All")                                                    // Filter with Project name (starting with it)
                        $projectFilter = 'AND project LIKE "' . $ciProject . '_%"';
                    if ($ciBranch <> "All")                                                     // Filter with Project branch (in the middle)
                        $projectFilter = 'AND project LIKE "%_' . $ciBranch . '_%"';
                    if ($ciProject <> "All" AND $ciBranch <> "All")                             // Filter with Project name and branch (starting with it)
                        $projectFilter = 'AND project LIKE "' . $ciProject . '_' . $ciBranch . '_%"';
                }
                $confFilter = "";
                if ($ciPlatform <> 0)                                                           // Filter with Platform
                    $confFilter = 'AND cfg LIKE "' . $ciPlatformFilterSql . '"';
                if ($conf <> "All")                                                             // Filter with Conf (overwrite possible Platform filter)
                    $confFilter = "AND cfg=\"$conf\"";
                $sql = cleanSqlString(
                       "SELECT name, project, build_number, cfg, passed, failed, skipped, runs, insignificant, timestamp
                        FROM all_test
                        WHERE name=\"$autotest\" $projectFilter $confFilter
                        ORDER BY project, build_number, cfg");                                  // (Note: Timescale filter not used because it is very slow; Timescale checked instead when looping the data)
                $dbColumnTestName = 0;
                $dbColumnTestProject = 1;
                $dbColumnTestBuildNumber = 2;
                $dbColumnTestCfg = 3;
                $dbColumnTestPassed = 4;
                $dbColumnTestFailed = 5;
                $dbColumnTestSkipped = 6;
                $dbColumnTestRuns = 7;
                $dbColumnTestInsignificant = 8;
                $dbColumnTestTimestamp = 9;
                if ($useMysqli) {
                    $result = mysqli_query($conn, $sql);
                    $numberOfRows = mysqli_num_rows($result);
                } else {
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
                    foreach ($arrayFailingAutotestProjectNames as $projectKey => $projectValue) {   // Find the correct Project
                        if ($projectValue == $resultRow[$dbColumnTestProject]) {
                            foreach ($_SESSION['arrayConfName'] as $confKey => $confValue) {        // Find the correct Configuration
                                if ($confValue == $resultRow[$dbColumnTestCfg]) {
                                    $arrayFailingAutotestConfNames[$confKey] = $confValue;
                                    if (checkAutotestFailed($resultRow[$dbColumnTestPassed],
                                                            $resultRow[$dbColumnTestFailed],
                                                            $resultRow[$dbColumnTestSkipped]))              // Failed Autotest identified by case results
                                        $autotestFailed = 1;
                                    else
                                        $autotestFailed = 0;
                                    $confString = ',' . $resultRow[$dbColumnTestBuildNumber]
                                        . '-' . $resultRow[$dbColumnTestInsignificant]
                                        . '-' . $autotestFailed
                                        . '-' . $resultRow[$dbColumnTestPassed]
                                        . '-' . $resultRow[$dbColumnTestFailed]
                                        . '-' . $resultRow[$dbColumnTestSkipped]
                                        . '-' . $resultRow[$dbColumnTestRuns]
                                        . '-,';                                     // Format is ",buildNumber-testInsign-testResultFailed-passed-failed-skipped-runs", where:
                                    $confStringBuildNumber = 0;                     // buildNumber
                                    $confStringInsignificant = 1;                   // testInsign = 0/1
                                    $confStringResultFailed = 2;                    // testResultFailed = 0/1
                                    $confStringPassed = 3;                          // passed = number of passed test cases
                                    $confStringFailed = 4;                          // failed = number of failed test cases
                                    $confStringSkipped = 5;                         // skipped = number of skipped test cases
                                    $confStringRuns = 6;                            // runs = number of test runs where >1 means reruns
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
                    mysqli_free_result($result);

                /* Print Autotest history data */
                echo '<div class="metricsTitle">';
                echo '<b>Result history by Project Configuration</b> in last ' . HISTORYBUILDCOUNT . ' Builds';
                echo '</div>';
                echo '<table class="fontSmall">';
                echo '<tr class="tableCellAlignLeft">';
                echo '<th></th>';
                echo '<th></th>';
                echo '<th colspan="' . HISTORYBUILDCOUNT . '" class="tableSideBorder">&nbsp;';
                if ($project <> "All") {
                    echo 'Results in Builds';
                    if ($timescaleType == "Since")
                        echo ' (since ' . $timescaleValue . ')';
                    echo ' - see <a href="javascript:void(0);" onclick="showMessageWindow(\'ci/msgautotestresultdescription.html\')">notation</a>';
                }
                echo '</th>';
                echo '</tr>';
                echo '<tr class="tableCellAlignLeft">';
                echo '<th class="tableBottomBorder">Project';
                if ($project <> "All" AND $checkedProjectCount > 0)                                 // When a project filtered and some data found
                    echo ' - <a href="javascript:void(0);" onclick="filterProjectAutotest(\'All\''
                        . ',\'' . $autotest . '\')">see all</a>';                                   // ... add link to filter all projects for this autotest
                echo '</th>';
                echo '<th class="tableBottomBorder">Configuration</th>';
                if ($project == "All") {
                    echo '<th colspan="' . HISTORYBUILDCOUNT . '" class="tableBottomBorder tableSideBorder">Results in Builds';
                    if ($timescaleType == "Since")
                        echo ' (since ' . $timescaleValue . ')';
                    echo ' - see <a href="javascript:void(0);" onclick="showMessageWindow(\'ci/msgautotestresultdescription.html\')">notation</a>';
                    echo '</th>';
                } else {
                    $lastPrintedBuild = $latestBuildNumber;
                    $firstPrintedBuild = 1;
                    if ($lastPrintedBuild > HISTORYBUILDCOUNT)                                      // Limit number of Builds printed (the last HISTORYBUILDCOUNT ones)
                        $firstPrintedBuild = $lastPrintedBuild - HISTORYBUILDCOUNT + 1;
                    if ($lastPrintedBuild <= HISTORYBUILDCOUNT) {                                   // If latest Build number is less than the HISTORYBUILDCOUNT ...
                        for ($i=1; $i<=HISTORYBUILDCOUNT-$lastPrintedBuild; $i++) {
                            if (HISTORYBUILDCOUNT - $lastPrintedBuild >= $i)
                                echo '<td class="tableBottomBorder tableSideBorder"></td>';         // ... print empty cells to the left
                        }
                    }
                    for ($i=$firstPrintedBuild; $i<=$lastPrintedBuild; $i++) {                      // Print the Builds
                        $filteredBuild = '<b>' . $i . '</b>';
                        $cellColor = '<td class="tableBottomBorder tableSideBorder tableCellCentered">';
                        if ($timescaleType == "All") {
                            $buildNumberOffset = $latestBuildNumber - $i;
                            if ($buildNumberOffset == $build) {
                                $filteredBuild = $i;
                                $cellColor = '<td class="tableBottomBorder tableSideBorder tableCellCentered tableCellBuildSelected">';
                            }
                        } else {
                            if ($i >= $firstTimescaleBuild)
                                $cellColor = '<td class="tableBottomBorder tableSideBorder tableCellCentered timescaleSince">';
                        }
                        echo $cellColor . $filteredBuild . '</td>';
                    }
                }
                echo '</tr>';
                $k = 0;
                $previousProject = "";
                foreach ($arrayFailingAutotestProjectNames as $projectKey => $projectValue) {
                    foreach ($_SESSION['arrayConfName'] as $confKey => $confValue) {
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
                                /* Check Configuration Build result and significance from database (both for failed and successful Autotests) */
                                $sql = cleanSqlString(
                                       "SELECT result, insignificant, timestamp
                                        FROM cfg
                                        WHERE cfg=\"$confValue\" AND project=\"$projectValue\" AND build_number=$i");    // Will return one row
                                $dbColumnCfgResult = 0;
                                $dbColumnCfgInsignificant = 1;
                                $dbColumnCfgTimestamp = 2;
                                if ($useMysqli) {
                                    $result2 = mysqli_query($conn, $sql);
                                    $resultRow2 = mysqli_fetch_row($result2);
                                } else {
                                    $result2 = mysql_query($sql) or die (mysql_error());
                                    $resultRow2 = mysql_fetch_row($result2);
                                }
                                $buildResult = $resultRow2[$dbColumnCfgResult];
                                $booBuildSign = FALSE;
                                if ($resultRow2[$dbColumnCfgInsignificant] == 0)
                                    $booBuildSign = TRUE;
                                $booBuildOutOfTimescale = FALSE;
                                if ($timescaleType == "Since")
                                    if ($resultRow2[$dbColumnCfgTimestamp] < $timescaleValue)
                                        $booBuildOutOfTimescale = TRUE;
                                /* Check Autotest result, significance and test case data from the array saved above */
                                $stringAutotestDetails = ',' . $arrayFailingAutotestProjectConfBuilds[$projectKey][$confKey];
                                $booAutotest = strpos($stringAutotestDetails, ',' . strval($i) . '-');         // If run
                                $booSignAutotest = strpos($stringAutotestDetails, ',' . strval($i) . '-0');    // Significance
                                $booFailedAutotest = FALSE;                                                    // Result
                                if (strpos($stringAutotestDetails, ',' . strval($i) . '-0-1') OR
                                    strpos($stringAutotestDetails, ',' . strval($i) . '-1-1'))
                                    $booFailedAutotest = TRUE;
                                $buildNumberString = createBuildNumberString($i);
                                $buildDetails = '<b>Autotest ' . $autotest . ':</b><br>';
                                if ($booAutotest) {                                                                                 // Autotest data available only from all_test table
                                    $stringAutotestDetails = substr($stringAutotestDetails, strpos($stringAutotestDetails, ',' . strval($i) . '-'));    // Find the right Build
                                    $arrayAutotestDetails = array();
                                    $arrayAutotestDetails = explode('-', $stringAutotestDetails);
                                    if ($booFailedAutotest)                                                                         // Autotest result
                                        $autotestResult = "FAILED";
                                    else
                                        $autotestResult = "PASSED";
                                    $buildDetails = $buildDetails . 'Result: ' . $autotestResult . '<br>';
                                    if ($booSignAutotest)                                                                           // Autotest significance
                                        $autotestInsignificant = FLAGOFF;
                                    else
                                        $autotestInsignificant = FLAGON;
                                    $buildDetails = $buildDetails . 'Insignificant: ' . $autotestInsignificant . '<br>';
                                    $buildDetails = $buildDetails . 'Passed cases: ' . $arrayAutotestDetails[$confStringPassed] . '<br>';
                                    $buildDetails = $buildDetails . 'Failed cases: ' . $arrayAutotestDetails[$confStringFailed] . '<br>';
                                    $buildDetails = $buildDetails . 'Skipped cases: ' . $arrayAutotestDetails[$confStringSkipped] . '<br>';
                                    $buildDetails = $buildDetails . 'Runs: ' . $arrayAutotestDetails[$confStringRuns] . '<br>';
                                } else {
                                    $buildDetails = $buildDetails . '(results not available)<br>';
                                }
                                $buildDetails = $buildDetails . '<br>';
                                $buildDetails = $buildDetails . '<b>Build ' . $i . ':</b><br>';
                                if ($buildResult <> "") {                                                                           // Build data available
                                    $buildDetails = $buildDetails . 'Result: ' . $buildResult . '<br>';                             // Build result (Build data available for all)
                                    $buildDetails = $buildDetails . 'Time: ' . $resultRow2[$dbColumnCfgTimestamp] . '<br>';         // Build time
                                    if ($booBuildSign)                                                                              // Build significance
                                        $buildInsignificant = FLAGOFF;
                                    else
                                        $buildInsignificant = FLAGON;
                                    $buildDetails = $buildDetails . 'Insignificant: ' . $buildInsignificant . '<br>';
                                } else {
                                    $buildDetails = $buildDetails . '(results not available)<br>';
                                }
                                $buildDetails = $buildDetails . '<i>Click link for the log file</i>';
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
                                                    . '/' . $confValue . '/log.txt.gz" target="_blank">' . '<span class="popupMessage">'
                                                    . $i . '<span class="fontColorBlack">' . $buildDetails . '</span></span>' . '</a></td>';
                                            }
                                        } else {
                                            if ($booBuildOutOfTimescale) {
                                                // Red background to indicate significant failure in insignificant Conf, grey font color to indicate out of Timescale
                                                echo '<td class="tableSingleBorder tableCellCentered tableCellBackgroundRed fontColorGrey">' . $i . '</td>';
                                            } else {
                                                // Red background to indicate significant failure in insignificant Conf; link to log file
                                                echo '<td class="tableSingleBorder tableCellCentered tableCellBackgroundRed">
                                                    <a href="' . LOGFILEPATHCI . $projectValue . '/build_' . $buildNumberString
                                                    . '/' . $confValue . '/log.txt.gz" target="_blank">' . '<span class="popupMessage">'
                                                    . $i . '<span class="fontColorBlack">' . $buildDetails . '</span></span>' . '</a></td>';
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
                                                    . '/' . $confValue . '/log.txt.gz" target="_blank">' . '<span class="popupMessage">'
                                                    . $i . '<span class="fontColorBlack">' . $buildDetails . '</span></span>' . '</a></td>';
                                        }
                                    }
                                /* Print the successful or not run Build */
                                } else {
                                    // Successful (Autotest run and not failed)
                                    if ($booAutotest) {
                                        if ($booBuildOutOfTimescale) {
                                            // Green background to indicate success, grey font color to indicate out of Timescale
                                            echo '<td class="tableSingleBorder tableCellCentered tableCellBackgroundGreen fontColorGrey">' . $i . '</td>';
                                        } else {
                                            // Green background to indicate success; log file link
                                            echo '<td class="tableSingleBorder tableCellCentered tableCellBackgroundGreen">
                                                  <a href="' . LOGFILEPATHCI . $projectValue . '/build_' . $buildNumberString
                                                    . '/' . $confValue . '/log.txt.gz" target="_blank">' . '<span class="popupMessage">'
                                                    . $i . '<span class="fontColorBlack">' . $buildDetails . '</span></span>' . '</a></td>';
                                        }
                                    // Autotest was not run
                                    } else {
                                        if ($booBuildOutOfTimescale) {
                                            // White background to indicate not a failure, grey font color to indicate out of Timescale
                                            echo '<td class="tableSingleBorder tableCellCentered fontColorGrey">' . $i . '</td>';
                                        } else {
                                            // White background to indicate not a failure; log file link
                                            echo '<td class="tableSingleBorder tableCellCentered">
                                                  <a href="' . LOGFILEPATHCI . $projectValue . '/build_' . $buildNumberString
                                                    . '/' . $confValue . '/log.txt.gz" target="_blank">' . '<span class="popupMessage">'
                                                    . $i . '<span class="fontColorBlack">' . $buildDetails . '</span></span>' . '</a></td>';
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
                    mysqli_free_result($result2);
                $timeAutotestHistoryEnd = microtime(true);

                /* 3. Autotest case data */

                $arrayTestcaseNames = array();
                $arrayTestcaseFailed = array();
                $arrayTestcaseAll = array();
                $arrayInvalidTestResultFiles = array();
                $arrayTestcaseConfs = array();
                $failingTestcaseCount = 0;
                $testcaseCount = 0;
                if ($round == 1) {
                    echo '<div class="metricsTitle">';
                    echo '<b>Test cases <span class="loading"><span>.</span><span>.</span><span>.</span></span> </b>';
                    echo '</div>';
                } else {
                    echo '<div class="metricsTitle">';
                    echo '<b>Test cases</b>';                                                           // Title to be continued below with build details ...
                    if ($checkedProjectCount == 1) {                                                    // Data shown only for one project for performance reasons

                        /* Get the latest project build number if not available yet (the combined $ciProject and $ciBranch selection results to one project although $project not selected) */
                        if ($buildNumber == MAXCIBUILDNUMBER) {
                            foreach ($_SESSION['arrayProjectName'] as $projectKey => $projectValue) {
                                if ($checkedProject == $projectValue)
                                    $buildNumber = $arrayProjectBuildLatest[$projectKey];
                            }
                        }

                        /* Get the first available/filtered build in database */
                        $minBuildNumberInDatabase = $_SESSION['minBuildNumberInDatabase'];

                        /* Read the test results from the Project directory (structure is e.g. QtBase_stable_Integration/build_03681/macx-ios-clang_OSX_10.8 */
                        if ($timescaleType == "All") {                                                  // If timescale not filtered read only the latest/selected build
                            $buildCheckType = CHECKBUILDONE;
                            $buildNumberToCheck = $buildNumber;
                        } else {                                                                        // If timescale filtered read all the available builds since the date
                            $buildCheckType = CHECKBUILDSINCE;
                            $buildNumberToCheck = setMinBuildNumberToCheck($latestBuildNumber, $minBuildNumberInDatabase, $timescaleType);
                        }
                        if ($ciPlatform == 0)                                                           // If Platform not filtered, read any Conf
                            $confToCheck = "All";
                        else                                                                            // Filter with Platform
                            $confToCheck = $ciPlatformFilter;
                        if ($conf <> "All")                                                             // Filter with Conf (overwrite possible Platform filter)
                            $confToCheck = $conf;
                        $minBuildNumberWithTestResults = readProjectTestResultDirectory(
                            CITESTRESULTSDIRECTORY, $checkedProject, $buildCheckType, $buildNumberToCheck, $confToCheck, TRUE, $arrayAutotestName,
                            $arrayFailingAutotestAllBuilds,
                            $arrayTestcaseNames, $arrayTestcaseFailed, $arrayTestcaseAll, $arrayTestcaseConfs,
                            $failingTestcaseCount, $testcaseCount, $arrayInvalidTestResultFiles);       // Returns the first available build number if timescale filtered, otherwise the selected build

                        /* If test result files found */
                        if ($minBuildNumberWithTestResults < MAXCIBUILDNUMBER) {

                            /* Calculate the failure percentage */
                            $maxCount = 0;
                            for ($k=0; $k<$testcaseCount; $k++) {
                                $arrayTestcaseFailedPercentage[$k] = calculatePercentage($arrayTestcaseFailed[$k], $arrayTestcaseAll[$k]);
                                if ($arrayTestcaseFailed[$k] > $maxCount)
                                    $maxCount = $arrayTestcaseFailed[$k];                               // Save maxCount for sorting
                                if ($arrayTestcaseAll[$k] > $maxCount)
                                    $maxCount = $arrayTestcaseAll[$k];                                  // Save maxCount for sorting
                            }

                            /* Read the timestamp of the first build with detailed test result data */
                            if ($project <> "All") {
                                $projectFilter = "project = \"$project\"";                              // Project is filtered here
                                $buildFilter = "build_number = $minBuildNumberWithTestResults";
                                $from = "ci";
                                $sql = "SELECT MIN(timestamp)
                                        FROM $from
                                        WHERE $projectFilter AND $buildFilter";
                                $dbColumnCiTimestamp = 0;
                                if ($useMysqli) {
                                    $result2 = mysqli_query($conn, $sql);
                                    $numberOfRows2 = mysqli_num_rows($result2);
                                } else {
                                    $result2 = mysql_query($sql) or die (mysql_error());
                                    $numberOfRows2 = mysql_num_rows($result2);
                                }
                                if ($useMysqli)
                                    $resultRow2 = mysqli_fetch_row($result2);
                                else
                                    $resultRow2 = mysql_fetch_row($result2);
                                $minBuildNumberWithTestResultsTimestamp = $resultRow2[$dbColumnCiTimestamp];
                                if ($useMysqli)
                                    mysqli_free_result($result2);                                       // Free result set
                            }

                            /* Print the test report info */
                            $buildCount = $latestBuildNumber - $minBuildNumberWithTestResults + 1;
                            if ($timescaleType == "All")
                                $testResultBuilds = ' from build ';
                            else
                                $testResultBuilds = ' from ' . $buildCount . ' builds since ';
                            $testResultBuilds = $testResultBuilds . $minBuildNumberWithTestResults . ' ('
                                . substr($minBuildNumberWithTestResultsTimestamp, 0, strpos($minBuildNumberWithTestResultsTimestamp, " ")) . ')';
                            $testResultBuildsSeeMore = setSeeMoreNote($timescaleType, $timescaleValue);
                            $failingTestcasePercentage = calculatePercentage($failingTestcaseCount, $testcaseCount);
                            echo $testResultBuilds . $testResultBuildsSeeMore . '</div>';               // ... the title closed here

                            /* Set the default sorting to Failed % when displayed */
                            $sortBy = AUTOTESTSORTBYAUTOTESTFAILEDPERCENTAGE;

                            /* Print the test case table titles */
                            echo '<table class="fontSmall">';
                            echo '<tr>';
                            echo '<th></th>';
                            if ($timescaleType == "All" AND CITESTRESULTBUILDCOUNT == 1) {
                                $buildData = '<td colspan="3" class="tableBottomBorder tableSideBorder tableCellCentered tableCellBuildSelected">';
                                if ($build == 0)
                                    $buildData = $buildData . 'LATEST BUILD</td>';
                                else
                                    $buildData = $buildData . 'BUILD ' . $buildNumber . '</td>';
                            }
                            if ($timescaleType == "All" AND CITESTRESULTBUILDCOUNT > 1)
                                $buildData = '<td colspan="3" class="tableBottomBorder tableSideBorder tableCellCentered timescaleAll">
                                    <b>ALL BUILDS (SINCE ' . $_SESSION['minBuildDate'] . ')</b></td>';
                            if ($timescaleType == "Since") {
                                if ($minBuildNumberWithTestResults == $firstTimescaleBuild)     // Data available since the same build as filtered by date
                                    $buildData = '<td colspan="3" class="tableBottomBorder tableSideBorder tableCellCentered timescaleSince">';
                                else                                                            // Data available only for certain builds after those filtered by date
                                    $buildData = '<td colspan="3" class="tableBottomBorder tableSideBorder tableCellCentered timescaleSinceBuild">';
                                $buildData = $buildData . 'ALL BUILDS</td>';
                            }
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
                                $xmlBuildInfo = $xmlBuildInfo . ' since Build ' . $minBuildNumberWithTestResults;
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

                                            /* Test case: Builds where failed */
                                            echo '<td class="tableLeftBorder tableCellCentered tableWidth2">';
                                            if ($arrayTestcaseFailed[$i] > 0)
                                                echo $arrayTestcaseFailed[$i];
                                            else
                                                echo '-';
                                            echo '</td>';

                                            /* Test case: Builds where run (all) */
                                            echo '<td class="tableCellCentered tableWidth2">';
                                            if ($arrayTestcaseAll[$i] > 0)
                                                echo $arrayTestcaseAll[$i];
                                            else
                                                echo '-';
                                            echo '</td>';

                                            /* Test case: Failed % */
                                            if ($arrayTestcaseFailedPercentage[$i] >= AUTOTESTFAILUREWARNINGLEVEL)
                                                echo '<td class="tableRightBorder tableCellCentered fontColorRed tableWidth1">';
                                            else
                                                echo '<td class="tableRightBorder tableCellCentered tableWidth2">';
                                            if ($arrayTestcaseFailedPercentage[$i] > 0)
                                                echo $arrayTestcaseFailedPercentage[$i] . '%';
                                            else
                                                echo '-';
                                            echo '</td>';

                                            /* Configurations */
                                            if ($conf == "All") {                                       // Show list of configurations only when not filtered

                                                /* Configuration: Name */
                                                echo '<td class="tableTopBorder tableLeftBorder">';
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

                                                /* Configuration: Failed testcases */
                                                echo '<td class="tableTopBorder tableLeftBorder">';
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

                                                /* Configuration: All testcases */
                                                echo '<td class="tableTopBorder">';
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

                                                /* Configuration: Failed % */
                                                echo '<td class="tableTopBorder tableRightBorder">';
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
                            $arrayTestcaseFailedPercentageSum = calculatePercentage($arrayTestcaseFailedSum, $arrayTestcaseAllSum);
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
                            echo '</div><br/><br/>(not any test result files available)<br/><br/>';
                        }

                    } else {
                        echo '</div><br/><br/><i>Please select one of the Projects above to see the test case list...</i><br/><br/>';
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
