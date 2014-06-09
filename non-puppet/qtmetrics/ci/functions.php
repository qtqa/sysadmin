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

/* Convert the numeric Build number to a 5 digit string needed for directory links (Example: http://testresults.qt-project.org/ci/Qt3D_master_Integration/build_00412) */
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

/* Clean the SQL statement with possible errors when combining several WHERE conditions */
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

?>