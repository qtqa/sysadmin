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
    // $_SESSION['arrayProjectName']
    // $_SESSION['arrayProjectBuildLatest']
    // $_SESSION['arrayProjectBuildLatestResult']

$i = 0;
echo '<table class="fontSmall">';
echo '<tr class="tableBottomBorder">';
echo '<th></th>';                                                       // Titles
echo '<th><br/><br/>Latest<br/>build</th>';
echo '<th><br/><br/>Latest<br/>result</th>';
echo '<th class="tableLeftBorder"><br/><br/>Failed<br/>Builds</th>';
echo '<th><br/><br/>Successed<br/>Builds</th>';
echo '<th><br/><br/>All<br/>Builds</th>';
echo '<th class="tableLeftBorder">Failed<br/>signif.<br/>autotests<br/>in latest</th>';
echo '<th>Failed<br/>insignif.<br/>autotests<br/>in latest</th>';
echo '<th class="tableLeftBorder">All<br/>insignif.<br/>autotests<br/>in latest</th>';
echo '<th><br/>All<br/>autotests<br/>in latest</th>';
echo '<th class="tableLeftBorder">Force<br/>success<br/>Confs<br/>in latest</th>';
echo '<th><br/>Insignif.<br/>Confs<br/>in latest</th>';
echo '<th><br/>All<br/>Confs<br/>in latest</th>';
echo '</tr>';
foreach ($_SESSION['arrayProjectName'] as $key=>$value) {
    if ($i % 2 == 0)
        echo '<tr>';
    else
        echo '<tr class="tableBackgroundColored">';

    /* Project name */
    echo '<td><a href="javascript:void(0);" onclick="filterProject(\'' . $value . '\')">' . $value . '</a></td>';

    /* Latest Build number and result */
    echo '<td>' . $_SESSION['arrayProjectBuildLatest'][$key] . '</td>';
    $fontColorClass = "fontColorBlack";
    if ($_SESSION['arrayProjectBuildLatestResult'][$key] == "SUCCESS")
        $fontColorClass = "fontColorGreen";
    if ($_SESSION['arrayProjectBuildLatestResult'][$key] == "FAILURE")
        $fontColorClass = "fontColorRed";
    echo '<td class="' . $fontColorClass . '">' . $_SESSION['arrayProjectBuildLatestResult'][$key] . '</td>';

    /* Build statistics */
    $count = $_SESSION['arrayProjectBuildCountFailure'][$key];
    if ($count > 0)
        echo '<td>' . $count . ' (' . round(100*$count/$_SESSION['arrayProjectBuildCount'][$key],0) . '%)' . '</td>';
    else
        echo '<td>-</td>';
    $count = $_SESSION['arrayProjectBuildCountSuccess'][$key];
    if ($count > 0)
        echo '<td>' . $count . ' (' . round(100*$count/$_SESSION['arrayProjectBuildCount'][$key],0) . '%)' . '</td>';
    else
        echo '<td>-</td>';
    $count = $_SESSION['arrayProjectBuildCount'][$key];
    if ($count > 0)
        echo '<td>' . $count . '</td>';
    else
        echo '<td>-</td>';

    /* Number of failed significant/insignificant autotests */
    $count = $_SESSION['arrayProjectBuildLatestSignificantCount'][$key];
    if ($count > 0)
        echo '<td class="tableCellCentered">' . $count . '</td>';
    else
        echo '<td class="tableCellCentered">-</td>';
    $count = $_SESSION['arrayProjectBuildLatestInsignificantCount'][$key];
    if ($count > 0)
        echo '<td class="tableCellCentered">' . $count . '</td>';
    else
        echo '<td class="tableCellCentered">-</td>';

    /* Insignificant autotests vs. All autotests */
    echo '<td class="tableCellCentered">(n/a)</td>';                    // All insignificant autotests in latest Build; Data not yet available in db
    echo '<td class="tableCellCentered">(n/a)</td>';                    // All autotests in latest Build; Data not yet available in db

    /* Force success and insignificant Configurations vs. All Configurations */
    echo '<td class="tableCellCentered">(n/a)</td>';                    // Force success Confs in latest Build; Data not yet available in db
    echo '<td class="tableCellCentered">(n/a)</td>';                    // Insignificant Confs in latest Build; Data not yet available in db
    $count = $_SESSION['arrayProjectBuildLatestConfCount'][$key];
    if ($count > 0)
        echo '<td class="tableCellCentered">' . $count . '</td>';
    else
        echo '<td class="tableCellCentered">-</td>';

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