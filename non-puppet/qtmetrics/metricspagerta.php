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

        <?php include "rta/metricsboxdefinitions.php";?>


    <script>

        /* With these functions you can control the order of execution when loading the page first time.
           These functions are called from ajaxrequest.js
           Here the boxes are loaded synchoronously in the following order:
           a) Database status (initial loading with welcome text)
           b) Filters
           c) Metrics boxes
           d) Database status (normal use with session status)   */
        function loadAll()                                          // The starting point
        {
            loadDatabaseStatus(1);                                  // a)
        }

        function getMetricDataRequestCompleted()                    // Called when a metrics box has been updated
        {
            // Load the database status every time a metrics box is updated (to keep status updated when user uses the page)
            loadDatabaseStatus(0);                                  // d)
            window.scrollTo(0,0);                                   // Scroll window focus to the top of the page (selecting a job from history to show level 2 kept the focus vertically without this)
        }

        function getFiltersRequestCompleted()                       // Called when the filter box has been updated
        {
            loadMetricsBoxes();                                     // c) Load the metrics boxes next
        }

        function getDatabaseStatusInitialRequestCompleted()         // Called when the status box has been updated (initial step)
        {
            getFilters("filters", "rta/getfilters.php");            // b) Load the filters next (initial loading of the page)
        }

        function getDatabaseStatusRequestCompleted()                // Called when the status box has been updated (following steps)
        {
        }

        /* Load database status */
        function loadDatabaseStatus(initial)
        {
            if (initial == 1)
                getDatabaseStatusInitial("databaseStatus", "rta/getdatabasestatus.php", initial, getTimeOffset());  // Time offset passed to show the session time and database update time with the same 'timezone'
            else
                getDatabaseStatus("databaseStatus", "rta/getdatabasestatus.php", initial, getTimeOffset());         // Time offset passed to show the session time and database update time with the same 'timezone'
        }

        /* Load all metrics boxes */
        function loadMetricsBoxes()
        {
            showMetricsBoxes("All", "All", "All", "All");
        }

        /* Update all metrics boxes */
        function showMetricsBoxes(test, license, platform, job)
        {
            document.getElementById("test").value = test;           // Save default values (not necessarily the first item in the list)
            document.getElementById("license").value = license;
            document.getElementById("platform").value = platform;
            document.getElementById("job").value = job;
            var i;
            var file;
            var filterString;
            <?php
            foreach ($arrayMetricsBoxes as $key=>$value) {          // Loop all defined boxes (read and store the file path via php because defined as php)
                $filepath = $arrayMetricsBoxes[$key][0];
            ?>
                i = "<?php echo $key ?>";                           // (transfer php variables to javascript variables)
                file = "<?php echo $filepath ?>";
                filterString = createFilterString(test, license, platform, job);
                getMetricData(i, file, filterString);
            <?php
            }
            ?>
        }

        /* Update the metrics boxes based on filtering */
        function updateMetricsBoxes(filter, value)                  // filter = "test" / "license" / "platform" / "job"
        {
            document.getElementById(filter).value = value;          // Save filtered value
            var i;
            var file;
            var filterString;
            var appliedFilter;
            var clearFilter;
            <?php
            foreach ($arrayMetricsBoxes as $key=>$value) {          // Loop all defined boxes (read and store the file path via php because defined as php)
                $filepath = $arrayMetricsBoxes[$key][0];
                $appliedFilters = $arrayMetricsBoxes[$key][1];
                $clearFilters = $arrayMetricsBoxes[$key][2];
            ?>
                i = "<?php echo $key ?>";                           // (transfer php variables to javascript variables)
                file = "<?php echo $filepath ?>";
                appliedFilter = "-<?php echo $appliedFilters ?>";
                clearFilter = "-<?php echo $clearFilters ?>";
                checkClearFilter(appliedFilter, clearFilter, filter);
                if (appliedFilter.search(filter) >= 0 || appliedFilter.search("All") >= 0) {    // Check if this filter should update the metrics box
                    filterString = createFilterString(document.getElementById("test").value,
                                                      document.getElementById("license").value,
                                                      document.getElementById("platform").value,
                                                      document.getElementById("job").value);
                    getMetricData(i, file, filterString);
                }
            <?php
            }
            ?>
        }

        /* Check and clear filters on other filter changes (used with nested metrics boxes to get to 1st level) */
        function checkClearFilter(applied, clear, filter)
        {
            if (clear.search("test") >= 0 && filter != "test") {
                if (applied.search("All") >= 0)
                    document.getElementById("test").value = "All";
                if (applied.search("license") >= 0)
                    document.getElementById("test").value = "All";
                if (applied.search("platform") >= 0)
                    document.getElementById("test").value = "All";
                if (applied.search("job") >= 0)
                    document.getElementById("test").value = "All";
            }
            if (clear.search("license") >= 0 && filter != "license") {
                if (applied.search("All") >= 0)
                    document.getElementById("license").value = "All";
                if (applied.search("test") >= 0)
                    document.getElementById("license").value = "All";
                if (applied.search("platform") >= 0)
                    document.getElementById("license").value = "All";
                if (applied.search("job") >= 0)
                    document.getElementById("license").value = "All";
            }
            if (clear.search("platform") >= 0 && filter != "platform") {
                if (applied.search("All") >= 0)
                    document.getElementById("platform").value = "All";
                if (applied.search("test") >= 0)
                    document.getElementById("platform").value = "All";
                if (applied.search("license") >= 0)
                    document.getElementById("platform").value = "All";
                if (applied.search("job") >= 0)
                    document.getElementById("platform").value = "All";
            }
            if (clear.search("job") >= 0 && filter != "job") {
                if (applied.search("All") >= 0)
                    document.getElementById("job").value = "All";
                if (applied.search("test") >= 0)
                    document.getElementById("job").value = "All";
                if (applied.search("license") >= 0)
                    document.getElementById("job").value = "All";
                if (applied.search("platform") >= 0)
                    document.getElementById("job").value = "All";
            }
        }

        /* Create the filter string */
        function createFilterString(test, license, platform, job)
        {
            var filterString;
            var filterSeparator = "<?php echo FILTERSEPARATOR ?>";               // (transfer php constant to javascript)
            var filterValueSeparator = "<?php echo FILTERVALUESEPARATOR ?>";
            filterString = "test" + filterValueSeparator + test + filterSeparator
                + "license" + filterValueSeparator + license + filterSeparator
                + "platform" + filterValueSeparator + platform + filterSeparator
                + "job" + filterValueSeparator + job + filterSeparator;
            return filterString;
        }

        /* Update the metrics boxes when test filter changed */
        function filterTest(value)
        {
            updateMetricsBoxes("test", value);
        }

        /* Update the metrics boxes when license filter changed */
        function filterLicense(value)
        {
            updateMetricsBoxes("license", value);
        }

        /* Update the metrics boxes when platform filter changed */
        function filterPlatform(value)
        {
            updateMetricsBoxes("platform", value);
        }

        /* Update the metrics boxes when job filter changed */
        function filterJob(value)
        {
            updateMetricsBoxes("job", value);
        }

        /* Set all filters to "All" */
        function clearFilters()
        {
            loadMetricsBoxes();                                     // Note: Using this function will lead to only one Ajax call
        }

        /* Clear session variables and reload the page */
        function reloadFilters()
        {
            <?php
            session_unset();                                        // After clearing the filter session variables they are reloaded from the database
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
        <?php include "commondefinitions.php";?>
        <?php include "header.php";?>

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
        foreach ($arrayMetricsBoxes as $key=>$value)
            echo "<div id=\"metricsBox$key\" class=\"metricArea\"><img src=\"images/ajax-loader.gif\" alt=\"loading\"> Loading...</div>";   // Div content when initially opening the page before Ajax call
        ?>

        <?php include "footer.php";?>

        </div>     <!-- end of container -->
    </body>
</html>
