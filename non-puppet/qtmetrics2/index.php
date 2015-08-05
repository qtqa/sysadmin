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
 * @since     03-08-2015
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
    $buildProjectPlatformRoute = str_replace('/:targetOs', '', Slim\Slim::getInstance()->urlFor('buildproject_platform'));
    $app->render('home.html', array(
        'root' => Slim\Slim::getInstance()->urlFor('root'),
        'overviewRoute' => Slim\Slim::getInstance()->urlFor('overview'),
        'platformRoute' => $buildProjectPlatformRoute,
        'topRoute' => Slim\Slim::getInstance()->urlFor('top'),
        'flakyRoute' => Slim\Slim::getInstance()->urlFor('flaky'),
        'refreshed' => Factory::db()->getDbRefreshed() . ' (GMT)',
        'masterProject' => $ini['master_build_project'],
        'masterState' => $ini['master_build_state'],
        'branches' => Factory::db()->getBranches(),
        'platforms' => Factory::db()->getTargetPlatformOs()
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
    $buildProjectRoute = Slim\Slim::getInstance()->urlFor('buildproject');
    $testsetProjectRoute = str_replace('/:project', '', Slim\Slim::getInstance()->urlFor('testsetproject'));
    $app->render('overview.html', array(
        'root' => Slim\Slim::getInstance()->urlFor('root'),
        'breadcrumb' => $breadcrumb,
        'buildProjectRoute' => $buildProjectRoute,
        'testsetProjectRoute' => $testsetProjectRoute,
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
})->name('overview');

/**
 * UI route: /buildproject (GET)
 */

$app->get('/buildproject', function() use($app)
{
    $ini = Factory::conf();
    $breadcrumb = array(
        array('name' => 'home', 'link' => Slim\Slim::getInstance()->urlFor('root')),
        array('name' => 'overview', 'link' => Slim\Slim::getInstance()->urlFor('overview'))
    );
    $buildProjectPlatformRoute = str_replace('/:targetOs', '', Slim\Slim::getInstance()->urlFor('buildproject_platform'));
    $confRoute = str_replace('/:conf', '', Slim\Slim::getInstance()->urlFor('conf'));
    $app->render('build_project.html', array(
        'root' => Slim\Slim::getInstance()->urlFor('root'),
        'breadcrumb' => $breadcrumb,
        'buildPlatformRoute' => $buildProjectPlatformRoute,
        'confRoute' => $confRoute,
        'refreshed' => Factory::db()->getDbRefreshed() . ' (GMT)',
        'masterProject' => $ini['master_build_project'],
        'masterState' => $ini['master_build_state'],
        'platforms' => Factory::db()->getTargetPlatformOs(),
        'targetOs' => '',
        'latestProjectRuns' => Factory::db()->getLatestProjectBranchBuildResults(
            $ini['master_build_project'],
            $ini['master_build_state']),
        'projectRuns' => Factory::createProjectRuns(
            $ini['master_build_project'],
            $ini['master_build_state']),                // managed as objects
        'project' => Factory::createProject(
            $ini['master_build_project'],
            $ini['master_build_project'],
            $ini['master_build_state']),                // managed as object
        'confRuns' => Factory::createConfRuns(
            $ini['master_build_project'],
            $ini['master_build_state'],
            '',
            '')                                         // managed as objects
    ));
})->name('buildproject');

/**
 * UI route: /buildproject/:project/platform (GET)
 * Similar to /buildproject but filtered with os
 */

$app->get('/buildproject/platform/:targetOs', function($targetOs) use($app)
{
    $targetOs = strip_tags($targetOs);
    $ini = Factory::conf();
    $buildProjectRoute = str_replace('/:project', '', Slim\Slim::getInstance()->urlFor('buildproject'));
    $breadcrumb = array(
        array('name' => 'home', 'link' => Slim\Slim::getInstance()->urlFor('root')),
        array('name' => 'overview', 'link' => Slim\Slim::getInstance()->urlFor('overview')),
        array('name' => $ini['master_build_project'], 'link' => $buildProjectRoute)
    );
    $buildProjectPlatformRoute = str_replace('/:targetOs', '', Slim\Slim::getInstance()->urlFor('buildproject_platform'));
    $confRoute = str_replace('/:conf', '', Slim\Slim::getInstance()->urlFor('conf'));
    $app->render('build_project.html', array(
        'root' => Slim\Slim::getInstance()->urlFor('root'),
        'breadcrumb' => $breadcrumb,
        'buildPlatformRoute' => $buildProjectPlatformRoute,
        'confRoute' => $confRoute,
        'refreshed' => Factory::db()->getDbRefreshed() . ' (GMT)',
        'masterProject' => $ini['master_build_project'],
        'masterState' => $ini['master_build_state'],
        'platforms' => Factory::db()->getTargetPlatformOs(),
        'targetOs' => $targetOs,
        'latestProjectRuns' => Factory::db()->getLatestProjectBranchBuildResults(
            $ini['master_build_project'],
            $ini['master_build_state']),
        'projectRuns' => Factory::createProjectRuns(
            $ini['master_build_project'],
            $ini['master_build_state']),                // managed as objects
        'project' => Factory::createProject(
            $ini['master_build_project'],
            $ini['master_build_project'],
            $ini['master_build_state']),                // managed as object
        'confRuns' => Factory::createConfRuns(
            $ini['master_build_project'],
            $ini['master_build_state'],
            $targetOs,
            '')                                         // managed as objects
    ));
})->name('buildproject_platform');

/**
 * UI route: /testsetproject (GET)
 */

$app->get('/testsetproject/:project', function($project) use($app)
{
    $project = strip_tags($project);
    $ini = Factory::conf();
    $breadcrumb = array(
        array('name' => 'home', 'link' => Slim\Slim::getInstance()->urlFor('root')),
        array('name' => 'overview', 'link' => Slim\Slim::getInstance()->urlFor('overview'))
    );
    $confRoute = str_replace('/:conf', '', Slim\Slim::getInstance()->urlFor('conf'));
    $app->render('testset_project.html', array(
        'root' => Slim\Slim::getInstance()->urlFor('root'),
        'breadcrumb' => $breadcrumb,
        'confRoute' => $confRoute,
        'refreshed' => Factory::db()->getDbRefreshed() . ' (GMT)',
        'masterProject' => $ini['master_build_project'],
        'masterState' => $ini['master_build_state'],
        'project' => $project,
        'latestTestsetRuns' => Factory::db()->getLatestTestsetProjectBranchTestsetResults(
            $project,
            $ini['master_build_project'],
            $ini['master_build_state']),
        'projectRuns' => Factory::createProjectRuns(
            $ini['master_build_project'],
            $ini['master_build_state']),                // managed as objects
        'confBuilds' => Factory::db()->getTestsetProjectResultsByBranchConf(
            $project,
            $ini['master_build_project'],
            $ini['master_build_state'])
    ));
})->name('testsetproject');

/**
 * UI route: /conf/:conf (GET)
 */

$app->get('/conf/:conf', function($conf) use($app)
{
    $conf = strip_tags($conf);
    $ini = Factory::conf();
    $buildProjectRoute = str_replace('/:project', '', Slim\Slim::getInstance()->urlFor('buildproject'));
    $breadcrumb = array(
        array('name' => 'home', 'link' => Slim\Slim::getInstance()->urlFor('root')),
        array('name' => 'overview', 'link' => Slim\Slim::getInstance()->urlFor('overview')),
        array('name' => $ini['master_build_project'], 'link' => $buildProjectRoute)
    );
    $testsetRoute = str_replace('/:testset/:project', '', Slim\Slim::getInstance()->urlFor('testset'));
    $testsetProjectRoute = str_replace('/:project', '', Slim\Slim::getInstance()->urlFor('testsetproject'));
    $app->render('conf.html', array(
        'root' => Slim\Slim::getInstance()->urlFor('root'),
        'breadcrumb' => $breadcrumb,
        'testsetRoute' => $testsetRoute,
        'testsetProjectRoute' => $testsetProjectRoute,
        'refreshed' => Factory::db()->getDbRefreshed() . ' (GMT)',
        'masterProject' => $ini['master_build_project'],
        'masterState' => $ini['master_build_state'],
        'testsetProject' => '',
        'latestConfRuns' => Factory::db()->getLatestConfBranchBuildResults(
            $conf,
            $ini['master_build_project'],
            $ini['master_build_state']),
        'projectRuns' => Factory::createProjectRuns(
            $ini['master_build_project'],
            $ini['master_build_state']),                // managed as objects
        'conf' => Factory::createConf(
            $conf,
            $ini['master_build_project'],
            $ini['master_build_state']),                // managed as object
        'confRuns' => Factory::createConfRuns(
            $ini['master_build_project'],
            $ini['master_build_state'],
            '',
            $conf),                                     // managed as objects
        'testsetRuns' => Factory::createTestsetRunsInConf(
            $conf,
            '',
            $ini['master_build_project'],
            $ini['master_build_state'])                 // managed as objects
    ));
})->name('conf');

/**
 * UI route: /conf/:conf/:testsetproject (GET)
 */

$app->get('/conf/:conf/:testsetproject', function($conf, $testsetProject) use($app)
{
    $conf = strip_tags($conf);
    $testsetProject = strip_tags($testsetProject);
    $ini = Factory::conf();
    $testsetProjectRoute = str_replace('/:project', '', Slim\Slim::getInstance()->urlFor('testsetproject'));
    $breadcrumb = array(
        array('name' => 'home', 'link' => Slim\Slim::getInstance()->urlFor('root')),
        array('name' => 'overview', 'link' => Slim\Slim::getInstance()->urlFor('overview')),
        array('name' => $testsetProject, 'link' => $testsetProjectRoute . '/' . $testsetProject)
    );
    $testsetRoute = str_replace('/:testset/:project', '', Slim\Slim::getInstance()->urlFor('testset'));
    $app->render('conf.html', array(
        'root' => Slim\Slim::getInstance()->urlFor('root'),
        'breadcrumb' => $breadcrumb,
        'testsetRoute' => $testsetRoute,
        'testsetProjectRoute' => $testsetProjectRoute,
        'refreshed' => Factory::db()->getDbRefreshed() . ' (GMT)',
        'masterProject' => $ini['master_build_project'],
        'masterState' => $ini['master_build_state'],
        'testsetProject' => $testsetProject,
        'latestConfRuns' => null,                       // not used
        'projectRuns' => Factory::createProjectRuns(
            $ini['master_build_project'],
            $ini['master_build_state']),                // managed as objects
        'conf' => Factory::createConf(
            $conf,
            $ini['master_build_project'],
            $ini['master_build_state']),                // managed as object
        'confRuns' => Factory::createConfRuns(
            $ini['master_build_project'],
            $ini['master_build_state'],
            '',
            $conf),                                     // managed as objects
        'testsetRuns' => Factory::createTestsetRunsInConf(
            $conf,
            $testsetProject,
            $ini['master_build_project'],
            $ini['master_build_state'])                 // managed as objects
    ));
})->name('conf_testsetproject');

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
            $ini['master_build_state'])                 // managed as objects
    ));
})->name('top');

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
            null)                                       // managed as objects
    ));
})->name('flaky');

