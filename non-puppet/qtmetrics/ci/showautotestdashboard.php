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
// -> parameter is here 'returned' to its original value
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

/* Check the latest Build number for the Project */
if ($project <> "All") {
    foreach($_SESSION['arrayProjectName'] as $projectKey => $projectValue) {
        if ($project == $projectValue)
            $latestProjectBuild = $_SESSION['arrayProjectBuildLatest'][$projectKey];
    }
}

/*************************************************************/
/* NESTED LEVEL 1: No autotest filtering done (default view) */
/*************************************************************/

if ($autotest == "All") {
    echo '<b>AUTOTEST DASHBOARD:</b> Select Autotest<br/><br/>';
    if(isset($_SESSION['arrayAutotestName'])) {

        /* Get all (failing) Autotest names and required Project data */
        $arrayFailingAutotestNames = array();
        $arrayFailingAutotestNames = $_SESSION['arrayAutotestName'];
        $autotestCount = 0;
        foreach($arrayFailingAutotestNames as $key => $value) {
            $autotestCount++;
        }
        $arrayProjectName = $_SESSION['arrayProjectName'];
        $arrayProjectBuildLatest = $_SESSION['arrayProjectBuildLatest'];

        /* Get number and names Configurations for each Autotest in latest Build (categorised as significant/insignificant) */
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

        $maxCount = 0;                                                                // Max count of Autotests in any category (used for sorting the lists)
        $totalCount = 0;                                                              // Total count of Autotests in any category (used to identify if any was found)
        $projectCounter = 0;
        foreach($_SESSION['arrayProjectName'] as $projectKey => $projectValue) {      // Check Projects first to identify latest Build number (different for each Project)

            /* When Project filtered, show only the related Project data */
            if ($project <> "All")
                if ($projectValue <> $project) {
                    $projectCounter++;
                    continue;                                                         // Skip to the next Project (in the foreach loop)
                }

            /* Read Configurations for Project(s) */
            $sql = "SELECT cfg, insignificant
                    FROM cfg
                    WHERE project=\"$arrayProjectName[$projectCounter]\"
                        AND build_number=$arrayProjectBuildLatest[$projectCounter]";  // Find Configurations for the Project
            $dbColumnCfgCfg = 0;
            $dbColumnCfgInsignificant = 1;
            if ($useMysqli) {
                $result = mysqli_query($conn, $sql);
                $numberOfRows = mysqli_num_rows($result);
            } else {
                $selectdb="USE $db";
                $result = mysql_query($selectdb) or die (mysql_error());
                $result = mysql_query($sql) or die (mysql_error());
                $numberOfRows = mysql_num_rows($result);
            }
            for ($i=0; $i<$numberOfRows; $i++) {                                      // Loop the Configurations (in Project)
                if ($useMysqli)
                    $resultRow = mysqli_fetch_row($result);
                else
                    $resultRow = mysql_fetch_row($result);
                $confName = $resultRow[$dbColumnCfgCfg];

                /* When Configuration filtered, show only the related Configuration data */
                if ($conf <> "All")
                    if ($confName <> $conf) {
                        continue;                                                     // Skip to the next Configuration (in the for loop)
                    }

                /* Read Autotests for Configuration(s) */
                $sql = "SELECT name, insignificant
                        FROM test
                        WHERE project=\"$arrayProjectName[$projectCounter]\"
                            AND build_number=$arrayProjectBuildLatest[$projectCounter]
                            AND cfg=\"$confName\"";                                   // Find the Autotests for each Configuration
                $dbColumnTestName = 0;
                $dbColumnTestInsignificant = 1;
                if ($useMysqli) {
                    $result2 = mysqli_query($conn, $sql);
                    $numberOfRows2 = mysqli_num_rows($result2);
                } else {
                    $selectdb="USE $db";
                    $result2 = mysql_query($selectdb) or die (mysql_error());
                    $result2 = mysql_query($sql) or die (mysql_error());
                    $numberOfRows2 = mysql_num_rows($result2);
                }
                for ($j=0; $j<$numberOfRows2; $j++) {                                 // Loop the Autotests (in Project Configuration)
                    if ($useMysqli)
                        $resultRow2 = mysqli_fetch_row($result2);
                    else
                        $resultRow2 = mysql_fetch_row($result2);
                    if ($resultRow[$dbColumnCfgInsignificant] == 0) {                 // Check the Autotest failing category
                        if ($resultRow2[$dbColumnTestInsignificant] == 0) {
                            $autotestFailureCategory = SIGNAUTOTESTBLOCKINGCONF;
                        } else {
                            $autotestFailureCategory = INSIGNAUTOTESTBLOCKINGCONF;
                        }
                    } else {
                        if ($resultRow2[$dbColumnTestInsignificant] == 0) {
                            $autotestFailureCategory = SIGNAUTOTESTINSIGNCONF;
                        } else {
                            $autotestFailureCategory = INSIGNAUTOTESTINSIGNCONF;
                        }
                    }
                    for ($k=0; $k<$autotestCount; $k++) {                             // Loop all the Autotests to find the ones for this Project Configuration
                        if ($arrayFailingAutotestNames[$k] == $resultRow2[$dbColumnTestName]) {
                            switch ($autotestFailureCategory) {
                                case SIGNAUTOTESTBLOCKINGCONF:
                                    if (!strpos($arrayFailingSignAutotestBlockingConfNames[$k],$resultRow[$dbColumnCfgCfg])) {   // Each Conf to be listed only once
                                        $arrayFailingSignAutotestBlockingConfCount[$k]++;
                                        $totalCount++;
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
                                        $totalCount++;
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
                                        $totalCount++;
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
                                        $totalCount++;
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
                            break;                                                    // Match found, skip the rest
                        }
                    }            // Endfor all Autotests
                }                // Endfor Autotests in Project Configuration
            }                    // Endfor Configurations in project
            $projectCounter++;
        }                        // Endfor Projects

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
            mysqli_free_result($result);                                        // Free result set
            mysqli_free_result($result2);                                       // Free result set
        }

        /* Print Project and Configuration, if filtered */
        if ($project <> "All" OR $conf <> "All") {
            echo '<table>';
            if ($project <> "All")
                echo '<tr><td>Project:</td><td class="tableCellBackgroundTitle">' . $project . '</td></tr>';
            if ($conf <> "All")
                echo '<tr><td>Configuration:</td><td class="tableCellBackgroundTitle">' . $conf . '</td></tr>';
            if ($project <> "All")
                echo '<tr><td>Latest Build:</td><td class="tableCellBackgroundTitle">' . $latestProjectBuild . '</td></tr>';
            if ($totalCount == 0) {
                echo '<tr><td><br></td></tr>';                                  // Empty row
                echo '<tr><td></td><td>Not any Failed Autotests</td></tr>';
            }
            echo '</table>';
            echo '<br>';
        }

        if ($totalCount > 0) {                                                  // List only if something to list (info of 'not any' printed above)

            /* Print the titles */
            echo '<table class="fontSmall">';
            echo '<tr>';
            echo '<th></th>';
            echo '<th colspan="4" class="tableBottomBorder tableSideBorder">LATEST BUILD BY FAILURE CATEGORY</th>';
            echo '</tr>';
            echo '<tr>';
            echo '<th></th>';
            echo '<th colspan="2" class="tableBottomBorder tableSideBorder">Failed Significant Autotests</th>';
            echo '<th colspan="2" class="tableBottomBorder tableSideBorder">Failed Insignificant Autotests</th>';
            echo '</tr>';
            echo '<tr class="tableBottomBorder">';
            echo '<td class="tableCellAlignRight">
                  <span class="popupMessageImg"><img src="images/info.png" alt="info">
                      <span><b>FAILURE CATEGORY DESCRIPTIONS:</b><br><br>
                            <b>1) Failed Significant Autotests in Blocking CI Configurations</b><br><br>
                               These autotests or code under test should be fixed to make CI more stable and to improve CI throughput.<br><br>
                            <b>2) Failed Significant Autotests in Insignificant CI Configurations</b><br><br>
                               These autotests or code under test should be fixed; or the failed autotests should be marked individually
                               insignificant for relevant configurations to be able to improve CI coverage.<br><br>
                            <b>3) Failed Insignificant Autotests in Blocking CI Configurations</b><br><br>
                               These autotests or code under test should be fixed to improve CI coverage.<br><br>
                            <b>4) Failed Insignificant Autotests in Insignificant CI Configurations</b><br><br>
                               You should first aim to make the CI configuration blocking by fixing 2) and then 3).<br>&nbsp
                      </span>
                  </span></td>';
            echo '<td class="tableLeftBorder tableCellCentered">1) Blocking<br>Confs</td>';
            echo '<td class="tableCellCentered">2) Insignificant<br>Confs</td>';
            echo '<td class="tableLeftBorder tableCellCentered">3) Blocking<br>Confs</td>';
            echo '<td class="tableRightBorder tableCellCentered">4) Insignificant<br>Confs</td>';
            echo '</tr>';

            /* Print list of Autotests */
            $k = 0;
            for ($countOrder=$maxCount; $countOrder>=0; $countOrder--) {                   // Sort the list by looping from the highest count
                for ($i=0; $i<$autotestCount; $i++) {                                      // Loop the Autotests
                    if ($arrayFailingSignAutotestBlockingConfCount[$i] == $countOrder) {   // Fixed sorting based on significant Autotests in blocking Configuration
                        if ($arrayFailingSignAutotestBlockingConfCount[$i]
                            + $arrayFailingSignAutotestInsignConfCount[$i]
                            + $arrayFailingInsignAutotestBlockingConfCount[$i]
                            + $arrayFailingInsignAutotestInsignConfCount[$i] > 0) {        // Skip if not any failures
                            if ($k % 2 == 0)
                                echo '<tr>';
                            else
                                echo '<tr class="tableBackgroundColored">';

                            /* Autotest name */
                            $parameter = $arrayFailingAutotestNames[$i];
                            // Problem: Problem: Parameter passing (with GET method in URL) does not work with text 'ftp' or 'http' (autotest names 'tst_qftp' and 'tst_qhttp')
                            // -> parameter is here 'encoded' and then 'decoded' when receiving (at the top of this file)
                            $parameter = str_replace("ftp","ft-endoded-p",$parameter);
                            $parameter = str_replace("http","htt-endoded-p",$parameter);
                            echo '<td><a href="javascript:void(0);" onclick="filterAutotest(\'' . $parameter . '\')">' . $arrayFailingAutotestNames[$i] . '</a></td>';

                            /* Significant Autotests in blocking Configuration (with names as a popup) */
                            if ($arrayFailingSignAutotestBlockingConfCount[$i] > 0)
                                echo '<td class="tableLeftBorder tableCellCentered"><span class="popupMessage">'
                                    . $arrayFailingSignAutotestBlockingConfCount[$i]
                                    . '<span><b>' . $arrayFailingAutotestNames[$i] . ':</b><br>'
                                    . substr($arrayFailingSignAutotestBlockingConfNames[$i],strlen('<br>'))
                                    . '</span></span></td>';                            // Skip leading '<br>' set above
                            else
                                echo '<td class="tableLeftBorder tableCellCentered">-</td>';

                            /* Significant Autotests in insignificant Configuration (with names as a popup) */
                            if ($arrayFailingSignAutotestInsignConfCount[$i] > 0)
                                echo '<td class="tableCellCentered"><span class="popupMessage">' . $arrayFailingSignAutotestInsignConfCount[$i]
                                    . '<span><b>' . $arrayFailingAutotestNames[$i] . ':</b><br>' . substr($arrayFailingSignAutotestInsignConfNames[$i],strlen('<br>'))
                                    . '</span></span></td>';                            // Skip leading '<br>' set above
                            else
                                echo '<td class="tableCellCentered">-</td>';

                            /* Insignificant Autotests in blocking Configuration (with names as a popup) */
                            if ($arrayFailingInsignAutotestBlockingConfCount[$i] > 0)
                                echo '<td class="tableLeftBorder tableCellCentered"><span class="popupMessage">' . $arrayFailingInsignAutotestBlockingConfCount[$i]
                                    . '<span><b>' . $arrayFailingAutotestNames[$i] . ':</b><br>' . substr($arrayFailingInsignAutotestBlockingConfNames[$i],strlen('<br>'))
                                    . '</span></span></td>';                            // Skip leading '<br>' set above
                            else
                                echo '<td class="tableLeftBorder tableCellCentered">-</td>';

                            /* Insignificant Autotests in insignificant Configuration (with names as a popup) */
                            if ($arrayFailingInsignAutotestInsignConfCount[$i] > 0)
                                echo '<td class="tableRightBorder tableCellCentered"><span class="popupMessage">' . $arrayFailingInsignAutotestInsignConfCount[$i]
                                    . '<span><b>' . $arrayFailingAutotestNames[$i] . ':</b><br>' . substr($arrayFailingInsignAutotestInsignConfNames[$i],strlen('<br>'))
                                    . '</span></span></td>';                            // Skip leading '<br>' set above
                            else
                                echo '<td class="tableRightBorder tableCellCentered">-</td>';

                            echo '</tr>';
                            $k++;
                        }
                        if ($k > 12 AND !isset($_SESSION['failingAutotestsShowFullList'])) {                                      // List cut mode: By default show only n items in the list to leave room for possible other metrics boxes
                            break;
                        }
                    }         // Endif sorting order
                }             // Endfor Autotests
            }                 // Endfor sorting
            echo '</table>';
        }                     // Endif $totalCount

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
    echo '<b>AUTOTEST DASHBOARD:</b> <a href="javascript:void(0);" onclick="filterAutotest(\'All\')">Select Autotest</a> -> ' . $autotest . '<br/><br/>';
    if(isset($_SESSION['arrayAutotestName'])) {
        $i = 0;
        foreach($_SESSION['arrayAutotestName'] as $key => $value) {
            if ($autotest == $value) {
                echo '<table>';

                /* Autotest name, and Project and Configuration (if filtered) */
                echo '<tr><td>Autotest: </td><td class="tableCellBackgroundTitle">' . $autotest . '</td></tr>';
                if ($project <> "All")
                    echo '<tr><td>Project: </td><td class="tableCellBackgroundTitle">' . $project . '</td></tr>';
                if ($conf <> "All")
                    echo '<tr><td>Configuration: </td><td class="tableCellBackgroundTitle">' . $conf . '</td></tr>';
                if ($project <> "All")
                    echo '<tr><td>Latest Build:</td><td class="tableCellBackgroundTitle">' . $latestProjectBuild . '</td></tr>';
                echo '<tr><td><br></td></tr>';                                  // Empty row

                echo '<tr><td colspan="3"><b>Projects and Configurations (their latest Build) by failure category</b></td></tr>';
                echo '<tr><td><br></td></tr>';                                  // Empty row

                /* Significant Autotests in blocking Configuration */
                echo '<tr>';
                $count = $_SESSION['arrayFailingSignAutotestBlockingConfCount'][$i];
                if ($count == 0 OR $count == "")
                    echo '<td>Significant Failures:</td>
                          <td>Not in any Blocking Configurations</td>';
                else
                    echo '<td>Significant Failures:</td>
                          <td>In ' . $count . ' Blocking Configurations:</td>
                          <td>In Projects:</td>';
                echo '</tr>';
                echo '<tr>';
                echo '<td></td>';
                echo '<td class="fontColorGrey">' . substr($_SESSION['arrayFailingSignAutotestBlockingConfNames'][$i],strlen('<br>')) . '</td>';
                echo '<td class="fontColorGrey">' . substr($_SESSION['arrayFailingSignAutotestBlockingConfProjects'][$i],strlen('<br>')) . '</td>';
                echo '</tr>';

                /* Significant Autotests in insignificant Configuration (with names as a popup) */
                echo '<tr>';
                $count = $_SESSION['arrayFailingSignAutotestInsignConfCount'][$i];
                if ($count == 0 OR $count == "")
                    echo '<td></td>
                          <td>Not in any Insignificant Configurations</td>';
                else
                    echo '<td></td>
                          <td>In ' . $count . ' Insignificant Configurations:</td>
                          <td>In Projects:</td>';
                echo '</tr>';
                echo '<tr>';
                echo '<td></td>';
                echo '<td class="fontColorGrey">' . substr($_SESSION['arrayFailingSignAutotestInsignConfNames'][$i],strlen('<br>')) . '</td>';
                echo '<td class="fontColorGrey">' . substr($_SESSION['arrayFailingSignAutotestInsignConfProjects'][$i],strlen('<br>')) . '</td>';
                echo '</tr>';
                echo '<tr><td><br></td></tr>';                                  // Empty row

                /* Insignificant Autotests in blocking Configuration (with names as a popup) */
                echo '<tr>';
                $count = $_SESSION['arrayFailingInsignAutotestBlockingConfCount'][$i];
                if ($count == 0 OR $count == "")
                    echo '<td>Insignificant Failures:</td>
                          <td>Not in any Blocking Configurations</td>';
                else
                    echo '<td>Insignificant Failures:</td>
                          <td>In ' . $count . ' Blocking Configurations:</td>
                          <td>In Projects:</td>';
                echo '</tr>';
                echo '<tr>';
                echo '<td></td>';
                echo '<td class="fontColorGrey">' . substr($_SESSION['arrayFailingInsignAutotestBlockingConfNames'][$i],strlen('<br>')) . '</td>';
                echo '<td class="fontColorGrey">' . substr($_SESSION['arrayFailingInsignAutotestBlockingConfProjects'][$i],strlen('<br>')) . '</td>';
                echo '</tr>';

                /* Insignificant Autotests in insignificant Configuration (with names as a popup) */
                echo '<tr>';
                $count = $_SESSION['arrayFailingInsignAutotestInsignConfCount'][$i];
                if ($count == 0 OR $count == "")
                    echo '<td></td>
                          <td>Not in any Insignificant Configurations</td>';
                else
                    echo '<td></td>
                          <td>In ' . $count . ' Insignificant Configurations:</td>
                          <td>In Projects:</td>';
                echo '</tr>';
                echo '<tr>';
                echo '<td></td>';
                echo '<td class="fontColorGrey">' . substr($_SESSION['arrayFailingInsignAutotestInsignConfNames'][$i],strlen('<br>')) . '</td>';
                echo '<td class="fontColorGrey">' . substr($_SESSION['arrayFailingInsignAutotestInsignConfProjects'][$i],strlen('<br>')) . '</td>';
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