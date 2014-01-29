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

/* The connectiondefinitions.php and commonfunctions.php are needed but they are included in the parent metrics page (metricspage***.php) instead */

/*  Main menu ($metricsPage identifies the active page, it is set in the metricspage***.php) */
echo '<div id="menu">';
echo '<ul class="mainMenu">';

/* This page is on public Qt Project server */
if (isPublicServer(PUBLICSERVER)) {
    // CI metrics
    if ($metricsPage == "metricspageci")
        echo '<li class="active"><a href="metricspageci.php">CI Metrics</a></li>';
    else
        echo '<li class="inactive"><a href="metricspageci.php">CI Metrics</a></li>';
    // RTA metrics (redirect to local server; show only if requested from internal Digia network)
    if (isInternalClient($internalIps))
        echo '<li class="inactive"><a href="metricspagerta.php">RTA Metrics</a></li>';
}

/* This page is on local Digia server */
else {
    // CI metrics (redirect to public server)
    echo '<li class="inactive"><a href="http://' . PUBLICSERVER . '/qtmetrics/metricspageci.php">CI Metrics</a></li>';
    // RTA metrics
    if ($metricsPage == "metricspagerta")
        echo '<li class="active"><a href="metricspagerta.php">RTA Metrics</a></li>';
    else
        echo '<li class="inactive"><a href="metricspagerta.php">RTA Metrics</a></li>';
}

echo '</ul>';
echo '</div>';

?>
