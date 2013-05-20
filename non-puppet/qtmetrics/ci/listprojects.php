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

/* Following 'input' variabes must be set prior to including this file */
    // All Project session variables: $_SESSION['arrayProject...']

$i = 0;
echo '<table class="fontSmall">';

/* Titles */
echo '<tr>';
echo '<th></th>';
echo '<th colspan="8" class="tableBottomBorder tableSideBorder">LATEST BUILD</th>';
echo '<th colspan="3" class="tableBottomBorder tableSideBorder">ALL BUILDS</th>';
echo '</tr>';
echo '<tr>';
echo '<th></th>';
echo '<th colspan="3" class="tableBottomBorder tableSideBorder">Build Info</th>';
echo '<th colspan="2" class="tableBottomBorder tableSideBorder">Amount of Failed Autotests</th>';
echo '<th colspan="3" class="tableBottomBorder tableSideBorder">Amount of Configurations</th>';
echo '<th colspan="3" class="tableBottomBorder tableSideBorder">Amount of Builds</th>';
echo '</tr>';
echo '<tr class="tableBottomBorder">';
echo '<td></td>';
echo '<td class="tableLeftBorder tableCellCentered">ID</td>';
echo '<td class="tableCellCentered">Result</td>';
echo '<td class="tableCellCentered">Date</td>';
echo '<td class="tableLeftBorder tableCellCentered">Significant</td>';
echo '<td class="tableCellCentered">Insignificant</td>';
echo '<td class="tableLeftBorder tableCellCentered">Force success</td>';
echo '<td class="tableCellCentered">Insignificant</td>';
echo '<td class="tableCellCentered">Total</td>';
echo '<td class="tableLeftBorder tableCellCentered">Failed</td>';
echo '<td class="tableCellCentered">Successed</td>';
echo '<td class="tableRightBorder tableCellCentered">Total</td>';
echo '</tr>';
foreach ($_SESSION['arrayProjectName'] as $key=>$value) {
    if ($i % 2 == 0)
        echo '<tr>';
    else
        echo '<tr class="tableBackgroundColored">';

    /* Project name */
    echo '<td><a href="javascript:void(0);" onclick="filterProject(\'' . $value . '\')">' . $value . '</a></td>';

    /* Latest Build number and result */
    echo '<td class="tableLeftBorder">' . $_SESSION['arrayProjectBuildLatest'][$key] . '</td>';
    $fontColorClass = "fontColorBlack";
    if ($_SESSION['arrayProjectBuildLatestResult'][$key] == "SUCCESS")
        $fontColorClass = "fontColorGreen";
    if ($_SESSION['arrayProjectBuildLatestResult'][$key] == "FAILURE")
        $fontColorClass = "fontColorRed";
    echo '<td class="' . $fontColorClass . '">' . $_SESSION['arrayProjectBuildLatestResult'][$key] . '</td>';
    $date = strstr($_SESSION['arrayProjectBuildLatestTimestamp'][$key], ' ', TRUE);
    echo '<td>' . $date . '</td>';

    /* Number of failed significant/insignificant autotests */
    $count = $_SESSION['arrayProjectBuildLatestSignificantCount'][$key];
    if ($count > 0)
        echo '<td class="tableLeftBorder tableCellCentered">' . $count . '</td>';
    else
        echo '<td class="tableLeftBorder tableCellCentered">-</td>';
    $count = $_SESSION['arrayProjectBuildLatestInsignificantCount'][$key];
    if ($count > 0)
        echo '<td class="tableCellCentered">' . $count . '</td>';
    else
        echo '<td class="tableCellCentered">-</td>';

    /* Force success and insignificant Configurations vs. All Configurations */
    $count = $_SESSION['arrayProjectBuildLatestConfCountForceSuccess'][$key];
    if ($count > 0)
        echo '<td class="tableLeftBorder tableCellCentered">' . $count . '</td>';
    else
        echo '<td class="tableLeftBorder tableCellCentered">-</td>';
    $count = $_SESSION['arrayProjectBuildLatestConfCountInsignificant'][$key];
    if ($count > 0)
        echo '<td class="tableCellCentered">' . $count . '</td>';
    else
        echo '<td class="tableCellCentered">-</td>';
    $count = $_SESSION['arrayProjectBuildLatestConfCount'][$key];
    if ($count > 0)
        echo '<td class="tableCellCentered">' . $count . '</td>';
    else
        echo '<td class="tableCellCentered">-</td>';

    /* Build statistics */
    $count = $_SESSION['arrayProjectBuildCountFailure'][$key];
    if ($count > 0)
        echo '<td class="tableLeftBorder tableCellAlignRight">' . $count . ' (' . round(100*$count/$_SESSION['arrayProjectBuildCount'][$key],0) . '%)' . '</td>';
    else
        echo '<td class="tableLeftBorder tableCellCentered">-</td>';
    $count = $_SESSION['arrayProjectBuildCountSuccess'][$key];
    if ($count > 0)
        echo '<td class="tableCellAlignRight">' . $count . ' (' . round(100*$count/$_SESSION['arrayProjectBuildCount'][$key],0) . '%)' . '</td>';
    else
        echo '<td class="tableCellCentered">-</td>';
    $count = $_SESSION['arrayProjectBuildCount'][$key];
    if ($count > 0)
        echo '<td class="tableRightBorder tableCellAlignRight">' . $count . '</td>';
    else
        echo '<td class="tableRightBorder tableCellCentered">-</td>';

    echo "</tr>";
    $i++;
    if ($i > 12 AND !isset($_SESSION['projectDashboardShowFullList'])) {                                         // List cut mode: By default show only n items in the list to leave room for possible other metrics boxes
        break;
    }
}
echo "</table>";
if (!isset($_SESSION['projectDashboardShowFullList'])) {
    echo '<br/><a href="javascript:void(0);" onclick="clearProjectFilters()">Show full list...</a><br/><br/>';   // List cut mode: If only first n items shown, add a link to see all
    $_SESSION['projectDashboardShowFullList'] = TRUE;                                                            // List cut mode: After refreshing the metrics box, show all items instead (set below to return the default 'cut mode')
}

?>