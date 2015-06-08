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
 * Home page
 * @version   0.1
 * @since     02-06-2015
 * @author    Juha Sippola
 */

include 'header.php';

// Get input data
$overviewRoute = $this->data['overviewRoute'];
$branchRoute = $this->data['branchRoute'];
$platformRoute = $this->data['platformRoute'];
$testRoute = $this->data['testRoute'];
$refreshed = $this->data['refreshed'];
$states = $this->data['states'];
$branches = $this->data['branches'];
$projects = $this->data['projects'];
$platforms = $this->data['platforms'];

?>

<div class="jumbotron">
    <div class="container">
        <div class="col-md-9">
            <h1>Qt Metrics <sup class="masterVersion">2</sup></h1>
        </div>
        <div class="col-md-3">
            <div class="well well-sm">
                <p>Data updated:</p>
                <?php
                echo $refreshed;
                ?>
            </div>
        </div>
    </div>
</div>

<div class="row">

    <div class="col-md-6">
        <h2>Overview</h2>
        <p>See the overview of the builds by projects across the latest branches:</p>
        <div>
            <?php
            foreach ($states as $state) {
                echo '<a class="btn btn-default btn-xs" disabled="disabled" href="' . $overviewRoute . '/' . $state['name'] . '" role="button">' . $state['name'] . '</a>';
            }
            ?>
        </div>
    </div>

    <div class="col-md-6">
        <h2>Platforms</h2>
        <p>See target platform status:</p>
        <div>
            <?php
            $os = array();
            foreach ($platforms as $platform) {
                $os[] = $platform['os'];
            }
            $os = array_unique($os);
            foreach ($os as $os_name) {
                echo '<div class="btn-group">';
                echo '<button type="button" class="btn btn-default btn-xs dropdown-toggle" disabled="disabled" data-toggle="dropdown" aria-expanded="false">';
                echo $os_name . ' <span class="caret"></span>';
                echo '</button>';
                echo '<ul class="dropdown-menu textSmall" role="menu">';
                foreach ($platforms as $platform) {
                    if ($platform['os'] == $os_name) {
                        echo '<li><a href="' .  $platformRoute . '/' . $os_name . '/' . $platform['os_version'] . '">';
                        echo (empty($platform['os_version']) ? '(no version)' : $platform['os_version']);
                        echo '</a></li>';
                    }
                }
                echo '</ul>';
                echo '</div>';
            }
            ?>
        </div>
    </div>
</div>

<div class="row">

    <div class="col-md-6">
        <h2>Branches</h2>
        <p>See branch status:</p>
        <div>
            <?php
            foreach ($branches as $branch) {
                echo '<a class="btn btn-default btn-xs" disabled="disabled" href="' . $branchRoute . '/' . $branch['name'] . '" role="button">' . $branch['name'] . '</a>';
            }
            ?>
        </div>
    </div>

    <div class="col-md-4">
        <h2>Tests</h2>
        <p>See top failure lists or single testset results:</p>
        <div>
            <?php
            echo '<a class="btn btn-default btn-xs" href="' . $testRoute . '/top' . '" role="button">top failures</a>';
            echo '<a class="btn btn-default btn-xs" href="' . $testRoute . '/flaky' . '" role="button">flaky testsets</a>';
            ?>
        </div>
        <div>
            <form class="form-horizontal" role="form" method="post">
            <div class="input-group input-group-sm">
                <input id="testsetInput" name="testsetInputValue" type="text" class="form-control" placeholder="testset name...">
                <span class="input-group-btn">
                    <?php
                    if (isset($_POST['testsetInputValue']))
                        $testsetSelected = $_POST['testsetInputValue'];
                        echo '<input id="testsetInputSubmit" name="testsetInputSubmit" type="submit" class="btn btn-default" value="Show">';
                    ?>
                </span>
            </div><!-- /input-group -->
            </form>
        </div>
    </div>
</div>

<div class="row">

    <div class="col-md-6">
        <h2>Projects</h2>
        <p>See project status:</p>
        <div>
            <?php
            foreach ($projects as $project) {
                // Show button color based on project status (according to the latest build results)
                $buttonStatus = 'btn-default';
                if ($project->getStatus() == ProjectRun::RESULT_SUCCESS)
                    $buttonStatus = 'btn-success';
                if ($project->getStatus() == ProjectRun::RESULT_FAILURE)
                    $buttonStatus = 'btn-danger';
                // Show only valid projects
                if ($project->getStatus() <> ProjectRun::RESULT_EMPTY)
                    echo '<a class="btn ' . $buttonStatus . ' btn-xs" disabled="disabled"
                        href="' . $overviewRoute . '/state/' . $project->getName() . '"
                        role="button">' . $project->getName() . '</a>';
            }
            ?>
        </div>
    </div>
</div>

<br>
<div class="alert alert-danger" role="alert">
    <strong>Under construction!</strong> Only few subpages implemented at the moment (those buttons enabled).
</div>

<?php
include 'footer.php';
?>

<!-- Local scripts for this page -->
<script src="scripts/testset_autocomplete.js"></script>

<?php
include 'close.php';
?>
