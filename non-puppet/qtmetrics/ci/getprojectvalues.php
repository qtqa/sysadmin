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

    /* Read all Project values from database */
    $sql="SELECT project, MAX(build_number), result FROM ci GROUP BY project ORDER BY project;";
    define("DBCOLUMNCIPROJECT", 0);
    define("DBCOLUMNCIBUILDNUMBER", 1);
    define("DBCOLUMNCIRESULT", 2);
    if ($useMysqli) {
        $result = mysqli_query($conn, $sql);
    } else {
        $selectdb="USE $db";
        $result = mysql_query($selectdb) or die (mysql_error());
        $result = mysql_query($sql) or die (mysql_error());
    }

    /* Store Project values to session variable */
    if ($useMysqli)
        $numberOfRows = mysqli_num_rows($result);
    else
        $numberOfRows = mysql_num_rows($result);
    $arrayProjectName = array();
    $arrayProjectBuildLatest = array();
    $arrayProjectBuildLatestResult = array();
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

    /* Read the result of latest Build for Projects from database (all must be read separately) */
    for ($j=0; $j<$numberOfRows; $j++) {                                             // Loop the Projects
        $sql="SELECT project, build_number, result FROM ci WHERE project=\"$arrayProjectName[$j]\" AND build_number=$arrayProjectBuildLatest[$j];";        // Will return only one row
        if ($useMysqli) {
            $result = mysqli_query($conn, $sql);
            $resultRow = mysqli_fetch_row($result);
        } else {
            $selectdb="USE $db";
            $result = mysql_query($selectdb) or die (mysql_error());
            $result = mysql_query($sql) or die (mysql_error());
            $resultRow = mysql_fetch_row($result);
        }
        $arrayProjectBuildLatestResult[] = $resultRow[DBCOLUMNCIRESULT];             // Build result
    }

    /* Save session variables */
    $_SESSION['arrayProjectName'] = $arrayProjectName;
    $_SESSION['arrayProjectBuildLatest'] = $arrayProjectBuildLatest;
    $_SESSION['arrayProjectBuildLatestResult'] = $arrayProjectBuildLatestResult;
    $_SESSION['arrayProjectBuildScopeMin'] = $arrayProjectBuildScopeMin;

    if ($useMysqli)
        mysqli_free_result($result);                                                 // Free result set

    /* Close connection to the server */
    require(__DIR__.'/../connectionclose.php');

}

?>