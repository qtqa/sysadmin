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

// (Note: session started in getfilters.php)

/* Get list of project values to session variable xxx (if not done already) */
if(!isset($_SESSION['arrayProjectName'])) {
    /* Connect to the server */
    require(__DIR__.'/../connect.php');

    /* Step 1: Read all Project values from database ... */
    $sql="SELECT project, MAX(build_number)
          FROM ci
          GROUP BY project ORDER BY project;";
    define("DBCOLUMNCIPROJECT", 0);
    define("DBCOLUMNCIBUILDNUMBER", 1);
    if ($useMysqli) {
        $result = mysqli_query($conn, $sql);
        $numberOfRows = mysqli_num_rows($result);
    } else {
        $selectdb="USE $db";
        $result = mysql_query($selectdb) or die (mysql_error());
        $result = mysql_query($sql) or die (mysql_error());
        $numberOfRows = mysql_num_rows($result);
    }
    /* ... and store to session variable */
    $arrayProjectName = array();
    $arrayProjectBuildLatest = array();
    $arrayProjectBuildScopeMin = array();
    for ($j=0; $j<$numberOfRows; $j++) {                                             // Loop the Projects
        if ($useMysqli)
            $resultRow = mysqli_fetch_row($result);
        else
            $resultRow = mysql_fetch_row($result);
        $arrayProjectName[] = $resultRow[DBCOLUMNCIPROJECT];                         // Project name
        $arrayProjectBuildLatest[] = $resultRow[DBCOLUMNCIBUILDNUMBER];              // Latest Build number for the Project
        if ($resultRow[DBCOLUMNCIBUILDNUMBER] - AUTOTEST_LATESTBUILDCOUNT > 0)
            $arrayProjectBuildScopeMin[] = $resultRow[DBCOLUMNCIBUILDNUMBER] - AUTOTEST_LATESTBUILDCOUNT + 1;   // First Build number in metrics scope
        else
            $arrayProjectBuildScopeMin[] = 1;                                        // Less builds than the scope count
    }

    /* Step 2: Read the result of latest Build for each Project from the database (all must be read separately) */
    $arrayProjectBuildLatestResult = array();
    $arrayProjectBuildLatestTimestamp = array();
    $arrayProjectBuildLatestDuration = array();
    define("DBCOLUMNCIPROJECT", 0);
    define("DBCOLUMNCIBUILDNUMBER", 1);
    define("DBCOLUMNCIRESULT", 2);
    define("DBCOLUMNCITIMESTAMP", 3);
    define("DBCOLUMNCIDURATION", 4);
    for ($i=0; $i<$numberOfRows; $i++) {                                             // Loop the Projects
        $sql = "SELECT project, build_number, result, timestamp, duration
                FROM ci
                WHERE project=\"$arrayProjectName[$i]\" AND build_number=$arrayProjectBuildLatest[$i];";        // Will return only one row
        if ($useMysqli) {
            $result2 = mysqli_query($conn, $sql);
            $resultRow2 = mysqli_fetch_row($result2);
        } else {
            $selectdb="USE $db";
            $result2 = mysql_query($selectdb) or die (mysql_error());
            $result2 = mysql_query($sql) or die (mysql_error());
            $resultRow2 = mysql_fetch_row($result2);
        }
        $arrayProjectBuildLatestResult[] = $resultRow2[DBCOLUMNCIRESULT];
        $arrayProjectBuildLatestTimestamp[] = $resultRow2[DBCOLUMNCITIMESTAMP];
        $arrayProjectBuildLatestDuration[] = $resultRow2[DBCOLUMNCIDURATION];
    }

    /* Step 3: Read the number of successful, failed and all Builds for each Project from the database (all must be read separately) */
    $arrayProjectBuildCount = array();
    $arrayProjectBuildCountSuccess = array();
    $arrayProjectBuildCountFailure = array();
    for ($i=0; $i<$numberOfRows; $i++) {                                             // Loop the Projects
        $sql = "SELECT 'SUCCESS', COUNT(result) AS 'count'
                FROM ci
                WHERE project=\"$arrayProjectName[$i]\" AND result=\"SUCCESS\"
                UNION
                SELECT 'FAILURE', COUNT(result) AS 'count'
                FROM ci
                WHERE project=\"$arrayProjectName[$i]\" AND result=\"FAILURE\"
                UNION
                SELECT 'Total', COUNT(result) AS 'count'
                FROM ci
                WHERE project=\"$arrayProjectName[$i]\"";                            // Will return three rows
        if ($useMysqli) {
            $result2 = mysqli_query($conn, $sql);
            $numberOfRows2 = mysqli_num_rows($result2);
        } else {
            $selectdb="USE $db";
            $result2 = mysql_query($selectdb) or die (mysql_error());
            $result2 = mysql_query($sql) or die (mysql_error());
            $numberOfRows2 = mysql_num_rows($result2);
        }
        for ($j=0; $j<$numberOfRows2; $j++) {                                        // Loop the counts
            if ($useMysqli)
                $resultRow2 = mysqli_fetch_row($result2);
            else
                $resultRow2 = mysql_fetch_row($result2);
            if ($resultRow2[0] == "SUCCESS")
                $arrayProjectBuildCountSuccess[] = $resultRow2[1];
            if ($resultRow2[0] == "FAILURE")
                $arrayProjectBuildCountFailure[] = $resultRow2[1];
            if ($resultRow2[0] == "Total")
                $arrayProjectBuildCount[] = $resultRow2[1];
        }
    }

    /* Step 4: Read the number of failed significant and insignificant autotests in latest Build for each Project from the database (all must be read separately) */
    $arrayProjectBuildLatestSignificantCount = array();
    $arrayProjectBuildLatestInsignificantCount = array();
    for ($i=0; $i<$numberOfRows; $i++) {                                             // Loop the Projects
        $sql = "SELECT 'significant', COUNT(name) AS 'count'
                FROM test
                WHERE insignificant=0 AND project=\"$arrayProjectName[$i]\" AND build_number=$arrayProjectBuildLatest[$i]
                UNION
                SELECT 'insignificant', COUNT(name) AS 'count'
                FROM test
                WHERE insignificant=1 AND project=\"$arrayProjectName[$i]\" AND build_number=$arrayProjectBuildLatest[$i]";       // Will return two rows
        if ($useMysqli) {
            $result2 = mysqli_query($conn, $sql);
            $numberOfRows2 = mysqli_num_rows($result2);
        } else {
            $selectdb="USE $db";
            $result2 = mysql_query($selectdb) or die (mysql_error());
            $result2 = mysql_query($sql) or die (mysql_error());
            $numberOfRows2 = mysql_num_rows($result2);
        }
        for ($j=0; $j<$numberOfRows2; $j++) {                                        // Loop the counts
            if ($useMysqli)
                $resultRow2 = mysqli_fetch_row($result2);
            else
                $resultRow2 = mysql_fetch_row($result2);
            if ($resultRow2[0] == "significant")
                $arrayProjectBuildLatestSignificantCount[] = $resultRow2[1];
            if ($resultRow2[0] == "insignificant")
                $arrayProjectBuildLatestInsignificantCount[] = $resultRow2[1];
        }
    }

    /* Step 5: Read the number of Confs (including force success and insignificant) for each Project from the database (all must be read separately) */
    $arrayProjectBuildLatestConfCount = array();
    $arrayProjectBuildLatestConfCountForceSuccess = array();
    $arrayProjectBuildLatestConfCountInsignificant = array();
    for ($i=0; $i<$numberOfRows; $i++) {                                             // Loop the Projects
        $sql = "SELECT 'forcesuccess', COUNT(cfg) AS 'count'
                FROM cfg
                WHERE project=\"$arrayProjectName[$i]\" AND build_number=$arrayProjectBuildLatest[$i] AND forcesuccess=1
                UNION
                SELECT 'insignificant', COUNT(cfg) AS 'count'
                FROM cfg
                WHERE project=\"$arrayProjectName[$i]\" AND build_number=$arrayProjectBuildLatest[$i] AND insignificant=1
                UNION
                SELECT 'Total', COUNT(cfg) AS 'count'
                FROM cfg
                WHERE project=\"$arrayProjectName[$i]\" AND build_number=$arrayProjectBuildLatest[$i]";
        if ($useMysqli) {
            $result2 = mysqli_query($conn, $sql);
            $numberOfRows2 = mysqli_num_rows($result2);
        } else {
            $selectdb="USE $db";
            $result2 = mysql_query($selectdb) or die (mysql_error());
            $result2 = mysql_query($sql) or die (mysql_error());
            $numberOfRows2 = mysql_num_rows($result2);
        }
        for ($j=0; $j<$numberOfRows2; $j++) {                                        // Loop the counts
            if ($useMysqli)
                $resultRow2 = mysqli_fetch_row($result2);
            else
                $resultRow2 = mysql_fetch_row($result2);

            if ($resultRow2[0] == "forcesuccess")
                $arrayProjectBuildLatestConfCountForceSuccess[] = $resultRow2[1];
            if ($resultRow2[0] == "insignificant")
                $arrayProjectBuildLatestConfCountInsignificant[] = $resultRow2[1];
            if ($resultRow2[0] == "Total")
                $arrayProjectBuildLatestConfCount[] = $resultRow2[1];
        }
    }

    /* Save session variables */
    $_SESSION['arrayProjectName'] = $arrayProjectName;
    $_SESSION['arrayProjectBuildLatest'] = $arrayProjectBuildLatest;
    $_SESSION['arrayProjectBuildLatestResult'] = $arrayProjectBuildLatestResult;
    $_SESSION['arrayProjectBuildLatestTimestamp'] = $arrayProjectBuildLatestTimestamp;
    $_SESSION['arrayProjectBuildLatestDuration'] = $arrayProjectBuildLatestDuration;
    $_SESSION['arrayProjectBuildScopeMin'] = $arrayProjectBuildScopeMin;
    $_SESSION['arrayProjectBuildCount'] = $arrayProjectBuildCount;
    $_SESSION['arrayProjectBuildCountSuccess'] = $arrayProjectBuildCountSuccess;
    $_SESSION['arrayProjectBuildCountFailure'] = $arrayProjectBuildCountFailure;
    $_SESSION['arrayProjectBuildLatestSignificantCount'] = $arrayProjectBuildLatestSignificantCount;
    $_SESSION['arrayProjectBuildLatestInsignificantCount'] = $arrayProjectBuildLatestInsignificantCount;
    $_SESSION['arrayProjectBuildLatestConfCount'] = $arrayProjectBuildLatestConfCount;
    $_SESSION['arrayProjectBuildLatestConfCountForceSuccess'] = $arrayProjectBuildLatestConfCountForceSuccess;
    $_SESSION['arrayProjectBuildLatestConfCountInsignificant'] = $arrayProjectBuildLatestConfCountInsignificant;

    if ($useMysqli) {
        mysqli_free_result($result);                                                 // Free result set
        mysqli_free_result($result2);                                                // Free result set
    }

    /* Close connection to the server */
    require(__DIR__.'/../connectionclose.php');

}

?>