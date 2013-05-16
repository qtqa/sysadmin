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
$timeStart = microtime(true);
$project = $_GET["project"];
$conf = $_GET["conf"];
// Problem: Parameter passing (with GET method in URL) destroys the string "++", e.g. "linux-g++-32_Ubuntu_10.04_x86" -> "linux-g  -32_Ubuntu_10.04_x86"
$conf = str_replace("g  ","g++",$conf);
$autotest = $_GET["autotest"];
// Problem: Problem: Parameter passing (with GET method in URL) does not work with text 'ftp' or 'http' (autotest names 'tst_qftp' and 'tst_qhttp')
// -> parameter is here 'decoded' ('encoded' in sending at the bottom part of this file)
$autotest = str_replace("ft-endoded-p","ftp",$autotest);
$autotest = str_replace("htt-endoded-p","http",$autotest);

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

/*************************************************************/
/* NESTED LEVEL 1: No autotest filtering done (default view) */
/*************************************************************/

if ($autotest == "All") {
    echo '<b>MOST COMMONLY FAILING INSIGNIFICANT AUTOTESTS:</b> Select Autotest<br/><br/>';
    if(isset($_SESSION['arrayAutotestName'])) {
        $i = 0;
        echo '<table class="fontSmall">';
        echo '<tr class="tableBottomBorder">';
        echo '<th></th>';                                                       // Titles
        echo '<th>Projects<br/>in latest</th>';
        echo '<th>Confs<br/>in latest</th>';
        echo '<th class="tableLeftBorder">Projects<br/>in last ' . AUTOTEST_LATESTBUILDCOUNT . '</th>';
        echo '<th><span class="popupMessage">Confs<br/>in last ' . AUTOTEST_LATESTBUILDCOUNT
            . '<span>List ordered by this column (skipped where count is 0 or 1)</span></span></th>';
        echo '<th class="tableLeftBorder">Projects<br/>in a week</th>';
        echo '<th>Confs<br/>in a week</th>';
        echo '<th class="tableLeftBorder">Projects<br/>in a month</th>';
        echo '<th>Confs<br/>in a month</th>';
        echo '</tr">';

        /* Get all (failing) autotest names */
        $arrayFailingAutotests = array();
        $arrayFailingAutotests = $_SESSION['arrayAutotestName'];
        $autotestCount = 0;
        foreach($arrayFailingAutotests as $key => $value) {
            $autotestCount++;
        }
        $arrayProjectName = $_SESSION['arrayProjectName'];
        $arrayProjectBuildLatest = $_SESSION['arrayProjectBuildLatest'];
        $arrayProjectBuildScopeMin = $_SESSION['arrayProjectBuildScopeMin'];

        /* Get number of Projects for each autotest in latest Build */
        $arrayFailingAutotestsProjectCountLatestBuild = array();
        $arrayFailingAutotestsProjectNamesLatestBuild = array();
        $k = 0;
        foreach($_SESSION['arrayProjectName'] as $projectKey => $projectValue) {
            $sql = "SELECT DISTINCT name
                    FROM test
                    WHERE project=\"$arrayProjectName[$k]\" AND build_number=$arrayProjectBuildLatest[$k] AND insignificant=1";
            if ($useMysqli) {
                $result = mysqli_query($conn, $sql);
                $numberOfRows = mysqli_num_rows($result);
            } else {
                $selectdb="USE $db";
                $result = mysql_query($selectdb) or die (mysql_error());
                $result = mysql_query($sql) or die (mysql_error());
                $numberOfRows = mysql_num_rows($result);
            }
            for ($i=0; $i<$numberOfRows; $i++) {                            // Loop the Project Autotests
                if ($useMysqli)
                    $resultRow = mysqli_fetch_row($result);
                else
                    $resultRow = mysql_fetch_row($result);
                for ($j=0; $j<$autotestCount; $j++) {                       // Loop all the Autotests to find the ones for this Project
                    if ($arrayFailingAutotests[$j] == $resultRow[0]) {
                        $arrayFailingAutotestsProjectCountLatestBuild[$j]++;
                        $arrayFailingAutotestsProjectNamesLatestBuild[$j]
                            = $arrayFailingAutotestsProjectNamesLatestBuild[$j] . '<br>' . $arrayProjectName[$k];
                        break;                                              // Match found, skip the rest
                    }
                }
            }
            $k++;
        }
        $_SESSION['arrayFailingAutotestsProjectCountLatestBuild'] = $arrayFailingAutotestsProjectCountLatestBuild;
        $_SESSION['arrayFailingAutotestsProjectNamesLatestBuild'] = $arrayFailingAutotestsProjectNamesLatestBuild;

        /* Get number of Configurations for each autotest in latest Build */
        $arrayFailingAutotestsConfCountLatestBuild = array();
        $arrayFailingAutotestsConfNamesLatestBuild = array();
        $k = 0;
        foreach($_SESSION['arrayProjectName'] as $projectKey => $projectValue) {
            $sql = "SELECT name, cfg
                    FROM test
                    WHERE project=\"$arrayProjectName[$k]\" AND build_number=$arrayProjectBuildLatest[$k] AND insignificant=1
                    ORDER BY name";
            if ($useMysqli) {
                $result = mysqli_query($conn, $sql);
                $numberOfRows = mysqli_num_rows($result);
            } else {
                $selectdb="USE $db";
                $result = mysql_query($selectdb) or die (mysql_error());
                $result = mysql_query($sql) or die (mysql_error());
                $numberOfRows = mysql_num_rows($result);
            }
            for ($i=0; $i<$numberOfRows; $i++) {                            // Loop the Project Autotests
                if ($useMysqli)
                    $resultRow = mysqli_fetch_row($result);
                else
                    $resultRow = mysql_fetch_row($result);
                for ($j=0; $j<$autotestCount; $j++) {                       // Loop all the Autotests to find the ones for this Project
                    if ($arrayFailingAutotests[$j] == $resultRow[0]) {
                        if (!strpos($arrayFailingAutotestsConfNamesLatestBuild[$j],$resultRow[1])) {     // If Conf not yet counted in
                            $arrayFailingAutotestsConfCountLatestBuild[$j]++;
                            $arrayFailingAutotestsConfNamesLatestBuild[$j]
                                = $arrayFailingAutotestsConfNamesLatestBuild[$j] . '<br>' . $resultRow[1];
                        }
                        break;                                              // Match found, skip the rest
                    }
                }
            }
            $k++;
        }
        $_SESSION['arrayFailingAutotestsConfCountLatestBuild'] = $arrayFailingAutotestsConfCountLatestBuild;
        $_SESSION['arrayFailingAutotestsConfNamesLatestBuild'] = $arrayFailingAutotestsConfNamesLatestBuild;

        /* Get number of Projects for each autotest in last x Builds */
        $arrayFailingAutotestsProjectCountLastBuilds = array();
        $arrayFailingAutotestsProjectNamesLastBuilds = array();
        $k = 0;
        foreach($_SESSION['arrayProjectName'] as $projectKey => $projectValue) {
            $sql = "SELECT DISTINCT name
                    FROM test
                    WHERE project=\"$arrayProjectName[$k]\" AND build_number>$arrayProjectBuildScopeMin[$k] AND insignificant=1";
            if ($useMysqli) {
                $result = mysqli_query($conn, $sql);
                $numberOfRows = mysqli_num_rows($result);
            } else {
                $selectdb="USE $db";
                $result = mysql_query($selectdb) or die (mysql_error());
                $result = mysql_query($sql) or die (mysql_error());
                $numberOfRows = mysql_num_rows($result);
            }
            for ($i=0; $i<$numberOfRows; $i++) {                            // Loop the Project Autotests
                if ($useMysqli)
                    $resultRow = mysqli_fetch_row($result);
                else
                    $resultRow = mysql_fetch_row($result);
                for ($j=0; $j<$autotestCount; $j++) {                       // Loop all the Autotests to find the ones for this Project
                    if ($arrayFailingAutotests[$j] == $resultRow[0]) {
                        $arrayFailingAutotestsProjectCountLastBuilds[$j]++;
                        $arrayFailingAutotestsProjectNamesLastBuilds[$j]
                            = $arrayFailingAutotestsProjectNamesLastBuilds[$j] . '<br>' . $arrayProjectName[$k];
                        break;                                              // Match found, skip the rest
                    }
                }
            }
            $k++;
        }
        $_SESSION['arrayFailingAutotestsProjectCountLastBuilds'] = $arrayFailingAutotestsProjectCountLastBuilds;
        $_SESSION['arrayFailingAutotestsProjectNamesLastBuilds'] = $arrayFailingAutotestsProjectNamesLastBuilds;

        /* Get number of Configurations for each autotest in last x Builds */
        $arrayFailingAutotestsConfCountLastBuilds = array();
        $arrayFailingAutotestsConfNamesLastBuilds = array();
        $k = 0;
        $maxCount = 0;
        foreach($_SESSION['arrayProjectName'] as $projectKey => $projectValue) {
            $sql = "SELECT name, cfg
                    FROM test
                    WHERE project=\"$arrayProjectName[$k]\" AND build_number>$arrayProjectBuildScopeMin[$k] AND insignificant=1
                    ORDER BY name";
            if ($useMysqli) {
                $result = mysqli_query($conn, $sql);
                $numberOfRows = mysqli_num_rows($result);
            } else {
                $selectdb="USE $db";
                $result = mysql_query($selectdb) or die (mysql_error());
                $result = mysql_query($sql) or die (mysql_error());
                $numberOfRows = mysql_num_rows($result);
            }
            for ($i=0; $i<$numberOfRows; $i++) {                                // Loop the Project Autotests
                if ($useMysqli)
                    $resultRow = mysqli_fetch_row($result);
                else
                    $resultRow = mysql_fetch_row($result);
                for ($j=0; $j<$autotestCount; $j++) {                           // Loop all the Autotests to find the ones for this Project
                    if ($arrayFailingAutotests[$j] == $resultRow[0]) {
                        if (!strpos($arrayFailingAutotestsConfNamesLastBuilds[$j],$resultRow[1])) {     // If Conf not yet counted in
                            $arrayFailingAutotestsConfCountLastBuilds[$j]++;
                            $arrayFailingAutotestsConfNamesLastBuilds[$j]
                                = $arrayFailingAutotestsConfNamesLastBuilds[$j] . '<br>' . $resultRow[1];
                            if ($arrayFailingAutotestsConfCountLastBuilds[$j] > $maxCount)
                                $maxCount = $arrayFailingAutotestsConfCountLastBuilds[$j];
                        }
                        break;                                                  // Match found, skip the rest
                    }
                }
            }
            $k++;
        }
        $_SESSION['arrayFailingAutotestsConfCountLastBuilds'] = $arrayFailingAutotestsConfCountLastBuilds;
        $_SESSION['arrayFailingAutotestsConfNamesLastBuilds'] = $arrayFailingAutotestsConfNamesLastBuilds;

        if ($useMysqli)
            mysqli_free_result($result);                                        // Free result set

        /* Print list of Autotests */
        $k = 0;
        for ($countOrder=$maxCount; $countOrder>1; $countOrder--) {             // Order the list by looping from the highest count (skip the ones where count is 1)
            for ($i=0; $i<$autotestCount; $i++) {                               // Loop the Autotests
                if ($arrayFailingAutotestsConfCountLastBuilds[$i] == $countOrder) {
                    if ($k % 2 == 0)
                        echo '<tr>';
                    else
                        echo '<tr class="tableBackgroundColored">';

                    /* Autotest name */
                    $parameter = $arrayFailingAutotests[$i];
                    // Problem: Problem: Parameter passing (with GET method in URL) does not work with text 'ftp' or 'http' (autotest names 'tst_qftp' and 'tst_qhttp')
                    // -> parameter is here 'encoded' and then 'decoded' when receiving (at the top of this file)
                    $parameter = str_replace("ftp","ft-endoded-p",$parameter);
                    $parameter = str_replace("http","htt-endoded-p",$parameter);
                    echo '<td><a href="javascript:void(0);" onclick="filterAutotest(\'' . $parameter . '\')">' . $arrayFailingAutotests[$i] . '</a></td>';

                    /* Counts in latest Build (with names as a popup) */
                    if ($arrayFailingAutotestsProjectCountLatestBuild[$i] > 0)
                        echo '<td class="tableCellCentered"><span class="popupMessage">' . $arrayFailingAutotestsProjectCountLatestBuild[$i]
                            . '<span><b>' . $arrayFailingAutotests[$i] . ':</b><br>' . substr($arrayFailingAutotestsProjectNamesLatestBuild[$i],strlen('<br>'))
                            . '</span></span></td>';                            // Skip leading '<br>' set above
                    else
                        echo '<td class="tableCellCentered">-</td>';
                    if ($arrayFailingAutotestsConfCountLatestBuild[$i] > 0)
                        echo '<td class="tableCellCentered"><span class="popupMessage">' . $arrayFailingAutotestsConfCountLatestBuild[$i]
                            . '<span><b>' . $arrayFailingAutotests[$i] . ':</b><br>' . substr($arrayFailingAutotestsConfNamesLatestBuild[$i],strlen('<br>'))
                            . '</span></span></td>';                            // Skip leading '<br>' set above
                    else
                        echo '<td class="tableCellCentered">-</td>';

                    /* Counts in last x Builds (with names as a popup) */
                    if ($arrayFailingAutotestsProjectCountLastBuilds[$i] > 0)
                        echo '<td class="tableCellCentered"><span class="popupMessage">' . $arrayFailingAutotestsProjectCountLastBuilds[$i]
                            . '<span><b>' . $arrayFailingAutotests[$i] . ':</b><br>' . substr($arrayFailingAutotestsProjectNamesLastBuilds[$i],strlen('<br>'))
                            . '</span></span></td>';                            // Skip leading '<br>' set above
                    else
                        echo '<td class="tableCellCentered">-</td>';
                    if ($arrayFailingAutotestsConfCountLastBuilds[$i] > 0)
                        echo '<td class="tableCellCentered"><span class="popupMessage">' . $arrayFailingAutotestsConfCountLastBuilds[$i]
                            . '<span><b>' . $arrayFailingAutotests[$i] . ':</b><br>' . substr($arrayFailingAutotestsConfNamesLastBuilds[$i],strlen('<br>'))
                            . '</span></span></td>';                            // Skip leading '<br>' set above
                    else
                        echo '<td class="tableCellCentered">-</td>';

                    /* Counts in a week (with names as a popup) */
                    echo '<td class="tableCellCentered">(n/a)</td>';            // Project count in a week; Data not yet available in db
                    echo '<td class="tableCellCentered">(n/a)</td>';            // Configuration count in a week; Data not yet available in db

                    /* Counts in a month (with names as a popup) */
                    echo '<td class="tableCellCentered">(n/a)</td>';            // Project count in a month; Data not yet available in db
                    echo '<td class="tableCellCentered">(n/a)</td>';            // Configuration count in a month; Data not yet available in db

                    echo '</tr>';
                    $k++;
                }
                if ($k > 12 AND !isset($_SESSION['failingAutotestsShowFullList'])) {                                      // List cut mode: By default show only n items in the list to leave room for possible other metrics boxes
                    break;
                }
            }
        }
        echo '</table>';

        if (!isset($_SESSION['failingAutotestsShowFullList'])) {
            echo '<br/><a href="javascript:void(0);" onclick="filterAutotest(\'All\')">Show full list...</a><br/><br/>';  // List cut mode: If only first n items shown, add a link to see all
            $_SESSION['failingAutotestsShowFullList'] = TRUE;                                                             // List cut mode: After refreshing the metrics box, show all items instead (set below to return the default 'cut mode')
        }

    } else {
        echo '<br/>Filter values not ready, please <a href="javascript:void(0);" onclick="reloadFilters()">reload</a> ...';
    }
}

