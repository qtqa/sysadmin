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

// Read values from database to session variables (so these are updated only once per session)
$timeStart = microtime(true);
include "getprojectvalues.php";
$timeProjectValues = microtime(true);
include "getconfvalues.php";
$timeConfValues = microtime(true);
include "getautotestvalues.php";
$timeAutotestValues = microtime(true);
?>

<div id="filterTitle">
<b>FILTERS:</b>
</div>

<div id="filterButtons">
<button onclick="clearFilters()">Clear selections</button>
<button onclick="reloadFilters()">Reload</button>
</div>

<div id="filterFields">

<div id="filterFieldsLeft">
<form name="form">
<label>Project:</label>
<select name="project" id="project" onchange="filterProject(this.value)">
<?php
    echo "<option value=\"All\">All</option>";
    foreach ($_SESSION['arrayProjectName'] as $key=>$value)
        echo "<option value=\"$value\">$value</option>";
?>
</select>
<br/>
<label>Configuration:</label>
<select name="conf" id="conf" onchange="filterConf(this.value)">
<?php
    echo "<option value=\"All\">All</option>";
    foreach ($_SESSION['arrayConfName'] as $key=>$value)
        echo "<option value=\"$value\">$value</option>";
?>
</select>
<br/>
<label>Autotest:</label>
<select name="autotest" id="autotest" onchange="filterAutotest(this.value)">
<?php
    echo "<option value=\"All\">All</option>";
    foreach ($_SESSION['arrayAutotestName'] as $key=>$value)
        echo "<option value=\"$value\">$value</option>";
?>
</select>
<input id="autotestSortBy" type="hidden" value="0">
</div>
<div id="filterFieldsMiddle">
<label>Timescale:</label>
<select name="timescale" id="timescale" onchange="filterTimescale(this.value)">
    <option value="All">All</option>
    <option value="Since">Since a date</option>
</select>
</div>
</form>

<div id="filterFieldsRight">
<form name="date" id="date">
<label>Since date:</label>
<?php
/* Date picker calendar from http://www.triconsole.com/php/calendar_datepicker.php */
require_once(__DIR__.'/../calendar/classes/tc_calendar.php');         // Get class into the page
$myCalendar = new tc_calendar("since", true, false);                  // Instantiate class and set properties ("since" = element id, 'true' + 'false' = date picker with no input box)
    $myCalendar->setIcon('calendar/images/iconCalendar.gif');         // (directory under the directory of this Ajax file; this is why the image directory exists here)
    $myCalendar->setDate(substr($_SESSION['maxBuildDate'], 8, 2), substr($_SESSION['maxBuildDate'], 5, 2), substr($_SESSION['maxBuildDate'], 0, 4));   // Last build date as a default value
    $myCalendar->setPath('calendar/');                                // (relative from the main directory)
    $myCalendar->setYearInterval(2012, 2015);
    $myCalendar->dateAllow($_SESSION['minBuildDate'], $_SESSION['maxBuildDate']);  // Allows only the build dates that are available in the database
    $myCalendar->setDateFormat('Y-m-d');
    $myCalendar->showWeeks(true);
    $myCalendar->startDate(1);
    $myCalendar->setAlignment('left', 'bottom');
    $myCalendar->setOnChange('filterTimescale("Since")');
    $myCalendar->writeScript();                                       // Write the calendar to the screen
?>
</div>
</form>

</div>

<?php
/* Elapsed time */
if ($showElapsedTime) {
    $timeEnd = microtime(true);
    $time1 = round($timeProjectValues - $timeStart, 4);
    $time11 = round($timeProjectValuesStep1 - $timeStart, 4);
    $time12 = round($timeProjectValuesStep2 - $timeProjectValuesStep1, 4);
    $time13 = round($timeProjectValuesStep3 - $timeProjectValuesStep2, 4);
    $time14 = round($timeProjectValuesStep4 - $timeProjectValuesStep3, 4);
    $time15 = round($timeProjectValuesStep5 - $timeProjectValuesStep4, 4);
    $time2 = round($timeConfValues - $timeProjectValues, 4);
    $time3 = round($timeAutotestValues - $timeConfValues, 4);
    $time = round($timeEnd - $timeStart, 4);
    echo "<div class=\"elapdedTime\">";
    echo "<ul><li>";
    echo "Total time: $time s (project values: $time1 s ($time11 + $time12 + $time13 + $time14 + $time15), conf values: $time2 s, autotest values: $time3 s)";
    echo "</li></ul>";
    echo "</div>";
}
?>
