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
 * Qt Metrics API
 * @version   0.7
 * @since     30-06-2015
 * @author    Juha Sippola
 */

require_once 'src/Factory.php';
require_once 'lib/Slim/Slim/Slim.php';
require_once 'lib/Slim/Slim/View.php';
require_once 'lib/Slim/Slim/Views/Twig.php';
require_once 'lib/Twig/lib/Twig/Autoloader.php';

\Slim\Slim::registerAutoloader();
Twig_Autoloader::register();

$app = new Slim\Slim(array(
    'view' => new Slim\Views\Twig(),
    'templates.path' => 'templates'
));

/**
 * UI route: / (GET)
 */

$app->get('/', function() use($app)
{
    $ini = Factory::conf();
    $app->render('home.html', array(
        'root' => Slim\Slim::getInstance()->urlFor('root'),
        'overviewRoute' => Slim\Slim::getInstance()->urlFor('root') . 'overview',
        'branchRoute' => Slim\Slim::getInstance()->urlFor('root') . 'branch',
        'platformRoute' => Slim\Slim::getInstance()->urlFor('root') . 'platform',
        'testRoute' => Slim\Slim::getInstance()->urlFor('root') . 'test',
        'refreshed' => Factory::db()->getDbRefreshed() . ' (GMT)',
        'masterProject' => $ini['master_build_project'],
        'masterState' => $ini['master_build_state'],
        'branches' => Factory::db()->getBranches(),
        'platforms' => Factory::db()->getTargetPlatforms()
    ));
})->name('root');

/**
 * UI route: /overview (GET)
 */

$app->get('/overview', function() use($app)
{
    $ini = Factory::conf();
    $breadcrumb = array(
        array('name' => 'home', 'link' => Slim\Slim::getInstance()->urlFor('root'))
    );
    $app->render('overview.html', array(
        'root' => Slim\Slim::getInstance()->urlFor('root'),
        'breadcrumb' => $breadcrumb,
        'buildProjectRoute' => Slim\Slim::getInstance()->urlFor('root') . 'buildproject',
        'testsetProjectRoute' => Slim\Slim::getInstance()->urlFor('root') . 'testsetproject',
        'refreshed' => Factory::db()->getDbRefreshed() . ' (GMT)',
        'masterProject' => $ini['master_build_project'],
        'masterState' => $ini['master_build_state'],
        'latestProjectRuns' => Factory::db()->getLatestProjectBranchBuildResults(
            $ini['master_build_project'],
            $ini['master_build_state']),
        'latestTestsetRuns' => Factory::db()->getLatestProjectBranchTestsetResults(
            $ini['master_build_project'],
            $ini['master_build_state'])
    ));
});

/**
 * UI route: /buildproject (GET)
 */

$app->get('/buildproject/:project', function($project) use($app)
{
    $project = strip_tags($project);
    $ini = Factory::conf();
    $breadcrumb = array(
        array('name' => 'home', 'link' => Slim\Slim::getInstance()->urlFor('root'))
    );
    $app->render('build_project.html', array(
        'root' => Slim\Slim::getInstance()->urlFor('root'),
        'breadcrumb' => $breadcrumb,
        'refreshed' => Factory::db()->getDbRefreshed() . ' (GMT)',
        'masterProject' => $ini['master_build_project'],
        'masterState' => $ini['master_build_state'],
        'latestProjectRuns' => Factory::db()->getLatestProjectBranchBuildResults(
            $project,
            $ini['master_build_state']),
        'projectBuilds' => Factory::db()->getProjectBuildsByBranch(
            $ini['master_build_project'],
            $ini['master_build_state']),
        'project' => Factory::createProject(
            $project,
            $ini['master_build_project'],
            $ini['master_build_state']),                // managed as object
        'confRuns' => Factory::createConfRuns(
            $ini['master_build_project'],
            $ini['master_build_state'])                 // managed as objects
    ));
});

/**
 * UI route: /testsetproject (GET)
 */

$app->get('/testsetproject/:project', function($project) use($app)
{
    $project = strip_tags($project);
    $ini = Factory::conf();
    $breadcrumb = array(
        array('name' => 'home', 'link' => Slim\Slim::getInstance()->urlFor('root'))
    );
    $app->render('testset_project.html', array(
        'root' => Slim\Slim::getInstance()->urlFor('root'),
        'breadcrumb' => $breadcrumb,
        'refreshed' => Factory::db()->getDbRefreshed() . ' (GMT)',
        'masterProject' => $ini['master_build_project'],
        'masterState' => $ini['master_build_state'],
        'project' => $project,
        'latestTestsetRuns' => Factory::db()->getLatestTestsetProjectBranchTestsetResults(
            $project,
            $ini['master_build_project'],
            $ini['master_build_state']),
        'projectBuilds' => Factory::db()->getProjectBuildsByBranch(
            $ini['master_build_project'],
            $ini['master_build_state']),
        'confBuilds' => Factory::db()->getTestsetProjectResultsByBranchConf(
            $project,
            $ini['master_build_project'],
            $ini['master_build_state'])
    ));
});

/**
 * UI route: /test/top (GET)
 */

