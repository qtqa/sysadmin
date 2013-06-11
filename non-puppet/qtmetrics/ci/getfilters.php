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
include "getprojectvalues.php";
include "getconfvalues.php";
include "getautotestvalues.php";
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
</div>

<div id="filterFieldsRight">
</div>
</form>

</div>
