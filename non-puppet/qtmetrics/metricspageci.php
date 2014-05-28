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

<?php
include "connectiondefinitions.php";
include "commonfunctions.php";
?>

<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <script src="ajaxrequest.js"></script>
        <script src="calendar/calendar.js"></script>
        <link href="calendar/calendar.css" type="text/css" rel="stylesheet" />
        <link rel="stylesheet" type="text/css" href="styles.css" />
        <link rel="shortcut icon" href="images/favicon.ico" type="image/x-icon" />

        <?php include "ci/metricsboxdefinitions.php";?>

    <script>

        /* With these functions you can control the order of execution when loading the page first time.
           These functions are called from ajaxrequest.js
           Here the boxes are loaded synchoronously in the following order:
           a) Database status (initial loading with welcome text)
           b) Filters
           c) Metrics boxes
           d) Database status (normal use with session and database status)   */
        function loadAll()                                       // The starting point
        {
            loadDatabaseStatus(1);                               // a)
        }

        function getMetricDataRequestCompleted(metricId)         // Called when a metrics box has been updated
        {
            var file;
            var repeat;
            var round;
            <?php
            foreach ($arrayMetricsBoxes as $key=>$value) {       // Loop all the metrics boxes to find the completed one
                $filepath = $arrayMetricsBoxes[$key][METRICSBOXNAME];
            ?>
                i = <?php echo $key ?>;                          // (transfer php variables to javascript variables)
                if (metricId == i) {                             // Check the completed metrics box
                    file = "<?php echo $filepath ?>";
                    repeat = document.getElementById("repeatCount"+i).value;
                    round = document.getElementById("roundCounter"+i).value;
                    if (round < repeat) {                        // If the box must be repeated
                        round++;
                        document.getElementById("roundCounter"+i).value = round;
                        filterString = createFilterString(document.getElementById("project").value,
                                                          document.getElementById("conf").value,
                                                          document.getElementById("autotest").value,
                                                          document.getElementById("timescale").value,
                                                          document.getElementById("since").value,
                                                          document.getElementById("autotestSortBy").value);
                        getMetricData(i, file, round, filterString);
                    }
                    else {
                        document.getElementById("roundCounter"+i).value = 1;        // Reset the counter
                        // Load the database status every time a metrics box is updated (to keep status updated when user uses the page)
                        loadDatabaseStatus(0);                   // d)
                    }
                }
            <?php
            }
            ?>
        }

        function getFiltersRequestCompleted()                    // Called when the filter box has been updated
        {
            loadMetricsBoxes();                                  // c) Load the metrics boxes next
        }

        function getDatabaseStatusInitialRequestCompleted()      // Called when the status box has been updated (initial step)
        {
            getFilters("filters", "ci/getfilters.php");          // b) Load the filters next (initial loading of the page)
        }

        function getDatabaseStatusRequestCompleted()             // Called when the status box has been updated (following steps)
        {
        }

        /* Load database status */
        function loadDatabaseStatus(initial)
        {
            if (initial == 1)
                getDatabaseStatusInitial("databaseStatus", "ci/getdatabasestatus.php", initial, getTimeOffset());  // Time offset passed to show the session time and database update time with the same 'timezone'
            else
                getDatabaseStatus("databaseStatus", "ci/getdatabasestatus.php", initial, getTimeOffset());         // Time offset passed to show the session time and database update time with the same 'timezone'
        }

        /* Load all metrics boxes */
        function loadMetricsBoxes()
        {
            showMetricsBoxes("All", "All", "All", "All");
        }

        /* Show all metrics boxes the first time */
        function showMetricsBoxes(project, conf, autotest, timescale)
        {
            document.getElementById("project").value = project;  // Save default values (not necessarily the first item in the list)
            document.getElementById("conf").value = conf;
            document.getElementById("autotest").value = autotest;
            document.getElementById("timescale").value = timescale;
            var i;
            var file;
            var round;
            var filterString;
            <?php
            $arrayMetricsBoxRepeat = array();
            $arrayMetricsBoxRound = array();
            foreach ($arrayMetricsBoxes as $key=>$value) {       // Loop all the metrics boxes and send the Ajax call for each of them
                $filepath = $arrayMetricsBoxes[$key][METRICSBOXNAME];
            ?>
                i = <?php echo $key ?>;                          // (transfer php variables to javascript variables)
                file = "<?php echo $filepath ?>";
                round = 1;                                       // First round tor this update
                document.getElementById("roundCounter"+i).value = round;
                filterString = createFilterString(project, conf, autotest, timescale, "na", "na");
                getMetricData(i, file, round, filterString);
            <?php
            }
            ?>
        }

        /* Update the metrics boxes based on filtering */
        function updateMetricsBoxes(filter, value, sortBy)       // filter = "project" / "conf" / "autotest" / "timescale"; sortBy is optional
        {
            document.getElementById(filter).value = value;       // Save filtered value
            if (typeof sortBy == "undefined")                    // sortBy is optional, set 0 as a default
                var sortBy = 0;
            document.getElementById("autotestSortBy").value = sortBy;
            var i;
            var file;
            var round;
            var filterString;
            var appliedFilter;
            var clearFilter;
            var timescaleType = document.getElementById("timescale").value;      // Type: All/In/Since
            var timescaleValue = document.getElementById("since").value;         // In case of type "Since" use date value set in the calendar
            if (timescaleType.search("In") == 0) {                               // In case of type "In" ...
                timescaleValue = timescaleType.substr(3);                        // ... set value to month (e.g. "2013-06")
                timescaleType = "In";
            }
            <?php
            foreach ($arrayMetricsBoxes as $key=>$value) {       // Loop all the metrics boxes and send the Ajax call for each of them
                $filepath = $arrayMetricsBoxes[$key][METRICSBOXNAME];
                $appliedFilters = $arrayMetricsBoxes[$key][METRICSBOXFILTERSAPPLIED];
                $clearFilters = $arrayMetricsBoxes[$key][METRICSBOXFILTERSCLEARED];
            ?>
                i = "<?php echo $key ?>";                        // (transfer php variables to javascript variables)
                file = "<?php echo $filepath ?>";
                round = 1;                                       // First round tor this update
                document.getElementById("roundCounter"+i).value = round;
                appliedFilter = "-<?php echo $appliedFilters ?>";
                clearFilter = "-<?php echo $clearFilters ?>";
                checkClearFilter(appliedFilter, clearFilter, filter);
                if (appliedFilter.search(filter) >= 0 || appliedFilter.search("All") >= 0) {    // Check if this filter should update the metrics box
                    filterString = createFilterString(document.getElementById("project").value,
                                                      document.getElementById("conf").value,
                                                      document.getElementById("autotest").value,
                                                      timescaleType,
                                                      timescaleValue,
                                                      sortBy);
                    getMetricData(i, file, round, filterString);
                }
            <?php
            }
            ?>
        }

        /* Check and clear filters on other filter changes (used with nested metrics boxes to get to 1st level) */
        function checkClearFilter(applied, clear, filter)
        {
            if (clear.search("project") >= 0 && filter != "project") {
                if (applied.search("All") >= 0)
                    document.getElementById("project").value = "All";
                if (applied.search("conf") >= 0)
                    document.getElementById("project").value = "All";
                if (applied.search("autotest") >= 0)
                    document.getElementById("project").value = "All";
                if (applied.search("timescale") >= 0)
                    document.getElementById("project").value = "All";
            }
            if (clear.search("conf") >= 0 && filter != "conf") {
                if (applied.search("All") >= 0)
                    document.getElementById("conf").value = "All";
                if (applied.search("project") >= 0)
                    document.getElementById("conf").value = "All";
                if (applied.search("autotest") >= 0)
                    document.getElementById("conf").value = "All";
                if (applied.search("timescale") >= 0)
                    document.getElementById("conf").value = "All";
            }
            if (clear.search("autotest") >= 0 && filter != "autotest") {
                if (applied.search("All") >= 0)
                    document.getElementById("autotest").value = "All";
                if (applied.search("project") >= 0)
                    document.getElementById("autotest").value = "All";
                if (applied.search("conf") >= 0)
                    document.getElementById("autotest").value = "All";
                if (applied.search("timescale") >= 0)
                    document.getElementById("autotest").value = "All";
            }
            if (clear.search("timescale") >= 0 && filter != "timescale") {
                if (applied.search("All") >= 0)
                    document.getElementById("timescale").value = "All";
                if (applied.search("project") >= 0)
                    document.getElementById("timescale").value = "All";
                if (applied.search("conf") >= 0)
                    document.getElementById("timescale").value = "All";
                if (applied.search("autotest") >= 0)
                    document.getElementById("timescale").value = "All";
            }
        }

        /* Create the filter string */
        function createFilterString(project, conf, autotest, timescaleType, timescaleValue, sortBy)
        {
            var filterString;
            var filterSeparator = "<?php echo FILTERSEPARATOR ?>";               // (transfer php constant to javascript)
            var filterValueSeparator = "<?php echo FILTERVALUESEPARATOR ?>";
            filterString = "project" + filterValueSeparator + project + filterSeparator
                + "conf" + filterValueSeparator + conf + filterSeparator
                + "autotest" + filterValueSeparator + autotest + filterSeparator
                + "timescaleType" + filterValueSeparator + timescaleType + filterSeparator
                + "timescaleValue" + filterValueSeparator + timescaleValue + filterSeparator
                + "sortBy" + filterValueSeparator + sortBy + filterSeparator;
            return filterString;
        }

        /* Update the metrics boxes when project filter changed */
        function filterProject(value)
        {
            updateMetricsBoxes("project", value);
        }

        /* Update the metrics boxes when conf filter changed */
        function filterConf(value)
        {
            updateMetricsBoxes("conf", value);
        }

        /* Update the metrics boxes when autotest filter changed; output table can also be sorted */
        function filterAutotest(value, sortBy)
        {
            updateMetricsBoxes("autotest", value, sortBy);
        }

        /* Update the metrics boxes when timescale filter changed */
        function filterTimescale(value)
        {
            updateMetricsBoxes("timescale", value);
        }

        function filterProjectAutotest(project, autotest)
        {
            filterProject(project);
            filterAutotest(autotest);
        }

        /* Set all filters to "All" */
        function clearFilters()
        {
            loadMetricsBoxes();                                 // Note: Using this function will lead to only one Ajax call
        }

        /* Set Project filters to "All" */
        function clearProjectFilters()
        {
            filterProject("All");                               // Note: Using separate functions will lead to several Ajax calls
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

        /* Open a new window for a message file (html) */
        function showMessageWindow(messageFile)
        {
            myWindow=window.open(messageFile,'','resizable=yes,scrollbars=yes,width=600,height=600,left=500,top=100');
            myWindow.focus();
        }

        /* Get time offset between current time and the GMT/UTC (returned in format "GMT+0300") */
        function getTimeOffset()
        {
            var visitorTime = new Date();                                        // User client time
            var visitorTimeString = visitorTime.toString();                      // e.g. "Fri Jun 07 2013 12:49:38 GMT+0000 (Morocco Standard Time)" or "Fri Jun 07 2013 12:49:38 GMT+0300 (FLE Standard Time)"
            visitorTimeString = visitorTimeString.replace("UTC ","UTC+0000 ");   // in IE: "Fri Jun 07 12:49:38 UTC 2013" or ...
            visitorTimeString = visitorTimeString.replace("UTC","GMT");          //    ... "Fri Jun 07 12:49:38 UTC+0300 2013"
            var timeOffset;
            var i = visitorTimeString.search("GMT");
            if (i > 0) {
                timeOffset = visitorTimeString.substr(i,8);                      // "GMT+0300" (here also the xx:30 and xx:45 timezones include)
            } else {                                                             // For US timezones the timezone name used instead of "UTC" in IE
                offsetHour = -1 * visitorTime.getTimezoneOffset() / 60;          // Create the string based on getTimezoneOffset
                if (offsetHour > 9)
                    timeOffset = "GMT+" + offsetHour + "00";
                if (offsetHour >= 0 && offsetHour >= 9)
                    timeOffset = "GMT+0" + offsetHour + "00";
                if (offsetHour >= -9 && offsetHour < 0)
                    timeOffset = "GMT-0" + Math.abs(offsetHour) + "00";
                if (offsetHour < -9)
                    timeOffset = "GMT-" + Math.abs(offsetHour) + "00";
            }
            return timeOffset;
        }

    </script>

        <title>Qt Metrics</title>
    </head>

    <!-- Initially show all data -->
    <body onload="loadAll()">
        <div id="container">
        <?php include "commondefinitions.php";
        $metricsPage = "metricspageci";                 // Filename (without the extension) to identify active page for menu
        include "header.php";
        ?>

        <!-- Filters (loaded with Ajax call) -->
        <div id="filters">
        <b>FILTERS:</b><br/><br/>
        <img src="images/ajax-loader.gif" alt="loading"> Loading...<br/>
        </div>

        <!-- Database status (loaded with Ajax call) -->
        <div id="databaseStatus">
        <b>Welcome</b>
        </div>

        <!-- Metrics boxes -->
        <?php
        foreach ($arrayMetricsBoxes as $key=>$value) {       // Loop all the metrics boxes to create a div (and hidden input elements) for each of them
            echo "<div id=\"metricsBox$key\" class=\"metricArea\"><img src=\"images/ajax-loader.gif\" alt=\"loading\"> Loading...</div>";   // Div content when initially opening the page before Ajax call
            $repeat = $arrayMetricsBoxes[$key][METRICSBOXREPEAT];
            echo "<input id=\"repeatCount$key\" type=\"hidden\" value=\"$repeat\">";    // Store repeat count to an input element to be available for JavaScript functions
            echo "<input id=\"roundCounter$key\" type=\"hidden\">";                     // Store round counter to an input element to be available for JavaScript functions
        }
        ?>

        <?php include "footer.php";?>

        </div>     <!-- end of container -->
    </body>
</html>
