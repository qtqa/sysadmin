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
    // $project
    // $projectFilter
    // $buildNumber        (in listgeneraldata.php)
    // $build
    // $round

if ($round > 1) {                                                   // Skip on first round to optimize performance

    /* Graph as an expand/collapse section */
    echo '<div id="graphBuildPhases" class="graphAccordion">';
    echo '<h3><u>Project Build phases by Configuration</u> <i>(show/hide)</i></h3>';
    echo '<div id="graphBuildPhasesSection">';

    $timeStartThis = microtime(true);
    $timeRead = microtime(true);
    $arrayConfName = $_SESSION['arrayConfName'];                    // Get Configuration names

    /* Read Build phases for each Configuration from the database */
    $arrayDbConfBuildStart = array();                               // Build start timestamp of each Conf
    $arrayDbConfBuildEnd = array();                                 // Build end timestamp of each Conf
    $arrayDbConfBuildPhaseName = array();                           // Name of each Build phase for each Conf
    $arrayDbConfBuildPhaseStart = array();                          // Start timestamp of each Build phase for each Conf
    $arrayDbConfBuildPhaseEnd = array();                            // End timestamp of each Build phase for each Conf
    $arrayDbConfBuildPhaseDuration = array();                       // Duration of each Build phase for each Conf
    $arrayDbConfPhaseName = array();                                // Temporary arrays for calculation
    $arrayDbConfPhaseStart = array();                               // -,,-
    $arrayDbConfPhaseEnd = array();                                 // -,,-
    $arrayDbConfPhaseDuration = array();                            // -,,-
    foreach ($arrayConfName as $keyConf => $nameConf) {             // Initialize
        foreach ($arrayAllBuildPhases as $keyPhase => $namePhase) {
            $arrayDbConfBuildPhaseDuration[$keyConf][$keyPhase] = 0;
        }
    }
    $prevConf = "";
    $buildStart = "";
    $buildEnd = "";
    if ($build == 0) {                                              // Show the latest build ...
        $sql = cleanSqlString(
               "SELECT cfg, phase, parent, start, end
                FROM phases_latest
                WHERE $projectFilter
                ORDER BY cfg, start, end");
    } else {                                                        // ... or the selected build
        $sql = cleanSqlString(
               "SELECT cfg, phase, parent, start, end
                FROM phases
                WHERE $projectFilter AND build_number=$buildNumber
                ORDER BY cfg, start, end");
    }
    $dbColumnPhasesCfg = 0;
    $dbColumnPhasesPhase = 1;
    $dbColumnPhasesParent = 2;
    $dbColumnPhasesStart = 3;
    $dbColumnPhasesEnd = 4;
    if ($useMysqli) {
        $result = mysqli_query($conn, $sql);
        $numberOfRows = mysqli_num_rows($result);
    } else {
        $result = mysql_query($sql) or die (mysql_error());
        $numberOfRows = mysql_num_rows($result);
    }
    for ($j=0; $j<$numberOfRows; $j++) {
        if ($useMysqli)
            $resultRow = mysqli_fetch_row($result);
        else
            $resultRow = mysql_fetch_row($result);
        foreach ($arrayConfName as $key => $name) {
            if ($resultRow[$dbColumnPhasesCfg] == $name)                                // Find the Conf
                $confId = $key;
            if ($prevConf == $name)                                                     // Find the previous Conf
                $prevConfId = $key;
        }

        // Find the main phase to be shown in the graph with its subphases
        if ($resultRow[$dbColumnPhasesParent] == "" AND
            strpos($resultRow[$dbColumnPhasesPhase], BUILDMAINPHASEPREFIX) === 0) {
            $arrayDbConfBuildStart[$confId] = $resultRow[$dbColumnPhasesStart];         // Save Build start and end timestamps
            $arrayDbConfBuildEnd[$confId] = $resultRow[$dbColumnPhasesEnd];
        }
        // Save the arrays for each Conf when read (the previous Conf in the loop), and clear for the next one
        if ($confId != $prevConfId AND $prevConf != "") {
            $arrayDbConfBuildPhaseName[$prevConfId] = $arrayDbConfPhaseName;
            $arrayDbConfBuildPhaseStart[$prevConfId] = $arrayDbConfPhaseStart;
            $arrayDbConfBuildPhaseEnd[$prevConfId] = $arrayDbConfPhaseEnd;
            $arrayDbConfBuildPhaseDuration[$prevConfId] = $arrayDbConfPhaseDuration;
            $arrayDbConfPhaseName = array();
            $arrayDbConfPhaseStart = array();
            $arrayDbConfPhaseEnd = array();
            $arrayDbConfPhaseDuration = array();
        }
        // Find the subphases and save the name and timestamps
        if (strpos($resultRow[$dbColumnPhasesParent], BUILDMAINPHASEPREFIX) === 0) {
            if ($resultRow[$dbColumnPhasesEnd] != "0000-00-00 00:00:00") {              // Save only the phases completed
                $arrayDbConfPhaseName[] = $resultRow[$dbColumnPhasesPhase];
                $arrayDbConfPhaseStart[] = $resultRow[$dbColumnPhasesStart];
                $arrayDbConfPhaseEnd[] = $resultRow[$dbColumnPhasesEnd];
                $arrayDbConfPhaseDuration[] = timeDiffSeconds($resultRow[$dbColumnPhasesStart], $resultRow[$dbColumnPhasesEnd]);
                if ($arrayDbConfBuildEnd[$confId] < $resultRow[$dbColumnPhasesEnd])     // If a subphase was completed later than the main phase (case when the main phase aborted and shows "0000-00-00 00:00:00")
                    $arrayDbConfBuildEnd[$confId] = $resultRow[$dbColumnPhasesEnd];     // ... use the last subphase end as main phase end
            }
        }
        // Save earliest/latest time for the whole Build start/end time
        if ($buildStart == "" OR $buildStart > $resultRow[$dbColumnPhasesStart])
            $buildStart = $resultRow[$dbColumnPhasesStart];
        if ($buildEnd == "" OR $buildEnd < $resultRow[$dbColumnPhasesEnd])
            $buildEnd = $resultRow[$dbColumnPhasesEnd];

        $prevConf = $resultRow[$dbColumnPhasesCfg];                                     // Save to compare on the next loop round
    }
    // Save the arrays for the last Conf
    $arrayDbConfBuildPhaseName[$confId] = $arrayDbConfPhaseName;
    $arrayDbConfBuildPhaseStart[$confId] = $arrayDbConfPhaseStart;
    $arrayDbConfBuildPhaseEnd[$confId] = $arrayDbConfPhaseEnd;
    $arrayDbConfBuildPhaseDuration[$confId] = $arrayDbConfPhaseDuration;

    if ($useMysqli)
        mysqli_free_result($result);

    /* Print summary data */
    echo '<table>';
    echo '<tr><td>Build started (first phase start):</td><td>' . $buildStart . '</td></tr>';
    echo '<tr><td>Build ended (last phase end):</td><td>' . $buildEnd . '</td></tr>';
    echo '<tr><td>Build total duration:</td><td>' . dateDiff($buildEnd, $buildStart) . '</td></tr>';
    echo '</table><br>';

    /* Collect the Configuration phases for the Graphs */
    $phaseCount = 0;
    foreach ($arrayAllBuildPhases as $keyPhase => $namePhase)
        $phaseCount++;
    $arrayGraphBuildPhases = array();                                                       // List of phases (displayed name)
    $arrayGraphBuildConfs = array();                                                        // List of Confs built
    $arrayGraphBuildConfPhaseDuration = array();                                            // Duration of each phase for each built Configuration
    foreach ($arrayAllBuildPhases as $keyPhase => $namePhase) {
        $arrayGraphBuildPhases[] = $namePhase[BUILDPHASESDISPLAYNAME];
    }
    foreach ($arrayConfName as $keyConf => $nameConf) {                                                         // Loop all Confs
        $arrayGraphBuildPhaseDuration = array();
        $arrayGraphBuildPhaseStart = array();
        $arrayGraphBuildPhaseEnd = array();
        foreach ($arrayGraphBuildPhases as $keyPhase => $namePhase) {
            $arrayGraphBuildPhaseDuration[$keyPhase] = 0;                                                       // Initialize
            $arrayGraphBuildPhaseStart[$keyPhase] = 0;
            $arrayGraphBuildPhaseEnd[$keyPhase] = 0;
        }
        if ($arrayDbConfBuildStart[$keyConf] != "" AND $arrayDbConfBuildEnd[$keyConf] != "0000-00-00 00:00:00") {   // Save only the Builds started and completed
            $arrayGraphBuildConfs[] = $nameConf;
            // Save the executed phases
            foreach ($arrayAllBuildPhases as $keyPhase => $namePhase) {                                         // Loop all phases
                foreach ($arrayDbConfBuildPhaseName[$keyConf] as $keyPhaseDb => $namePhaseDb) {                 // Loop the executed phases
                    if (strpos($namePhaseDb, $namePhase[BUILDPHASESFULLNAMEPREFIX]) === 0) {                    // Save only the executed phases
                        $arrayGraphBuildPhaseDuration[$keyPhase] = $arrayDbConfBuildPhaseDuration[$keyConf][$keyPhaseDb];
                        $arrayGraphBuildPhaseStart[$keyPhase] = $arrayDbConfBuildPhaseStart[$keyConf][$keyPhaseDb];
                        $arrayGraphBuildPhaseEnd[$keyPhase] = $arrayDbConfBuildPhaseEnd[$keyConf][$keyPhaseDb];
                    }
                }
            }
            // Save the idle time between the phases
            $booQtQaTestsAfterConfiguring = FALSE;                                                              // For 'running the qtqa tests' phase exception handling
            foreach ($arrayAllBuildPhases as $keyPhase => $namePhase) {                                         // Loop all phases again
                if ($namePhase[BUILDPHASESFULLNAMEPREFIX] == "idle") {                                          // Calculate the idle time between phases
                    if ($arrayGraphBuildPhaseStart[$keyPhase + 1] != 0) {                                       // If the next phase was started
                        if ($keyPhase == 0) {                                                                   // If idle of the first phase
                            $idleDuration = timeDiffSeconds($buildStart,                                        // ... calculate the idle time from the build start
                                                            $arrayGraphBuildPhaseStart[$keyPhase + 1]);
                            $arrayGraphBuildPhaseDuration[$keyPhase] = $idleDuration;
                        } else {                                                                                // If idle of others phases
                            $idleDuration = timeDiffSeconds($arrayGraphBuildPhaseEnd[$keyPhase - 1],
                                                            $arrayGraphBuildPhaseStart[$keyPhase + 1]);         // ... calculate from previous phase end to next phase start
                            // Exception: 'qtqa tests' run after 'configuring Qt' (instead of after 'autotests')
                            if ($namePhase[BUILDPHASESID] == "PHASEQTQATESTS1IDLE") {
                                if ($idleDuration < 30 AND $arrayGraphBuildPhaseDuration[$keyPhase + 1] > 0) {  // If 'qtqa tests' run after 'configuring Qt' (in less than 30 sec) ...
                                    $booQtQaTestsAfterConfiguring = TRUE;                                       // ... set exception flag
                                } else {                                                                        // ... otherwise it is run after 'autotests', therefore ...
                                    $idleDuration = 0;                                                          // ... clear the duration of the 1st 'qtqa tests' idle
                                    $arrayGraphBuildPhaseDuration[$keyPhase + 1] = 0;                           // ... clear the duration of the 1st 'qtqa tests' phase
                                }
                            }
                            if ($namePhase[BUILDPHASESID] == "PHASECOMPILINGIDLE"                               // If 'compiling' phase normally after 'configuring'
                                AND !$booQtQaTestsAfterConfiguring) {
                                $idleDuration = timeDiffSeconds($arrayGraphBuildPhaseEnd[$keyPhase - 3],
                                                                $arrayGraphBuildPhaseStart[$keyPhase + 1]);     // ... skip the extra 'qtqa tests' exception phase
                            }
                            if ($namePhase[BUILDPHASESID] == "PHASEQTQATESTS2IDLE"                              // If 'qtqa tests' run after 'configuring Qt'
                                AND $booQtQaTestsAfterConfiguring) {
                                $idleDuration = 0;                                                              // ... clear the duration of the 2nd 'qtqa tests' idle
                                $arrayGraphBuildPhaseDuration[$keyPhase + 1] = 0;                               // ... clear the duration of the 2nd 'qtqa tests' phase
                            }
                            $arrayGraphBuildPhaseDuration[$keyPhase] = $idleDuration;
                        }
                        // Exception: Phase start was delayed (has an idle time) but the phase itself was completed in 0s (i.e. would not appear in the graph)
                        if ($idleDuration > 0 AND $arrayGraphBuildPhaseDuration[$keyPhase + 1] == 0) {          // Idle more than 0s but phase duration is 0s
                            $remainingDuration = 0;
                            for ($j=$keyPhase+1; $j<$phaseCount; $j++)                                          // Count the remaining phases (idle times ignored because not yet calculated here)
                                $remainingDuration = $remainingDuration + $arrayGraphBuildPhaseDuration[$j];
                            if ($remainingDuration == 0)                                                        // If the remaining phases are zero as well
                                $arrayGraphBuildPhaseDuration[$keyPhase + 1] = PHASEQUICKWITHIDLE;              // Tag phase duration to show in the graph
                        }
                    }
                }
            }
            // Save the array of duration for each phase (for a Conf)
            $arrayGraphBuildConfPhaseDuration[] = $arrayGraphBuildPhaseDuration;
        }
    }

    ?>

<!--[if gt IE 9]><!-->
    <div class="tableCellCentered">
    <?php /* Graph title */
        echo '<b>Build phases in the order of execution for ' . $project;
        echo ' Build ' . $buildNumber;
        if ($build == 0)
            echo ' (latest)';
        echo '</b><br>';
    ?>
    </div>

    <?php /* Graph */ ?>
    <div class="chart">
        <div id="tooltipBuildPhases" class="graphTooltip hidden">
            <p><span id="valueBuildPhases">100</span></p>
        </div>
    </div>
<!--<![endif]-->

    <?php /* Data (expandable) */ ?>
    <div class="dataAccordion">
        <h3><u>Data and notes</u> <i>(show/hide)</i></h3>
        <div>
            Note 1: The 'qtqa tests' phase is included twice. This is because it can be run either after the 'configuring Qt' or 'autotests' phase.<br>
            Note 2: Duration less than 60 seconds is shown as one minute in the graph to make it visible (actual duration is shown in the data).<br>
            <br>
            Duration of the phases and <i>the idle time between them</i> (in seconds):<br>
            <br>
            <?php
            echo '<table class="fontSmall">';
            echo '<tr class="tableBottomBorder">';
            echo '<td><b>Configuration</b></td>';
            foreach ($arrayAllBuildPhases as $keyPhase => $namePhase) {
                echo '<td class="tableCellCentered"><b>' . $namePhase[BUILDPHASESDISPLAYNAME] . '</b></td>';
            }
            echo '</tr>';
            foreach ($arrayGraphBuildConfs as $keyConf => $nameConf) {
                if ($keyConf % 2 == 0)
                    echo '<tr>';
                else
                    echo '<tr class="tableBackgroundColored">';
                echo '<td>' . $nameConf . '</td>';
                foreach ($arrayAllBuildPhases as $keyPhase => $namePhase) {
                    $duration = $arrayGraphBuildConfPhaseDuration[$keyConf][$keyPhase];
                    if ($duration == PHASEQUICKWITHIDLE)                // Decode possible tagged value
                        $duration = 0;
                    if ($namePhase[BUILDPHASESFULLNAMEPREFIX] == "idle")
                        echo '<td class="tableCellCentered"><i>' . $duration . '</i></td>';
                    else
                        echo '<td class="tableCellCentered">' . $duration . '</td>';
                }
                echo '</tr>';
            }
            echo '</table><br>';
            ?>
        </div>
    </div>

    <?php

    /* Elapsed time */
    if ($showElapsedTime) {
        $timeEnd = microtime(true);
        $timeDbRead = round($timeRead - $timeStartThis, 4);
        $timeCalc = round($timeEnd - $timeRead, 4);
        $time = round($timeEnd - $timeStartThis, 4);
        echo "<div class=\"elapdedTime\">";
        echo "<ul><li>";
        echo "<b>Total time</b> (round $round): $time s (database read time: $timeDbRead s, calculation time: $timeCalc s)";
        echo "</li></ul>";
        echo "</div>";
    } else {
        echo "<br>";
    }

    echo "</div>";  // graphBuildPhasesSection
    echo "</div>";  // graphBuildPhases

}  // $round > 1

?>

<!--[if gt IE 9]><!-->
<script id="scriptBuildPhases">

    /* Cut the Conf names to fit into graph (by cutting words until desired length reached) */
    var CONFCUTLENGTH = 52;
    function cutConfName(confName)
    {
        var cutName = confName;
        if (cutName.length > CONFCUTLENGTH)
            cutName = cutName.replace("developer-build", "dev...");
        if (cutName.length > CONFCUTLENGTH)
            cutName = cutName.replace("shadow-build", "sha...");
        if (cutName.length > CONFCUTLENGTH)
            cutName = cutName.replace("qtnamespace", "qtnam...");
        if (cutName.length > CONFCUTLENGTH)
            cutName = cutName.replace("qtdeclarative", "qtdec...");
        if (cutName.length > CONFCUTLENGTH)
            cutName = cutName.replace("qtlibinfix", "qtlib...");
        if (cutName.length > CONFCUTLENGTH)
            cutName = cutName.replace("Ubuntu", "U...");
        return cutName;
    }

    /* Change seconds to minutes (non-zero value less than 60 s returned as 1 min) */
    var phaseQuickWithIdle = <?php echo PHASEQUICKWITHIDLE; ?>;         // Read the tag value
    function secToMin(seconds)
    {
        if (seconds == phaseQuickWithIdle)                              // Decode possible tagged value
            seconds = 1;
        var mins = Math.round(seconds/60);
        if (seconds > 0 && seconds < 60)
            mins = 1;
        return mins;
    }

    /* Read the data from the saved PHP arrays to JavaScript */
    var arrayPhases = new Array();
    arrayPhases = <?php echo json_encode($arrayGraphBuildPhases); ?>;   // Read the phase names
    var phaseCount = arrayPhases.length;
    var arrayConfs = new Array();
    arrayConfs = <?php echo json_encode($arrayGraphBuildConfs); ?>;     // Read the Conf names
    var confCount = arrayConfs.length;
    for (i=0; i<confCount; i++) {
        arrayConfs[i] = cutConfName(arrayConfs[i]);                     // Cut the Conf names to fit into graph
    }
    var i,j;
    var arrayConfPhases = new Array();
    arrayConfPhases = <?php echo json_encode($arrayGraphBuildConfPhaseDuration); ?>;     // Read the phase durations (copy PHP array into JavaScript)

    /* Read the data for the graph from the saved data above */
    var dataset = new Array();
    var datasetName;
    var datasetData = new Array();
    var datasetConfData;
    for (j=0; j<phaseCount; j++) {
        datasetName = arrayPhases[j];
        datasetData = [];
        for (i=0; i<confCount; i++) {
            datasetConfData = {
                conf: arrayConfs[i],
                count: secToMin(arrayConfPhases[i][j])
            };
            datasetData[i] = datasetConfData;
        }
        dataset[j] = {
            name: datasetName,
            data: datasetData
        };
    }

    /* Graph size and margins */
    var graphSize = {
        width: 1100,                                                    // Graph width
        height: 450                                                     // Graph height
    };
    var xAxisLabelVerticalPosition = 40;
    var margins = {                                                     // Graph margins
        top: 12,
        left: 325,
        right: 10,
        bottom: xAxisLabelVerticalPosition + 2                          // Reserved space for X axis label
    };
    var marginsLegendPanel = {
        left: 40                                                        // Legent margin from the graph area
    };
    var legendPanel = {
        width: 300                                                      // Legend width
    };

    /* Create the horizontal stacked bar graph with a legend */
    var series = dataset.map(function (d) {
        return d.name;
    });
    var dataset = dataset.map(function (d) {
        return d.data.map(function (o, i) {
            // Structure it so that your numeric axis (the stacked amount) is y
            return {
                y: o.count,
                x: o.conf
            };
        });
    });
    var stack = d3.layout.stack();
    stack(dataset);
    var dataset = dataset.map(function (group) {
        return group.map(function (d) {
            // Invert the x and y values, and y0 becomes x0
            return {
                x: d.y,
                y: d.x,
                x0: d.y0
            };
        });
    });

    var width = graphSize.width - margins.left - margins.right - legendPanel.width;     // Graph bar area width
    var height = graphSize.height - margins.top - margins.bottom;                       // Graph bar area height

    var svg = d3.select('.chart')                                                       // Create SVG element
            .append('svg')
            .attr('width', width + margins.left + margins.right + legendPanel.width)
            .attr('height', height + margins.top + margins.bottom)
            .append('g')
            .attr('transform', 'translate(' + margins.left + ',' + margins.top + ')'),
        xMax = d3.max(dataset, function (group) {
            return d3.max(group, function (d) {
                return d.x + d.x0;
            });
        }),
        xScale = d3.scale.linear()                                      // Create X scale for bar graph
            .domain([0, xMax])
            .range([0, width]),
        confs = dataset[0].map(function (d) {
            return d.y;
        }),
        _ = console.log(confs),
        yScale = d3.scale.ordinal()                                     // Create Y scale for bar graph
            .domain(confs)
            .rangeRoundBands([0, height], .1),
        xAxis = d3.svg.axis()                                           // Create X axis
            .scale(xScale)
            .orient('bottom'),
        yAxis = d3.svg.axis()                                           // Create Y axis
            .scale(yScale)
            .orient('left'),
        colors = d3.scale.ordinal()                                    // Series colors (idle times in white)
            .range([
                "#ffffff", "#cdd2da",                                   // cleaning
                "#ffffff", "#7b6888",                                   // git repos
                "#ffffff", "#f0e68c",                                   // configuring Qt
                "#ffffff", "#386cb0",                                   // qtqa tests       *) Exception: qtqa tests may be in two places, using the same color
                "#ffffff", "#98abc5",                                   // compiling Qt
                "#ffffff", "#ff8c00",                                   // installing Qt
                "#ffffff", "#a05d56",                                   // autotests
                "#ffffff", "#386cb0",                                   // qtqa tests       *)
                "#ffffff", "#abdda4",                                   // (reserved)
                "#ffffff", "#ffff99"                                    // (reserved)
            ]),
        groups = svg.selectAll('g')
            .data(dataset)
            .enter()
            .append('g')
            .style('fill', function (d, i) {
            return colors(i);
        }),
        rects = groups.selectAll('rect')
            .data(function (d) {
            return d;
        })
            .enter()
            .append('rect')
            .attr('x', function (d) {
            return xScale(d.x0);
        })
            .attr('y', function (d, i) {
            return yScale(d.y);
        })
            .attr('height', function (d) {
            return yScale.rangeBand();
        })
            .attr('width', function (d) {
            return xScale(d.x);
        })
            .on('mouseover', function (d) {
            var xPos = parseFloat(d3.select(this).attr('x')) / 2 + width / 2;
            var yPos = parseFloat(d3.select(this).attr('y')) + yScale.rangeBand() / 2;

            d3.select('#tooltipBuildPhases')
                .style('left', xPos + 'px')
                .style('top', yPos + 'px')
                .select('#valueBuildPhases')
                .text(d.x);

            d3.select('#tooltipBuildPhases').classed('hidden', false);
        })
            .on('mouseout', function () {
            d3.select('#tooltipBuildPhases').classed('hidden', true);
        });

    svg.append('g')
        .attr('class', 'axis')
        .attr('transform', 'translate(0,' + height + ')')
        .call(xAxis);

    svg.append("text")                                                  // Create X axis label
        .attr("class", "x label")
        .attr("text-anchor", "end")
        .attr("x", margins.left)
        .attr("y", height + xAxisLabelVerticalPosition)
        .text("Time from the Build start (in minutes)");

    svg.append('g')
        .attr('class', 'axis')
        .call(yAxis);

    svg.append('rect')                                                  // Legend definitions
        .attr('fill', 'white')                                          // Legend background color
        .attr('stroke', 'black')                                        // Legend border
        .attr('width', 170)                                             // Legend background width (inside the total width)
        .attr('height', 22 * dataset.length)                            // Legend height
        .attr('x', width + marginsLegendPanel.left)
        .attr('y', 0)
        .attr("rx", 5)                                                  // Legend rounded corners
        .attr("ry", 5);

    series.forEach(function (s, i) {
        svg.append('text')
            .attr('fill', 'black')
            .attr('x', width + marginsLegendPanel.left + 8)
            .attr('y', i * 20 + 20)
            .text(s);
        svg.append('rect')
            .attr('fill', colors(i))
            .attr('width', 60)                                          // Legend color width
            .attr('height', 20)                                         // Legend color height
            .attr('x', width + marginsLegendPanel.left + 100)           // Legent color from the text start
            .attr('y', i * 20 + 5);
    });

    /* Expandable section for graph and its data (jQuery accordion) */
    $(function() {
        $( ".graphAccordion" ).accordion({
            collapsible: true,
            heightStyle: "content"
        });
    });
    $(function() {
        $( ".dataAccordion" ).accordion({
            collapsible: true,
            active: false,                                              // Collapsed by default
            heightStyle: "content"
        });
    });

</script>
<!--<![endif]-->