$app->get('/test/top', function() use($app)
{
    $ini = Factory::conf();
    $days = intval($ini['top_failures_last_days']) - 1;
    $since = Factory::getSinceDate($days);
    $breadcrumb = array(
        array('name' => 'home', 'link' => Slim\Slim::getInstance()->urlFor('root'))
    );
    $app->render('testsets_top.html', array(
        'root' => Slim\Slim::getInstance()->urlFor('root'),
        'breadcrumb' => $breadcrumb,
        'testsetRoute' => Slim\Slim::getInstance()->urlFor('root') . 'testset',
        'refreshed' => Factory::db()->getDbRefreshed() . ' (GMT)',
        'topN' => $ini['top_failures_n'],
        'lastDays' => $ini['top_failures_last_days'],
        'sinceDate' => $since,
        'masterProject' => $ini['master_build_project'],
        'masterState' => $ini['master_build_state'],
        'testsets' => Factory::createTestsets(
            Factory::LIST_FAILURES,
            $ini['master_build_project'],
            $ini['master_build_state'])                     // managed as objects
    ));
});

/**
 * UI route: /test/flaky (GET)
 */

$app->get('/test/flaky', function() use($app)
{
    $ini = Factory::conf();
    $days = intval($ini['flaky_testsets_last_days']) - 1;
    $since = Factory::getSinceDate($days);
    $breadcrumb = array(
        array('name' => 'home', 'link' => Slim\Slim::getInstance()->urlFor('root'))
    );
    $app->render('testsets_flaky.html', array(
        'root' => Slim\Slim::getInstance()->urlFor('root'),
        'breadcrumb' => $breadcrumb,
        'testsetRoute' => Slim\Slim::getInstance()->urlFor('root') . 'testset',
        'refreshed' => Factory::db()->getDbRefreshed() . ' (GMT)',
        'topN' => $ini['flaky_testsets_n'],
        'lastDays' => $ini['flaky_testsets_last_days'],
        'sinceDate' => $since,
        'testsets' => Factory::createTestsets(
            Factory::LIST_FLAKY,
            null,
            null)                                           // managed as objects
    ));
});

/**
 * UI route: /testset/:testset/:project (GET)
 */

$app->get('/testset/:testset/:project', function($testset, $project) use($app)
{
    $testset = strip_tags($testset);
    $project = strip_tags($project);
    $ini = Factory::conf();
    $breadcrumb = array(
        array('name' => 'home', 'link' => Slim\Slim::getInstance()->urlFor('root'))
    );
    if (Factory::checkTestset($testset)) {
        $app->render('testset.html', array(
            'root' => Slim\Slim::getInstance()->urlFor('root'),
            'breadcrumb' => $breadcrumb,
            'refreshed' => Factory::db()->getDbRefreshed() . ' (GMT)',
            'lastDaysFailures' => $ini['top_failures_last_days'],
            'lastDaysFlaky' => $ini['flaky_testsets_last_days'],
            'sinceDateFailures' => Factory::getSinceDate(intval($ini['top_failures_last_days']) - 1),
            'sinceDateFlaky' => Factory::getSinceDate(intval($ini['flaky_testsets_last_days']) - 1),
            'masterProject' => $ini['master_build_project'],
            'masterState' => $ini['master_build_state'],
            'projectBuilds' => Factory::db()->getProjectBuildsByBranch(
                $ini['master_build_project'],
                $ini['master_build_state']),
            'testset' => Factory::createTestset(
                $testset,
                $project,
                $ini['master_build_project'],
                $ini['master_build_state']),                // managed as object
            'testsetRuns' => Factory::createTestsetRuns(
                $testset,
                $project,
                $ini['master_build_project'],
                $ini['master_build_state'])                 // managed as objects
        ));
    } else {
        $app->render('empty.html', array(
            'root' => Slim\Slim::getInstance()->urlFor('root'),
            'message' => '404 Not Found'
        ));
        $app->response()->status(404);
    }
})->name('testsetProject');


$app->run();


/**
 * Handle the project name input selection on home page and redirect to testset project page
 */

if (isset($_POST["projectInputSubmit"])) {
    if (empty($_POST["projectInputValue"])) {
        header('Location: ' . Slim\Slim::getInstance()->urlFor('root'));
        exit();
    }
    if (isset($_POST["projectInputValue"])) {
        $project = htmlspecialchars($_POST['projectInputValue']);
        header('Location: ' . Slim\Slim::getInstance()->urlFor('root') . 'testsetproject/' . $project);
        exit();
    }
}

/**
 * Handle the testset name input selection on home page and redirect to testset page
 */

if (isset($_POST["testsetInputSubmit"])) {
    if (empty($_POST["testsetInputValue"])) {
        header('Location: ' . Slim\Slim::getInstance()->urlFor('root'));
        exit();
    }
    if (isset($_POST["testsetInputValue"])) {
        $string = explode(' (in ', htmlspecialchars($_POST['testsetInputValue']));     // the separator must match with that used in testset_search.php
        $testset = $string[0];
        $project = str_replace(')', '', $string[1]);
        header('Location: ' . Slim\Slim::getInstance()->urlFor('root') . 'testset/' . $testset . '/' . $project);
        exit();
    }
}

?>
