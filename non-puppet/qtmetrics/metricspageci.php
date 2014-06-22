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
include "commondefinitions.php";
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

        <link rel="stylesheet" href="http://code.jquery.com/ui/1.10.0/themes/base/jquery-ui.css" />
        <script src="http://code.jquery.com/jquery-1.8.3.js"></script>
        <script src="http://code.jquery.com/ui/1.10.0/jquery-ui.js"></script>

        <?php include "ci/metricsboxdefinitions.php";?>

    <script>

        var metricsRequestCount = 0;                             // The number of metrics box requests in progress
        var loadingMessageTimeout;                               // Timer to delay showing the loading message

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
                                                          document.getElementById("ciProject").value,
                                                          document.getElementById("ciBranch").value,
                                                          document.getElementById("ciPlatform").value,
                                                          document.getElementById("conf").value,
                                                          document.getElementById("autotest").value,
                                                          document.getElementById("build").value,
                                                          document.getElementById("timescale").value,
                                                          document.getElementById("since").value,
                                                          document.getElementById("autotestSortBy").value,
                                                          document.getElementById("autotestShowAll").value);
                        getMetricData(i, file, round, filterString);
                    }
                    else {
                        document.getElementById("roundCounter"+i).value = 1;        // Reset the counter
                        // Load the database status every time a metrics box is updated (to keep status updated when user uses the page)
                        loadDatabaseStatus(0);                   // d)
                        metricsRequestCount--;                   // A metrics box completed
                    }
                }
            <?php
            }
            ?>
            if (metricsRequestCount <= 0)                        // When all metrics boxes completed, close the loading window
                closeLoadingWindow();
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
            showMetricsBoxes("All", "All", "All", "All", "All", "All", 0, "All", "hide");
        }

        /* Show all metrics boxes the first time */
        function showMetricsBoxes(project, ciProject, ciBranch, ciPlatform, conf, autotest, build, timescale, showAll)
        {
            document.getElementById("project").value = project;  // Save default values (not necessarily the first item in the list)
            document.getElementById("ciProject").value = ciProject;
            document.getElementById("ciBranch").value = ciBranch;
            document.getElementById("ciPlatform").value = ciPlatform;
            document.getElementById("conf").value = conf;
            document.getElementById("autotest").value = autotest;
            document.getElementById("build").value = build;
            document.getElementById("timescale").value = timescale;
            document.getElementById("autotestShowAll").value = showAll;
            var i;
            var file;
            var round;
            var updatedMetricsBoxCount = 0;
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
                filterString = createFilterString(project,
                                                  ciProject,
                                                  ciBranch,
                                                  ciPlatform,
                                                  conf,
                                                  autotest,
                                                  build,
                                                  timescale,
                                                  "na",
                                                  "na",
                                                  showAll);
                getMetricData(i, file, round, filterString);
                updatedMetricsBoxCount++;
            <?php
            }
            ?>
            metricsRequestCount = updatedMetricsBoxCount;        // Save how many metrics boxes to be updated
        }

        /* Update the metrics boxes based on filtering */
        function updateMetricsBoxes(filter, value, sortBy, showAll) // filter = "project" / "ciProject" / "ciBranch" / "ciPlatform" / "conf" / "autotest" / "build" / "timescale"; sortBy and showAll are optional
        {
            document.getElementById(filter).value = value;       // Save filtered value
            if (typeof sortBy == "undefined")                    // sortBy is optional
                var sortBy = document.getElementById("autotestSortBy").value;
            if (typeof showAll == "undefined")                   // showAll is optional
                var showAll = document.getElementById("autotestShowAll").value;
            document.getElementById("autotestSortBy").value = sortBy;
            document.getElementById("autotestShowAll").value = showAll;
            var i;
            var file;
            var round;
            var updatedMetricsBoxCount = 0;
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
                                                      document.getElementById("ciProject").value,
                                                      document.getElementById("ciBranch").value,
                                                      document.getElementById("ciPlatform").value,
                                                      document.getElementById("conf").value,
                                                      document.getElementById("autotest").value,
                                                      document.getElementById("build").value,
                                                      timescaleType,
                                                      timescaleValue,
                                                      sortBy,
                                                      showAll);
                    getMetricData(i, file, round, filterString);
                    updatedMetricsBoxCount++;
                }
            <?php
            }
            ?>
            metricsRequestCount = updatedMetricsBoxCount;        // Save how many metrics boxes to be updated
            showLoadingWindow();                                 // Show a loading window for long lasting operations
        }

        /* Check and clear filters on other filter changes (used with nested metrics boxes to get to 1st level) */
        function checkClearFilter(applied, clear, filter)
        {
            if (clear.search("project") >= 0 && filter != "project") {
                if (applied.search("All") >= 0)
                    document.getElementById("project").value = "All";
                if (applied.search("ciProject") >= 0)
                    document.getElementById("project").value = "All";
                if (applied.search("ciBranch") >= 0)
                    document.getElementById("project").value = "All";
                if (applied.search("ciPlatform") >= 0)
                    document.getElementById("project").value = "All";
                if (applied.search("conf") >= 0)
                    document.getElementById("project").value = "All";
                if (applied.search("autotest") >= 0)
                    document.getElementById("project").value = "All";
                if (applied.search("build") >= 0)
                    document.getElementById("project").value = "All";
                if (applied.search("timescale") >= 0)
                    document.getElementById("project").value = "All";
            }
            if (clear.search("ciProject") >= 0 && filter != "ciProject") {
                if (applied.search("All") >= 0)
                    document.getElementById("ciProject").value = "All";
                if (applied.search("project") >= 0)
                    document.getElementById("ciProject").value = "All";
                if (applied.search("ciBranch") >= 0)
                    document.getElementById("ciProject").value = "All";
                if (applied.search("ciPlatform") >= 0)
                    document.getElementById("ciProject").value = "All";
                if (applied.search("conf") >= 0)
                    document.getElementById("ciProject").value = "All";
                if (applied.search("autotest") >= 0)
                    document.getElementById("ciProject").value = "All";
                if (applied.search("build") >= 0)
                    document.getElementById("ciProject").value = "All";
                if (applied.search("timescale") >= 0)
                    document.getElementById("ciProject").value = "All";
            }
            if (clear.search("ciBranch") >= 0 && filter != "ciBranch") {
                if (applied.search("All") >= 0)
                    document.getElementById("ciBranch").value = "All";
                if (applied.search("project") >= 0)
                    document.getElementById("ciBranch").value = "All";
                if (applied.search("ciProject") >= 0)
                    document.getElementById("ciBranch").value = "All";
                if (applied.search("ciPlatform") >= 0)
                    document.getElementById("ciBranch").value = "All";
                if (applied.search("conf") >= 0)
                    document.getElementById("ciBranch").value = "All";
                if (applied.search("autotest") >= 0)
                    document.getElementById("ciBranch").value = "All";
                if (applied.search("build") >= 0)
                    document.getElementById("ciBranch").value = "All";
                if (applied.search("timescale") >= 0)
                    document.getElementById("ciBranch").value = "All";
            }
            if (clear.search("ciPlatform") >= 0 && filter != "ciPlatform") {
                if (applied.search("All") >= 0)
                    document.getElementById("ciPlatform").value = "All";
                if (applied.search("project") >= 0)
                    document.getElementById("ciPlatform").value = "All";
                if (applied.search("ciProject") >= 0)
                    document.getElementById("ciPlatform").value = "All";
                if (applied.search("ciBranch") >= 0)
                    document.getElementById("ciPlatform").value = "All";
                if (applied.search("conf") >= 0)
                    document.getElementById("ciPlatform").value = "All";
                if (applied.search("autotest") >= 0)
                    document.getElementById("ciPlatform").value = "All";
                if (applied.search("build") >= 0)
                    document.getElementById("ciPlatform").value = "All";
                if (applied.search("timescale") >= 0)
                    document.getElementById("ciPlatform").value = "All";
            }
            if (clear.search("conf") >= 0 && filter != "conf") {
                if (applied.search("All") >= 0)
                    document.getElementById("conf").value = "All";
                if (applied.search("project") >= 0)
                    document.getElementById("conf").value = "All";
                if (applied.search("ciProject") >= 0)
                    document.getElementById("conf").value = "All";
                if (applied.search("ciBranch") >= 0)
                    document.getElementById("conf").value = "All";
                if (applied.search("ciPlatform") >= 0)
                    document.getElementById("conf").value = "All";
                if (applied.search("autotest") >= 0)
                    document.getElementById("conf").value = "All";
                if (applied.search("build") >= 0)
                    document.getElementById("conf").value = "All";
                if (applied.search("timescale") >= 0)
                    document.getElementById("conf").value = "All";
            }
            if (clear.search("autotest") >= 0 && filter != "autotest") {
                if (applied.search("All") >= 0)
                    document.getElementById("autotest").value = "All";
                if (applied.search("project") >= 0)
                    document.getElementById("autotest").value = "All";
                if (applied.search("ciProject") >= 0)
                    document.getElementById("autotest").value = "All";
                if (applied.search("ciBranch") >= 0)
                    document.getElementById("autotest").value = "All";
                if (applied.search("ciPlatform") >= 0)
                    document.getElementById("autotest").value = "All";
                if (applied.search("conf") >= 0)
                    document.getElementById("autotest").value = "All";
                if (applied.search("build") >= 0)
                    document.getElementById("autotest").value = "All";
                if (applied.search("timescale") >= 0)
                    document.getElementById("autotest").value = "All";
            }
            if (clear.search("build") >= 0 && filter != "build") {
                if (applied.search("All") >= 0)
                    document.getElementById("build").value = "All";
                if (applied.search("project") >= 0)
                    document.getElementById("build").value = "All";
                if (applied.search("ciProject") >= 0)
                    document.getElementById("build").value = "All";
                if (applied.search("ciBranch") >= 0)
                    document.getElementById("build").value = "All";
                if (applied.search("ciPlatform") >= 0)
                    document.getElementById("build").value = "All";
                if (applied.search("conf") >= 0)
                    document.getElementById("build").value = "All";
                if (applied.search("autotest") >= 0)
                    document.getElementById("build").value = "All";
                if (applied.search("timescale") >= 0)
                    document.getElementById("build").value = "All";
            }
            if (clear.search("timescale") >= 0 && filter != "timescale") {
                if (applied.search("All") >= 0)
                    document.getElementById("timescale").value = "All";
                if (applied.search("project") >= 0)
                    document.getElementById("timescale").value = "All";
                if (applied.search("ciProject") >= 0)
                    document.getElementById("timescale").value = "All";
                if (applied.search("ciBranch") >= 0)
                    document.getElementById("timescale").value = "All";
                if (applied.search("ciPlatform") >= 0)
                    document.getElementById("timescale").value = "All";
                if (applied.search("conf") >= 0)
                    document.getElementById("timescale").value = "All";
                if (applied.search("autotest") >= 0)
                    document.getElementById("timescale").value = "All";
                if (applied.search("build") >= 0)
                    document.getElementById("timescale").value = "All";
            }
        }

        /* Create the filter string (as defined in ci/definitions.php) */
        function createFilterString(project, ciProject, ciBranch, ciPlatform, conf, autotest, build, timescaleType, timescaleValue, sortBy, showAll)
        {
            var filterString;
            var filterSeparator = "<?php echo FILTERSEPARATOR ?>";               // (transfer php constant to javascript)
            var filterValueSeparator = "<?php echo FILTERVALUESEPARATOR ?>";
            filterString = "project" + filterValueSeparator + project + filterSeparator
                + "ciProject" + filterValueSeparator + ciProject + filterSeparator
                + "ciBranch" + filterValueSeparator + ciBranch + filterSeparator
                + "ciPlatform" + filterValueSeparator + ciPlatform + filterSeparator
                + "conf" + filterValueSeparator + conf + filterSeparator
                + "autotest" + filterValueSeparator + autotest + filterSeparator
                + "build" + filterValueSeparator + build + filterSeparator
                + "timescaleType" + filterValueSeparator + timescaleType + filterSeparator
                + "timescaleValue" + filterValueSeparator + timescaleValue + filterSeparator
                + "sortBy" + filterValueSeparator + sortBy + filterSeparator
                + "showAll" + filterValueSeparator + showAll + filterSeparator;
            return filterString;
        }

        /* Update the metrics boxes when project filter changed */
        function filterProject(value)
        {
            updateMetricsBoxes("project", value);
            filterBuild(0);                                         // Clear Project build filter when Project changed
        }

        /* Update the metrics boxes when (plain) project filter changed */
        function filterCiProject(value)
        {
            updateMetricsBoxes("ciProject", value);
            filterConf("All");                                      // Clear Configuration filter when Project name changed
            filterProject("All");                                   // Clear Project filter when Project name changed
        }

        /* Update the metrics boxes when (plain) branch filter changed */
        function filterCiBranch(value)
        {
            updateMetricsBoxes("ciBranch", value);
            filterConf("All");                                      // Clear Configuration filter when Project branch changed
            filterProject("All");                                   // Clear Project filter when Project branch changed
        }

        /* Update the metrics boxes when platform filter changed */
        function filterCiPlatform(value)
        {
            updateMetricsBoxes("ciPlatform", value);
            filterConf("All");                                      // Clear Configuration filter when Platform changed
            filterProject("All");                                   // Clear Project filter when Platform changed
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
            if (document.getElementById("timescale").value == "Since") {        // Highlight that the timescale filter is active
                document.getElementById("timescale").className = "timescaleSince";
                css('.date-tccontainer', 'background-color', '#FFCC00');        // (Note: This must follow the value defined in style.css)
            } else {
                document.getElementById("timescale").className = "timescaleAll";
                css('.date-tccontainer', 'background-color', 'white');          // (Note: This must follow the value defined in style.css)
            }
        }

        /* Update the metrics boxes when build filter changed */
        function filterBuild(value)
        {
            updateMetricsBoxes("build", value);
        }

        function filterProjectAutotest(project, autotest)
        {
            filterProject(project);
            filterAutotest(autotest);
        }

        /* Set all filters to "All" */
        function clearFilters()
        {
            filterTimescale("All");                             // Clear the possible timescale filter styling
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

        /* Toggle the show/hide selection for Autotest all builds results */
        function toggleAutotestShowAll(value)
        {
            var newValue;
            if (value=="show")
                newValue = "hide";
            else
                newValue = "show";
            updateMetricsBoxes("autotest", "All", 0, newValue);
        }

        /* Open a new window for a message file (html) */
        function showMessageWindow(messageFile)
        {
            myWindow = window.open(messageFile,'','resizable=yes,scrollbars=yes,width=600,height=600,left=500,top=100');
            myWindow.focus();
        }

        /* Ajax loading dialog window for long lasting operations */
        $(function()
        {
            $( "#popupDialog" ).dialog({
                autoOpen: false,
                resizable: false,
                minheight:180,
                modal: false,
                dialogClass: 'popupDialog',
                buttons: {
                    Ok: function() {
                        $( this ).dialog( "close" );
                    }
                },
                open: function() {
                    $('.ui-dialog-buttonpane').find('button').addClass('popupDialogButton');    // Add a class to be able to define the button style
                },
                close: function() {
                    $( this ).dialog( "close" );
                }
            });
        });
        function showLoadingWindow()
        {
            var delay = <?php echo LOADINGMESSAGEDELAY ?>;
            if (delay > 0) {                                                     // Loading message not shown at all when delay set to 0
                clearTimeout(loadingMessageTimeout);                             // Stop possible earlier timer
                loadingMessageTimeout = setTimeout(
                    function() {
                        $('#popupDialog').dialog('open');
                    }
                    ,delay);                                                     // Show the message with a delay
            }
        }
        function closeLoadingWindow()
        {
            var delay = <?php echo LOADINGMESSAGEDELAY ?>;
            if (delay > 0) {
                clearTimeout(loadingMessageTimeout);
                $('#popupDialog').dialog('close');
            }
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

        /* Change a css property value for an id or class (provided by w3c http://stackoverflow.com/questions/566203/changing-css-values-with-javascript) */
        function css(selector, property, value) {
            for (var i=0; i<document.styleSheets.length;i++) {                  //Loop through all styles
                //Try add rule
                try { document.styleSheets[i].insertRule(selector+ ' {'+property+':'+value+'}', document.styleSheets[i].cssRules.length);
                } catch(err) {try { document.styleSheets[i].addRule(selector, property+':'+value);} catch(err) {}}//IE
            }
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

        <div id="popupDialog" title="Please wait">
            <p><span class="ui-icon ui-icon-alert popupDialogMessage"></span>
                Please wait, loading the data takes a while <span class="loading"><span>.</span><span>.</span><span>.</span></span><br><br>Reload, if not ready in a few minutes.
            </p>
        </div>

        </div>     <!-- end of container -->
    </body>
</html>