/*************************************************************/
/* NESTED LEVEL 2: Autotest filtered                         */
/*************************************************************/

if ($autotest <> "All") {
    echo '<b>MOST COMMONLY FAILING INSIGNIFICANT AUTOTESTS:</b> <a href="javascript:void(0);" onclick="filterAutotest(\'All\')">Select Autotest</a> -> ' . $autotest . '<br/><br/>';
    if(isset($_SESSION['arrayAutotestName'])) {
        $i = 0;
        foreach($_SESSION['arrayAutotestName'] as $key => $value) {
            if ($autotest == $value) {
                echo '<table>';
                /* Autotest name */
                echo '<tr><td>Autotest: </td><td class="tableCellBackgroundTitle">' . $autotest . '</td></tr>';

                /* Counts and names in Project Builds */
                echo '<tr>';
                $count = $_SESSION['arrayFailingAutotestsProjectCountLatestBuild'][$i];
                if ($count == 0 OR $count == "")
                    echo '<td>Failures in Project builds:</td>
                          <td>Not in any Projects in their latest build</td>';
                else
                    echo '<td>Failures in Project builds:</td>
                          <td>In ' . $count . ' Projects in their latest build:</td>';
                echo '</tr>';
                echo '<tr>';
                echo '<td></td>';
                echo '<td class="fontColorGrey">' . substr($_SESSION['arrayFailingAutotestsProjectNamesLatestBuild'][$i],strlen('<br>')) . '</td>';
                echo '</tr>';
                echo '<tr>';
                $count = $_SESSION['arrayFailingAutotestsProjectCountLastBuilds'][$i];
                if ($count == 0 OR $count == "")
                    echo '<td></td>
                          <td>Not  in any Projects in their last ' . AUTOTEST_LATESTBUILDCOUNT . ' builds</td>';
                else
                    echo '<td></td>
                          <td>In ' . $count . ' Projects in their last ' . AUTOTEST_LATESTBUILDCOUNT . ' builds:</td>';
                echo '</tr>';
                echo '<tr>';
                echo '<td></td>';
                echo '<td class="fontColorGrey">' . substr($_SESSION['arrayFailingAutotestsProjectNamesLastBuilds'][$i],strlen('<br>')) . '</td>';
                echo '</tr>';

                /* Counts and names in Configuration Builds */
                echo '<tr>';
                $count = $_SESSION['arrayFailingAutotestsConfCountLatestBuild'][$i];
                if ($count == 0 OR $count == "")
                    echo '<td>Failures in Conf builds:</td>
                          <td>Not in any Confs in their latest build</td>';
                else
                    echo '<td>Failures in Conf builds:</td>
                          <td>In ' . $count . ' Confs in their latest build:</td>';
                echo '</tr>';
                echo '<tr>';
                echo '<td></td>';
                echo '<td class="fontColorGrey">' . substr($_SESSION['arrayFailingAutotestsConfNamesLatestBuild'][$i],strlen('<br>')) . '</td>';
                echo '</tr>';
                echo '<tr>';
                $count = $_SESSION['arrayFailingAutotestsConfCountLastBuilds'][$i];
                if ($count == 0 OR $count == "")
                    echo '<td></td>
                          <td>Not  in any Confs in their last ' . AUTOTEST_LATESTBUILDCOUNT . ' builds</td>';
                else
                    echo '<td></td>
                          <td>In ' . $count . ' Confs in their last ' . AUTOTEST_LATESTBUILDCOUNT . ' builds:</td>';
                echo '</tr>';
                echo '<tr>';
                echo '<td></td>';
                echo '<td class="fontColorGrey">' . substr($_SESSION['arrayFailingAutotestsConfNamesLastBuilds'][$i],strlen('<br>')) . '</td>';
                echo '</tr>';

                echo '</table><br/>';
                break;                                                  // Match found, skip the rest
            }
            $i++;
        }
    } else {
        echo '<br/>Filter values not ready, please <a href="javascript:void(0);" onclick="reloadFilters()">reload</a> ...';
    }
}

/* Close connection to the server */
require(__DIR__.'/../connectionclose.php');

?>