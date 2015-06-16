<?php
#############################################################################
##
## Copyright (C) 2015 The Qt Company Ltd.
## Contact: http://www.qt.io/licensing/
##
## This file is part of the Quality Assurance module of the Qt Toolkit.
##
## $QT_BEGIN_LICENSE:LGPL21$
## Commercial License Usage
## Licensees holding valid commercial Qt licenses may use this file in
## accordance with the commercial license agreement provided with the
## Software or, alternatively, in accordance with the terms contained in
## a written agreement between you and The Qt Company. For licensing terms
## and conditions see http://www.qt.io/terms-conditions. For further
## information use the contact form at http://www.qt.io/contact-us.
##
## GNU Lesser General Public License Usage
## Alternatively, this file may be used under the terms of the GNU Lesser
## General Public License version 2.1 or version 3 as published by the Free
## Software Foundation and appearing in the file LICENSE.LGPLv21 and
## LICENSE.LGPLv3 included in the packaging of this file. Please review the
## following information to ensure the GNU Lesser General Public License
## requirements will be met: https://www.gnu.org/licenses/lgpl.html and
## http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
##
## As a special exception, The Qt Company gives you certain additional
## rights. These rights are described in The Qt Company LGPL Exception
## version 1.1, included in the file LGPL_EXCEPTION.txt in this package.
##
## $QT_END_LICENSE$
##
#############################################################################

/**
 * Top failures (testsets) page
 * @version   0.2
 * @since     11-06-2015
 * @author    Juha Sippola
 */

include 'header.php';

// Failed/passed bar area size in px
const BAR_AREA = 120;

// Get input data
$breadcrumb = $this->data['breadcrumb'];
$testsetRoute = $this->data['testsetRoute'];
$refreshed = $this->data['refreshed'];
$topN = $this->data['topN'];
$lastDays = $this->data['lastDays'];
$sinceDate = $this->data['sinceDate'];
$masterProject = $this->data['masterProject'];
$masterState = $this->data['masterState'];
/**
 * @var Testset[] $testsets
 */
$testsets = $this->data['testsets'];

?>

<ol class="breadcrumb">
    <?php
    foreach ($breadcrumb as $link) {
        echo '<li><a href="' . $link['link'] . '">' . $link['name'] . '</a></li>';
    }
    ?>
    <li class="active">top failures</li>
</ol>

<div class="container-fluid">
    <div class="row">

        <div class="col-sm-12 col-md-12 main">

            <h1 class="page-header">
                <?php echo " Top $topN Failures" ?>
                <button type="button" class="btn btn-xs btn-info" data-toggle="collapse" data-target="#info" aria-expanded="false" aria-controls="info">
                    <span class="glyphicon glyphicon-info-sign"></span>
                </button>
                <small><?php echo $refreshed ?></small>
            </h1>
            <h3 class="sub-header"> <?php echo "Last $lastDays days <small>(since $sinceDate)</small>" ?> </h3>

            <div class="collapse" id="info">
                <div class="well infoWell">
                    <span class="glyphicon glyphicon-info-sign"></span> <strong>Top failures</strong><br>
                    <ul>
                        <li>Lists testsets by number of <strong><?php echo "$masterProject $masterState" ?></strong>
                            builds where it failed during the last <?php echo $lastDays ?> days.</li>
                        <li><strong>latest result</strong> shows the overall testset status based on the latest
                            <strong><?php echo "$masterProject $masterState" ?></strong> builds across all branches
                            (shows failed if failed in one or in several).</li>
                    </ul>
                </div>
            </div>

            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>testset</th>
                                <th class="showInLargeDisplay">project</th>
                                <th>latest result</th>
                                <th class="leftBorder center">failed <span class ="gray">(total)</span></th>
                                <th class="showInLargeDisplay">failed + passed</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Calculate max result count for the bar
                            $maxCount = 1;
                            foreach ($testsets as $testset) {
                                if ($testset->getTestsetResultCounts()['passed'] + $testset->getTestsetResultCounts()['failed'] > $maxCount)
                                    $maxCount = $testset->getTestsetResultCounts()['passed'] + $testset->getTestsetResultCounts()['failed'];
                            }
                            // Print testsets
                            foreach ($testsets as $testset) {
                                echo '<tr>';
                                    // Testset name
                                    echo '<td><a href="' . $testsetRoute . '/' . $testset->getName() . '">' .
                                        $testset->getName() . '</a></td>';
                                    // Project name
                                    echo '<td class="showInLargeDisplay">' . $testset->getProjectName() . '</td>';
                                    // Testset status according to the latest build results
                                    $resultIcon = '';
                                    if ($testset->getStatus() == testsetRun::RESULT_SUCCESS)
                                        $resultIcon = 'glyphicon glyphicon-ok green';
                                    if ($testset->getStatus() == testsetRun::RESULT_FAILURE)
                                        $resultIcon = 'glyphicon glyphicon-remove red';
                                    echo '<td><span class="spaceHorizontal ' . $resultIcon . '"></span>' . $testset->getStatus() . '</td>';
                                    // Show results as numbers
                                    $passed = $testset->getTestsetResultCounts()['passed'];
                                    $failed = $testset->getTestsetResultCounts()['failed'];
                                    $total = $passed + $failed;
                                    echo '<td class="leftBorder center">' . $failed . '<span class ="gray"> (' . $total . ')</span></td>';
                                    // Show results as bars (scaled to BAR_AREA px)
                                    $passedBar = floor((BAR_AREA/$maxCount) * $passed);
                                    if ($passed > 0 and $passedBar == 0)
                                        $passedBar = 1;
                                    $failedBar = floor((BAR_AREA/$maxCount) * $failed);
                                    if ($failed > 0 and $failedBar == 0)
                                        $failedBar = 1;
                                    echo '<td class="center showInLargeDisplay">';
                                        echo '<div>';
                                            echo '<div class="floatLeft redBackground" style="width: ' . $failedBar . 'px">&nbsp;</div>';
                                            echo '<div class="floatLeft greenBackground" style="width: ' . $passedBar . 'px">&nbsp;</div>';
                                        echo '</div>';
                                    echo '</td>';
                                echo '</tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div> <!-- /table-responsive -->
            </div> <!-- /panel-body -->

        </div> <!-- /col... -->
    </div> <!-- /row -->
</div> <!-- /container-fluid -->

<?php
include 'footer.php';
?>

<!-- Local scripts for this page -->

<?php
include 'close.php';
?>
