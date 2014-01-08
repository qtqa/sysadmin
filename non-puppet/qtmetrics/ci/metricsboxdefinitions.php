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

/* Metrics boxes */
$arrayMetricsBoxes = array (
    // Metrics boxes will appear in the order defined below
    //
    // *) Possible values: All, project, conf, autotest, timescale
    //
    //        File path and name                Applied filters *)            Filters to clear when other applied ones change *)
    //        --------------------------------------------------------------------------------------------------------------------------
    array(    "ci/showprojectdashboard.php"    ,"project,conf,timescale"      ,""                       ),
    array(    "ci/showautotestdashboard.php"   ,"All"                         ,"autotest"               ),
);

/* Filters */
define("FILTERSEPARATOR", ";");
define("FILTERVALUESEPARATOR", ":");
define("FILTERPROJECT", 0);
define("FILTERCONF", 1);
define("FILTERAUTOTEST", 2);
define("FILTERTIMESCALETYPE", 3);
define("FILTERTIMESCALEVALUE", 4);
define("FILTERSORTBY", 5);

?>