/**
 * UI route: /testset/:testset/:project (GET)
 */

$app->get('/testset/:testset/:project', function($testset, $project) use($app)
{
    $testset = strip_tags($testset);
    $project = strip_tags($project);
    if (Factory::checkTestset($testset)) {
        $ini = Factory::conf();
        $breadcrumb = array(
            array('name' => 'home', 'link' => Slim\Slim::getInstance()->urlFor('root'))
        );
        $confProjectRoute = str_replace('/:conf/:testsetproject', '', Slim\Slim::getInstance()->urlFor('conf_testsetproject'));
        $testsetProjectRoute = str_replace('/:project', '', Slim\Slim::getInstance()->urlFor('testsetproject'));
        $app->render('testset.html', array(
            'root' => Slim\Slim::getInstance()->urlFor('root'),
            'breadcrumb' => $breadcrumb,
            'confProjectRoute' => $confProjectRoute,
            'testsetProjectRoute' => $testsetProjectRoute,
            'refreshed' => Factory::db()->getDbRefreshed() . ' (GMT)',
            'lastDaysFailures' => $ini['top_failures_last_days'],
            'lastDaysFlaky' => $ini['flaky_testsets_last_days'],
            'sinceDateFailures' => Factory::getSinceDate(intval($ini['top_failures_last_days']) - 1),
            'sinceDateFlaky' => Factory::getSinceDate(intval($ini['flaky_testsets_last_days']) - 1),
            'masterProject' => $ini['master_build_project'],
            'masterState' => $ini['master_build_state'],
            'projectRuns' => Factory::createProjectRuns(
                $ini['master_build_project'],
                $ini['master_build_state']),                // managed as objects
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
})->name('testset');

/**
 * UI route: /sitemap (GET)
 */

$app->get('/sitemap', function() use($app)
{
    $breadcrumb = array(
        array('name' => 'home', 'link' => Slim\Slim::getInstance()->urlFor('root'))
    );
    $app->render('image.html', array(
        'root' => Slim\Slim::getInstance()->urlFor('root'),
        'breadcrumb' => $breadcrumb,
        'title' => 'Site Map',
        'navi_title' => 'site map',
        'image' => 'images/site_map.png'
    ));
})->name('sitemap');

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
