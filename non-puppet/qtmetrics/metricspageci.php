<?php
header('Content-type: text/html; charset=utf-8');             // Header information must be the first line in a html/php page
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

<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <script src="ajaxrequest.js"></script>
        <link rel="stylesheet" type="text/css" href="styles.css" />
        <link rel="shortcut icon" href="images/favicon.ico" type="image/x-icon" />

        <?php include "ci/metricsboxdefinitions.php";?>

    <script>

        /* Get all filter values and show all metrics boxes */
        function showAll()
        {
            getFilters("filters", "ci/getfilters.php");
            showMetricsBoxes("All","All");
        }

        /* Update all metrics boxes */
        function showMetricsBoxes(project,conf)
        {
            var i;
            var file;
            <?php
            foreach ($arrayMetricsBoxesCI as $key=>$value) {     // Loop all defined boxes (read and store the file path via php because defined as php)
                $filepath = $arrayMetricsBoxesCI[$key][0];
            ?>
                i = "<?php echo $key ?>";                        // (transfer php variables to javascript variables)
                file = "<?php echo $filepath ?>";
                getMetricData(i, file, project, conf);
            <?php
            }
            ?>
        }

        /* Update those metrics boxes that are applied to this filter */
        function filterProject(value)
        {
            var i;
            var file;
            var filter;
            var thisFilter = "Project";
            document.getElementById("project").value = value;    // Save filtered value
            <?php
            foreach ($arrayMetricsBoxesCI as $key=>$value) {     // Loop all defined boxes (read and store the file path via php because defined as php)
                $filepath = $arrayMetricsBoxesCI[$key][0];
                $filters = $arrayMetricsBoxesCI[$key][1];
            ?>
                i = "<?php echo $key ?>";                        // (transfer php variables to javascript variables)
                file = "<?php echo $filepath ?>";
                filter = "<?php echo $filters ?>";
                if (filter.search(thisFilter) >= 0 || filter.search("All") >= 0)   // Check if this filter should update the metrics box
                    getMetricData(i, file, value, document.getElementById("conf").value);
            <?php
            }
            ?>
        }

        /* Update those metrics boxes that are applied to this filter */
        function filterConf(value)
        {
            var i;
            var file;
            var filter;
            var thisFilter = "Conf";
            document.getElementById("conf").value = value;       // Save filtered value
            <?php
            foreach ($arrayMetricsBoxesCI as $key=>$value) {     // Loop all defined boxes (read and store the file path via php because defined as php)
                $filepath = $arrayMetricsBoxesCI[$key][0];
                $filters = $arrayMetricsBoxesCI[$key][1];
            ?>
                i = "<?php echo $key ?>";                        // (transfer php variables to javascript variables)
                file = "<?php echo $filepath ?>";
                filter = "<?php echo $filters ?>";
                if (filter.search(thisFilter) >= 0 || filter.search("All") >= 0)    // Check if this filter should update the metrics box
                    getMetricData(i, file, document.getElementById("project").value, value);
            <?php
            }
            ?>
        }

        /* Set filters to "All" */
        function clearFilters()
        {
        filterProject("All");
        filterConf("All");
        }

        /* Clear session variables and reload the page */
        function reloadFilters()
        {
            <?php
            session_unset();                                     // After clearing the filter session variables they are reloaded from the database
            ?>
            window.location.reload(true);
        }

    </script>

        <title>Qt Metrics</title>
    </head>

    <!-- Initially show all data -->
    <body onload="showAll()">
        <div id="container">
        <?php include "commondefinitions.php";?>
        <?php include "header.php";?>

        <!-- Filters -->
        <!-- (NOTE: The layout should remain same here and in getfilters.php because complete value lists are loaded here via Ajax) -->
        <div id="filters">
        <b>FILTERS:</b><br/><br/>
        <form name="form">
        Project: <select name="project" id="project" onchange="filterProject(this.value)">
        <?php
            echo "<option value=\"All\">Loading... </option>";
        ?>
        </select>
        Configuration: <select name="conf" id="conf" onchange="filterConf(this.value)">
        <?php
            echo "<option value=\"All\">Loading... </option>";
        ?>
        </select>
        </form>
        </div>     <!-- end of filters -->

        <!-- Metrics boxes -->
        <?php
        foreach ($arrayMetricsBoxesCI as $key=>$value)
            echo "<div id=\"metricsBox$key\" class=\"metricArea\">Loading...</div>";
        ?>

        <?php include "footer.php";?>

        </div>     <!-- end of container -->
    </body>
</html>