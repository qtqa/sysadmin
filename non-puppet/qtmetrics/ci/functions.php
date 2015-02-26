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

/* Read the plain Project name from the full Project string
   Input:   $project        (string)  Full Project name (e.g. "QtDeclarative_dev_Integration")
   Return:  (string) String till the second-to-last "_" character
*/
function getProjectName($project)
{
    $ciProject = substr($project, 0, strpos($project, "_Integration"));     // Cut to e.g. "QtDeclarative_dev"
    $ciProject = substr($ciProject, 0, strrpos($ciProject, "_"));           // Cut to e.g. "QtDeclarative"; strrpos used because some Project names may contain "_"
    if ($project == "Qt_4.8_Integration")                                   // Exception
        $ciProject = "Qt_4.8";
    return $ciProject;
}

/* Read the plain Project branch from the full Project string
   Input:   $project        (string)  Full Project name (e.g. "QtDeclarative_dev_Integration")
   Return:  (string) String between the last two "_" characters
*/
function getProjectBranch($project)
{
    $ciBranch = substr($project, 0, strpos($project, "_Integration"));      // Cut to e.g. "QtDeclarative_dev"
    $ciBranch = substr($ciBranch, strrpos($ciBranch, "_") + 1);             // Cut to e.g. "dev"; strrpos used because some Project names may contain "_"
    if ($project == "Qt_4.8_Integration")                                   // Exception
        $ciBranch = "";
    return $ciBranch;
}

/* Convert the numeric Build number to a 5 digit string needed for directory links (Example: http://testresults.qt.io/ci/Qt3D_master_Integration/build_00412)
   Input:   $buildNumber    (integer)  Build number (1 - 99999)
   Return:  (string) Five character string (00001 - 99999)
*/
function createBuildNumberString($buildNumber)
{
    $buildString = $buildNumber;
    if ($buildNumber < 10000)
        $buildString = '0' . $buildNumber;
    if ($buildNumber < 1000)
        $buildString = '00' . $buildNumber;
    if ($buildNumber < 100)
        $buildString = '000' . $buildNumber;
    if ($buildNumber < 10)
        $buildString = '0000' . $buildNumber;
    return $buildString;
}

/* Clean the SQL statement with possible errors when combining several WHERE conditions
   Input:   $sqlString      (string)  The SQL statement
   Return:  (string)
*/
function cleanSqlString($sqlString)
{
    $sql = $sqlString;
    // Remove multiple spaces
    $sql = preg_replace('/\s+/', ' ', $sql);
    // Replace invalid statements
    $invalid = array("WHERE AND", "AND AND");
    $valid = array("WHERE", "AND");
    $sql = str_replace($invalid, $valid, $sql);
    // Remove empty WHERE statement
    if (strlen($sql) - strlen("WHERE") - 1 == strpos($sql, "WHERE"))
        $sql = str_replace("WHERE", "", $sql);
    return $sql;
}

/* Identify the Autotest test result from the test case results
   Input:   $casesPassed    (integer)  Number of passed cases for the autotest
            $casesFailed    (integer)  Number of failed cases for the autotest
            $casesSkipped   (integer)  Number of skipped cases for the autotest
   Return:  (boolean) TRUE if failed
*/
function checkAutotestFailed($casesPassed, $casesFailed, $casesSkipped)
{
    $result = FALSE;
    if (isset($casesFailed) AND $casesFailed > 0)                                  // Failed if failed cases (and not NULL)
        $result = TRUE;
    if (!isset($casesPassed) AND !isset($casesFailed) AND !isset($casesSkipped))    // Crashed (failed) if counts not set (all are NULL)
        $result = TRUE;
    return $result;
}

/* Check if the fullString includes the findString (wildcard '*' may be used) or all of its strings separated with the wildcard '*'
   Input:   $fullString     (string)  The 'haystack' where searched from
            $findString     (string)  The 'needle' to be matched
   Return:  (boolean)
*/
function checkStringMatch($fullString, $findString)
{
    $arrayFind = array();
    $arrayFind = explode('*', $findString);                 // Get all strings separated with '*' into an array
    $arrayFind = array_filter($arrayFind);                  // Remove empty values
    $findCount = count($arrayFind);
    $findMatchCount = 0;
    $booMatch = FALSE;
    foreach ($arrayFind as $key => $find) {
        if (stripos($fullString, $find) !== FALSE)
            $findMatchCount++;                              // Count all the separate matches
    }
    if ($findCount == $findMatchCount)
        $booMatch = TRUE;                                   // All strings separated with '*' match
    return $booMatch;
}

/* Calculate percentage so that very low but not quite zero result is rounded to 1 and almost 100% but not quite is rounded to 99
   Input:   $numerator      (integer)  The numerator
            $divider        (integer)  The divider
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

?>