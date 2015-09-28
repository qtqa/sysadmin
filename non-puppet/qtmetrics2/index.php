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
 * @since     28-09-2015
 * @author    Juha Sippola
 */

require_once 'lib/Slim/Slim/Slim.php';
require_once 'lib/Slim/Slim/View.php';
require_once 'lib/Slim/Slim/Middleware.php';
require_once 'lib/Slim/Slim/Views/Twig.php';
require_once 'lib/Twig/lib/Twig/Autoloader.php';
require_once 'src/Factory.php';
require_once 'src/HttpBasicAuth.php';
require_once 'src/HttpBasicAuthRoute.php';

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
    $dbStatus = Factory::db()->getDbRefreshStatus();
    $buildProjectPlatformRoute = str_replace('/:targetOs', '', Slim\Slim::getInstance()->urlFor('buildproject_platform'));
    $app->render('home.html', array(
        'root' => Slim\Slim::getInstance()->urlFor('root'),
        'dbStatus' => $dbStatus,
        'refreshed' => $dbStatus['refreshed'] . ' (GMT)',
        'overviewRoute' => Slim\Slim::getInstance()->urlFor('overview'),
        'dashboardRoute' => Slim\Slim::getInstance()->urlFor('dashboard'),
        'platformRoute' => $buildProjectPlatformRoute,
        'topRoute' => Slim\Slim::getInstance()->urlFor('top'),
        'flakyRoute' => Slim\Slim::getInstance()->urlFor('flaky'),
        'topTestfunctionsRoute' => Slim\Slim::getInstance()->urlFor('toptestfunctions'),
        'durationTestsetsRoute' => Slim\Slim::getInstance()->urlFor('durationTestsets'),
        'bpassedTestfunctionsRoute' => Slim\Slim::getInstance()->urlFor('bpassed'),
        'masterProject' => $ini['master_build_project'],
        'masterState' => $ini['master_build_state'],
        'branches' => Factory::db()->getBranches(),
        'platforms' => Factory::db()->getTargetPlatformOs()
    ));
})->name('root');

/**
 * UI route: /dashboard (GET)
 */

$app->get('/dashboard', function() use($app)
{
    $ini = Factory::conf();
    $dbStatus = Factory::db()->getDbRefreshStatus();
    $breadcrumb = array(
        array('name' => 'home', 'link' => Slim\Slim::getInstance()->urlFor('root'))
    );
    $overviewRoute = Slim\Slim::getInstance()->urlFor('overview');
    $buildProjectRoute = Slim\Slim::getInstance()->urlFor('buildproject');
    $app->render('dashboard.html', array(
        'root' => Slim\Slim::getInstance()->urlFor('root'),
        'refreshed' => $dbStatus['refreshed'] . ' (GMT)',
        'breadcrumb' => $breadcrumb,
        'overviewRoute' => $overviewRoute,
        'buildProjectRoute' => $buildProjectRoute,
        'masterProject' => $ini['master_build_project'],
        'masterState' => $ini['master_build_state'],
        'latestConfRuns' => Factory::db()->getLatestConfBranchBuildResultsSum(
            $ini['master_build_project'],
            $ini['master_build_state']),
        'latestTestsetRuns' => Factory::db()->getLatestProjectBranchTestsetResultsSum(
            $ini['master_build_project'],
            $ini['master_build_state'])
    ));
})->name('dashboard');

/**
 * UI route: /overview (GET)
 */

$app->get('/overview', function() use($app)
{
    $ini = Factory::conf();
    $dbStatus = Factory::db()->getDbRefreshStatus();
    $breadcrumb = array(
        array('name' => 'home', 'link' => Slim\Slim::getInstance()->urlFor('root'))
    );
    $buildProjectRoute = Slim\Slim::getInstance()->urlFor('buildproject');
    $testsetProjectRoute = str_replace('/:project', '', Slim\Slim::getInstance()->urlFor('testsetproject'));
    $app->render('overview.html', array(
        'root' => Slim\Slim::getInstance()->urlFor('root'),
        'dbStatus' => $dbStatus,
        'refreshed' => $dbStatus['refreshed'] . ' (GMT)',
        'breadcrumb' => $breadcrumb,
        'buildProjectRoute' => $buildProjectRoute,
        'testsetProjectRoute' => $testsetProjectRoute,
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
    $dbStatus = Factory::db()->getDbRefreshStatus();
    $breadcrumb = array(
        array('name' => 'home', 'link' => Slim\Slim::getInstance()->urlFor('root')),
        array('name' => 'overview', 'link' => Slim\Slim::getInstance()->urlFor('overview'))
    );
    $buildProjectPlatformRoute = str_replace('/:targetOs', '', Slim\Slim::getInstance()->urlFor('buildproject_platform'));
    $confRoute = str_replace('/:conf', '', Slim\Slim::getInstance()->urlFor('conf'));
    $app->render('build_project.html', array(
        'root' => Slim\Slim::getInstance()->urlFor('root'),
        'dbStatus' => $dbStatus,
        'refreshed' => $dbStatus['refreshed'] . ' (GMT)',
        'breadcrumb' => $breadcrumb,
        'buildPlatformRoute' => $buildProjectPlatformRoute,
        'confRoute' => $confRoute,
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
    $dbStatus = Factory::db()->getDbRefreshStatus();
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
        'dbStatus' => $dbStatus,
        'refreshed' => $dbStatus['refreshed'] . ' (GMT)',
        'breadcrumb' => $breadcrumb,
        'buildPlatformRoute' => $buildProjectPlatformRoute,
        'confRoute' => $confRoute,
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
    $dbStatus = Factory::db()->getDbRefreshStatus();
    $breadcrumb = array(
        array('name' => 'home', 'link' => Slim\Slim::getInstance()->urlFor('root')),
        array('name' => 'overview', 'link' => Slim\Slim::getInstance()->urlFor('overview'))
    );
    $app->render('testset_project.html', array(
        'root' => Slim\Slim::getInstance()->urlFor('root'),
        'dbStatus' => $dbStatus,
        'refreshed' => $dbStatus['refreshed'] . ' (GMT)',
        'breadcrumb' => $breadcrumb,
        'masterProject' => $ini['master_build_project'],
        'masterState' => $ini['master_build_state'],
        'project' => $project
    ));
})->name('testsetproject');

$app->get('/data/testsetproject/latest/:project', function($project) use($app)
{
$project = strip_tags($project);
    $ini = Factory::conf();
    $app->render('testset_project_data_latest.html', array(
        'project' => $project,
        'latestTestsetRuns' => Factory::db()->getLatestTestsetProjectBranchTestsetResults(
            $project,
            $ini['master_build_project'],
            $ini['master_build_state'])
    ));
});

$app->get('/data/testsetproject/results/:project', function($project) use($app)
{
$project = strip_tags($project);
    $ini = Factory::conf();
    $confRoute = str_replace('/:conf', '', Slim\Slim::getInstance()->urlFor('conf'));
    $app->render('testset_project_data_results.html', array(
        'confRoute' => $confRoute,
        'project' => $project,
        'projectRuns' => Factory::createProjectRuns(
            $ini['master_build_project'],
            $ini['master_build_state']),                // managed as objects
        'confBuilds' => Factory::db()->getTestsetProjectResultsByBranchConf(
            $project,
            $ini['master_build_project'],
            $ini['master_build_state'])
    ));
});

/**
 * UI route: /conf/:conf (GET)
 */

$app->get('/conf/:conf', function($conf) use($app)
{
    $conf = strip_tags($conf);
    $ini = Factory::conf();
    $dbStatus = Factory::db()->getDbRefreshStatus();
    $buildProjectRoute = str_replace('/:project', '', Slim\Slim::getInstance()->urlFor('buildproject'));
    $breadcrumb = array(
        array('name' => 'home', 'link' => Slim\Slim::getInstance()->urlFor('root')),
        array('name' => 'overview', 'link' => Slim\Slim::getInstance()->urlFor('overview')),
        array('name' => $ini['master_build_project'], 'link' => $buildProjectRoute)
    );
    $testsetTestfunctionsRoute = str_replace('/:testset/:project/:conf', '', Slim\Slim::getInstance()->urlFor('testset_testfunctions'));
    $testsetProjectRoute = str_replace('/:project', '', Slim\Slim::getInstance()->urlFor('testsetproject'));
    $app->render('conf.html', array(
        'root' => Slim\Slim::getInstance()->urlFor('root'),
        'dbStatus' => $dbStatus,
        'refreshed' => $dbStatus['refreshed'] . ' (GMT)',
        'breadcrumb' => $breadcrumb,
        'testsetTestfunctionsRoute' => $testsetTestfunctionsRoute,
        'testsetProjectRoute' => $testsetProjectRoute,
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
    $dbStatus = Factory::db()->getDbRefreshStatus();
    $testsetProjectRoute = str_replace('/:project', '', Slim\Slim::getInstance()->urlFor('testsetproject'));
    $breadcrumb = array(
        array('name' => 'home', 'link' => Slim\Slim::getInstance()->urlFor('root')),
        array('name' => 'overview', 'link' => Slim\Slim::getInstance()->urlFor('overview')),
        array('name' => $testsetProject, 'link' => $testsetProjectRoute . '/' . $testsetProject)
    );
    $testsetTestfunctionsRoute = str_replace('/:testset/:project/:conf', '', Slim\Slim::getInstance()->urlFor('testset_testfunctions'));
    $app->render('conf.html', array(
        'root' => Slim\Slim::getInstance()->urlFor('root'),
        'dbStatus' => $dbStatus,
        'refreshed' => $dbStatus['refreshed'] . ' (GMT)',
        'breadcrumb' => $breadcrumb,
        'testsetTestfunctionsRoute' => $testsetTestfunctionsRoute,
        'testsetProjectRoute' => $testsetProjectRoute,
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
    $dbStatus = Factory::db()->getDbRefreshStatus();
    $days = intval($ini['top_failures_last_days']) - 1;
    $since = Factory::getSinceDate($days);
    $breadcrumb = array(
        array('name' => 'home', 'link' => Slim\Slim::getInstance()->urlFor('root'))
    );
    $app->render('testsets_top.html', array(
        'root' => Slim\Slim::getInstance()->urlFor('root'),
        'dbStatus' => $dbStatus,
        'refreshed' => $dbStatus['refreshed'] . ' (GMT)',
        'breadcrumb' => $breadcrumb,
        'topN' => $ini['top_failures_n'],
        'lastDays' => $ini['top_failures_last_days'],
        'sinceDate' => $since,
        'masterProject' => $ini['master_build_project'],
        'masterState' => $ini['master_build_state']
    ));
})->name('top');

$app->get('/data/test/top', function() use($app)
{
    $ini = Factory::conf();
    $days = intval($ini['top_failures_last_days']) - 1;
    $since = Factory::getSinceDate($days);
    $app->render('testsets_top_data.html', array(
        'testsetRoute' => Slim\Slim::getInstance()->urlFor('root') . 'testset',
        'lastDays' => $ini['top_failures_last_days'],
        'sinceDate' => $since,
        'testsets' => Factory::createTestsets(
            Factory::LIST_FAILURES,
            $ini['master_build_project'],
            $ini['master_build_state'])                 // managed as objects
    ));
});

/**
 * UI route: /test/flaky (GET)
 */

$app->get('/test/flaky', function() use($app)
{
    $ini = Factory::conf();
    $dbStatus = Factory::db()->getDbRefreshStatus();
    $days = intval($ini['flaky_testsets_last_days']) - 1;
    $since = Factory::getSinceDate($days);
    $breadcrumb = array(
        array('name' => 'home', 'link' => Slim\Slim::getInstance()->urlFor('root'))
    );
    $app->render('testsets_flaky.html', array(
        'root' => Slim\Slim::getInstance()->urlFor('root'),
        'dbStatus' => $dbStatus,
        'refreshed' => $dbStatus['refreshed'] . ' (GMT)',
        'breadcrumb' => $breadcrumb,
        'topN' => $ini['flaky_testsets_n'],
        'lastDays' => $ini['flaky_testsets_last_days'],
        'sinceDate' => $since
    ));
})->name('flaky');

$app->get('/data/test/flaky', function() use($app)
{
    $ini = Factory::conf();
    $days = intval($ini['flaky_testsets_last_days']) - 1;
    $since = Factory::getSinceDate($days);
    $app->render('testsets_flaky_data.html', array(
        'testsetRoute' => Slim\Slim::getInstance()->urlFor('root') . 'testset',
        'lastDays' => $ini['flaky_testsets_last_days'],
        'sinceDate' => $since,
        'testsets' => Factory::createTestsets(
            Factory::LIST_FLAKY,
            null,
            null)                                       // managed as objects
    ));
});

/**
 * UI route: /test/top/testfunctions (GET)
 */

$app->get('/test/top/testfunctions', function() use($app)
{
    $ini = Factory::conf();
    $dbStatus = Factory::db()->getDbRefreshStatus();
    $days = intval($ini['top_failures_last_days']) - 1;
    $since = Factory::getSinceDate($days);
    $breadcrumb = array(
        array('name' => 'home', 'link' => Slim\Slim::getInstance()->urlFor('root'))
    );
    $app->render('testfunctions_top.html', array(
        'root' => Slim\Slim::getInstance()->urlFor('root'),
        'dbStatus' => $dbStatus,
        'refreshed' => $dbStatus['refreshed'] . ' (GMT)',
        'breadcrumb' => $breadcrumb,
        'topN' => $ini['top_failures_n'],
        'lastDays' => $ini['top_failures_last_days'],
        'sinceDate' => $since,
        'masterProject' => $ini['master_build_project'],
        'masterState' => $ini['master_build_state']
    ));
})->name('toptestfunctions');

$app->get('/data/test/top/testfunctions', function() use($app)
{
    $ini = Factory::conf();
    $days = intval($ini['top_failures_last_days']) - 1;
    $since = Factory::getSinceDate($days);
    $app->render('testfunctions_top_data.html', array(
        'testsetRoute' => Slim\Slim::getInstance()->urlFor('root') . 'testset',
        'lastDays' => $ini['top_failures_last_days'],
        'sinceDate' => $since,
        'testfunctions' => Factory::createTestfunctions(
            Factory::LIST_FAILURES,
            '',
            '',
            $ini['master_build_project'],
            $ini['master_build_state'])                 // managed as objects
    ));
});

/**
 * UI route: /test/bpassed (GET)
 */

$app->get('/test/bpassed', function() use($app)
{
    $ini = Factory::conf();
    $dbStatus = Factory::db()->getDbRefreshStatus();
    $days = intval($ini['blacklisted_pass_last_days']) - 1;
    $since = Factory::getSinceDate($days);
    $breadcrumb = array(
        array('name' => 'home', 'link' => Slim\Slim::getInstance()->urlFor('root'))
    );
    $app->render('testfunctions_bpass.html', array(
        'root' => Slim\Slim::getInstance()->urlFor('root'),
        'dbStatus' => $dbStatus,
        'refreshed' => $dbStatus['refreshed'] . ' (GMT)',
        'breadcrumb' => $breadcrumb,
        'lastDays' => $ini['blacklisted_pass_last_days'],
        'sinceDate' => $since,
        'testset' => '',
        'project' => '',
        'masterProject' => $ini['master_build_project'],
        'masterState' => $ini['master_build_state']
    ));
})->name('bpassed');

$app->get('/data/test/bpassed', function() use($app)
{
    $ini = Factory::conf();
    $days = intval($ini['blacklisted_pass_last_days']) - 1;
    $since = Factory::getSinceDate($days);
    $app->render('testfunctions_bpass_data.html', array(
        'testsetRoute' => Slim\Slim::getInstance()->urlFor('root') . 'testset',
        'lastDays' => $ini['blacklisted_pass_last_days'],
        'sinceDate' => $since,
        'list' => 'functions',
        'testset' => '',
        'project' => '',
        'tests' => Factory::createTestfunctions(
            Factory::LIST_BPASSES,
            '',
            '',
            $ini['master_build_project'],
            $ini['master_build_state'])                 // managed as objects
    ));
});

/**
 * UI route: /test/bpassed/:testset/:project (GET)
 */

$app->get('/test/bpassed/:testset/:project', function($testset, $project) use($app)
{
    $ini = Factory::conf();
    $dbStatus = Factory::db()->getDbRefreshStatus();
    if (Factory::checkTestset($testset)) {
        $days = intval($ini['blacklisted_pass_last_days']) - 1;
        $since = Factory::getSinceDate($days);
        $testsetRoute = str_replace('/:testset/:project', '', Slim\Slim::getInstance()->urlFor('testset'));
        $breadcrumb = array(
            array('name' => 'home', 'link' => Slim\Slim::getInstance()->urlFor('root')),
            array('name' => $testset, 'link' => $testsetRoute . '/' . $testset . '/' . $project)
        );
        $app->render('testfunctions_bpass.html', array(
            'root' => Slim\Slim::getInstance()->urlFor('root'),
            'dbStatus' => $dbStatus,
            'refreshed' => $dbStatus['refreshed'] . ' (GMT)',
            'breadcrumb' => $breadcrumb,
            'lastDays' => $ini['blacklisted_pass_last_days'],
            'sinceDate' => $since,
            'testset' => $testset,
            'project' => $project,
            'masterProject' => $ini['master_build_project'],
            'masterState' => $ini['master_build_state']
        ));
    } else {
        $app->render('empty.html', array(
            'root' => Slim\Slim::getInstance()->urlFor('root'),
            'dbStatus' => $dbStatus,
            'message' => '404 Not Found'
        ));
        $app->response()->status(404);
    }
})->name('bpassedtestset');

$app->get('/data/test/bpassed/:testset/:project', function($testset, $project) use($app)
{
    $ini = Factory::conf();
    $days = intval($ini['blacklisted_pass_last_days']) - 1;
    $since = Factory::getSinceDate($days);
    $testsetRoute = str_replace('/:testset/:project', '', Slim\Slim::getInstance()->urlFor('testset'));
    $app->render('testfunctions_bpass_data.html', array(
        'testsetRoute' => $testsetRoute,
        'lastDays' => $ini['blacklisted_pass_last_days'],
        'sinceDate' => $since,
        'list' => 'functions',
        'testset' => $testset,
        'project' => $project,
        'tests' => Factory::createTestfunctions(
            Factory::LIST_BPASSES,
            $testset,
            $project,
            $ini['master_build_project'],
            $ini['master_build_state'])                 // managed as objects
    ));
});

/**
 * UI route: /test/bpassed/testrows/:testset/:project (GET)
 */

$app->get('/test/bpassed/testrows/:testset/:project', function($testset, $project) use($app)
{
    $ini = Factory::conf();
    $dbStatus = Factory::db()->getDbRefreshStatus();
    if (Factory::checkTestset($testset)) {
        $days = intval($ini['blacklisted_pass_last_days']) - 1;
        $since = Factory::getSinceDate($days);
        $testsetRoute = str_replace('/:testset/:project', '', Slim\Slim::getInstance()->urlFor('testset'));
        $breadcrumb = array(
            array('name' => 'home', 'link' => Slim\Slim::getInstance()->urlFor('root')),
            array('name' => $testset, 'link' => $testsetRoute . '/' . $testset . '/' . $project)
        );
        $app->render('testrows_bpass.html', array(
            'root' => Slim\Slim::getInstance()->urlFor('root'),
            'dbStatus' => $dbStatus,
            'refreshed' => $dbStatus['refreshed'] . ' (GMT)',
            'breadcrumb' => $breadcrumb,
            'lastDays' => $ini['blacklisted_pass_last_days'],
            'sinceDate' => $since,
            'testset' => $testset,
            'project' => $project,
            'masterProject' => $ini['master_build_project'],
            'masterState' => $ini['master_build_state']
        ));
    } else {
        $app->render('empty.html', array(
            'root' => Slim\Slim::getInstance()->urlFor('root'),
            'dbStatus' => $dbStatus,
            'message' => '404 Not Found'
        ));
        $app->response()->status(404);
    }
})->name('bpassedtestsetTestrows');

$app->get('/data/test/bpassed/testrows/:testset/:project', function($testset, $project) use($app)
{
    $ini = Factory::conf();
    $days = intval($ini['blacklisted_pass_last_days']) - 1;
    $since = Factory::getSinceDate($days);
    $testfunctionRoute = str_replace('/:testfunction/:testset/:project/:conf', '', Slim\Slim::getInstance()->urlFor('testfunction'));
    $app->render('testfunctions_bpass_data.html', array(
        'testfunctionRoute' => $testfunctionRoute,
        'lastDays' => $ini['blacklisted_pass_last_days'],
        'sinceDate' => $since,
        'list' => 'rows',
        'testset' => $testset,
        'project' => $project,
        'tests' => Factory::createTestrows(
            $testset,
            $project,
            $ini['master_build_project'],
            $ini['master_build_state'])                 // managed as objects
    ));
});

/**
 * UI route: /test/duration/testsets (GET)
 */

$app->get('/test/duration/testsets', function() use($app)
{
    $ini = Factory::conf();
    $dbStatus = Factory::db()->getDbRefreshStatus();
    $days = intval($ini['top_duration_last_days']) - 1;
    $since = Factory::getSinceDate($days);
    $breadcrumb = array(
        array('name' => 'home', 'link' => Slim\Slim::getInstance()->urlFor('root'))
    );
    $app->render('testsets_duration.html', array(
        'root' => Slim\Slim::getInstance()->urlFor('root'),
        'dbStatus' => $dbStatus,
        'refreshed' => $dbStatus['refreshed'] . ' (GMT)',
        'breadcrumb' => $breadcrumb,
        'lastDays' => $ini['top_duration_last_days'],
        'sinceDate' => $since,
        'durationLimitSec' => $ini['testset_top_duration_limit_sec'],
        'masterProject' => $ini['master_build_project'],
        'masterState' => $ini['master_build_state']
    ));
})->name('durationTestsets');

$app->get('/data/test/duration/testsets', function() use($app)
{
    $ini = Factory::conf();
    $days = intval($ini['top_duration_last_days']) - 1;
    $since = Factory::getSinceDate($days);
    $app->render('testsets_duration_data.html', array(
        'testsetRoute' => Slim\Slim::getInstance()->urlFor('root') . 'testset',
        'lastDays' => $ini['top_duration_last_days'],
        'sinceDate' => $since,
        'durationLimitSec' => $ini['testset_top_duration_limit_sec'],
        'list' => 'testsets',
        'testset' => '',
        'project' => '',
        'runs' => Factory::createTestsetRunsMaxDuration(
            $ini['master_build_project'],
            $ini['master_build_state'])                 // managed as objects
    ));
});

/**
 * UI route: /test/duration/testfunctions/:testset/:project (GET)
 */

$app->get('/test/duration/testfunctions/:testset/:project', function($testset, $project) use($app)
{
    $ini = Factory::conf();
    $dbStatus = Factory::db()->getDbRefreshStatus();
    if (Factory::checkTestset($testset)) {
        $days = intval($ini['top_duration_last_days']) - 1;
        $since = Factory::getSinceDate($days);
        $testsetRoute = str_replace('/:testset/:project', '', Slim\Slim::getInstance()->urlFor('testset'));
        $breadcrumb = array(
            array('name' => 'home', 'link' => Slim\Slim::getInstance()->urlFor('root')),
            array('name' => $testset, 'link' => $testsetRoute . '/' . $testset . '/' . $project)
        );
        $app->render('testfunctions_duration.html', array(
            'root' => Slim\Slim::getInstance()->urlFor('root'),
            'dbStatus' => $dbStatus,
            'refreshed' => $dbStatus['refreshed'] . ' (GMT)',
            'breadcrumb' => $breadcrumb,
            'lastDays' => $ini['top_duration_last_days'],
            'sinceDate' => $since,
            'durationLimitSec' => $ini['testfunction_top_duration_limit_sec'],
            'testset' => $testset,
            'project' => $project,
            'masterProject' => $ini['master_build_project'],
            'masterState' => $ini['master_build_state']
        ));
    } else {
        $app->render('empty.html', array(
            'root' => Slim\Slim::getInstance()->urlFor('root'),
            'dbStatus' => $dbStatus,
            'message' => '404 Not Found'
        ));
        $app->response()->status(404);
    }
})->name('durationTestfunctions');

$app->get('/data/test/duration/testfunctions/:testset/:project', function($testset, $project) use($app)
{
    $ini = Factory::conf();
    $days = intval($ini['top_duration_last_days']) - 1;
    $since = Factory::getSinceDate($days);
    $testsetTestfunctionsRoute = str_replace('/:testset/:project/:conf', '', Slim\Slim::getInstance()->urlFor('testset_testfunctions'));
    $app->render('testsets_duration_data.html', array(
        'testsetRoute' => Slim\Slim::getInstance()->urlFor('root') . 'testset',
        'testsetTestfunctionsRoute' => $testsetTestfunctionsRoute,
        'lastDays' => $ini['top_duration_last_days'],
        'sinceDate' => $since,
        'durationLimitSec' => $ini['testfunction_top_duration_limit_sec'],
        'list' => 'testfunctions',
        'testset' => $testset,
        'project' => $project,
        'runs' => Factory::createTestfunctionRunsMaxDuration(
            $testset,
            $project,
            $ini['master_build_project'],
            $ini['master_build_state'])                 // managed as objects
    ));
});

/**
 * UI route: /testset/:testset/:project (GET)
 */

$app->get('/testset/:testset/:project', function($testset, $project) use($app)
{
    $testset = strip_tags($testset);
    $project = strip_tags($project);
    $dbStatus = Factory::db()->getDbRefreshStatus();
    if (Factory::checkTestset($testset)) {
        $ini = Factory::conf();
        $breadcrumb = array(
            array('name' => 'home', 'link' => Slim\Slim::getInstance()->urlFor('root'))
        );
        $testsetTestfunctionsRoute = str_replace('/:testset/:project/:conf', '', Slim\Slim::getInstance()->urlFor('testset_testfunctions'));
        $testsetProjectRoute = str_replace('/:project', '', Slim\Slim::getInstance()->urlFor('testsetproject'));
        $bpassedTestsetRoute = str_replace('/:testset/:project', '', Slim\Slim::getInstance()->urlFor('bpassedtestset'));
        $bpassedtestsetTestrowsRoute = str_replace('/:testset/:project', '', Slim\Slim::getInstance()->urlFor('bpassedtestsetTestrows'));
        $durationTestfunctionsRoute = str_replace('/:testset/:project', '', Slim\Slim::getInstance()->urlFor('durationTestfunctions'));
        $app->render('testset.html', array(
            'root' => Slim\Slim::getInstance()->urlFor('root'),
            'dbStatus' => $dbStatus,
            'refreshed' => $dbStatus['refreshed'] . ' (GMT)',
            'breadcrumb' => $breadcrumb,
            'testsetTestfunctionsRoute' => $testsetTestfunctionsRoute,
            'testsetProjectRoute' => $testsetProjectRoute,
            'bpassedTestsetRoute' => $bpassedTestsetRoute,
            'bpassedtestsetTestrowsRoute' => $bpassedtestsetTestrowsRoute,
            'durationTestfunctionsRoute' => $durationTestfunctionsRoute,
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
            'dbStatus' => $dbStatus,
            'message' => '404 Not Found'
        ));
        $app->response()->status(404);
    }
})->name('testset');

/**
 * UI route: /testset/:testset/:project/:conf (GET)
 */

$app->get('/testset/:testset/:project/:conf', function($testset, $project, $conf) use($app)
{
    $testset = strip_tags($testset);
    $project = strip_tags($project);
    $conf = strip_tags($conf);
    $dbStatus = Factory::db()->getDbRefreshStatus();
    if (Factory::checkTestset($testset)) {
        $testsetRoute = str_replace('/:testset/:project', '', Slim\Slim::getInstance()->urlFor('testset'));
        $testsetProjectRoute = str_replace('/:project', '', Slim\Slim::getInstance()->urlFor('testsetproject'));
        $confProjectRoute = str_replace('/:conf/:testsetproject', '', Slim\Slim::getInstance()->urlFor('conf_testsetproject'));
        $ini = Factory::conf();
        $breadcrumb = array(
            array('name' => 'home', 'link' => Slim\Slim::getInstance()->urlFor('root')),
            array('name' => 'overview', 'link' => Slim\Slim::getInstance()->urlFor('overview')),
            array('name' => $project, 'link' => $testsetProjectRoute . '/' . $project),
            array('name' => $conf, 'link' => $confProjectRoute . '/' . urlencode($conf) . '/' . $project)
        );
        $testfunctionRoute = str_replace('/:testfunction/:testset/:project/:conf', '', Slim\Slim::getInstance()->urlFor('testfunction'));
        $app->render('testset_testfunctions.html', array(
            'root' => Slim\Slim::getInstance()->urlFor('root'),
            'dbStatus' => $dbStatus,
            'refreshed' => $dbStatus['refreshed'] . ' (GMT)',
            'breadcrumb' => $breadcrumb,
            'testfunctionRoute' => $testfunctionRoute,
            'masterProject' => $ini['master_build_project'],
            'masterState' => $ini['master_build_state'],
            'conf' => $conf,
            'projectRuns' => Factory::createProjectRuns(
                $ini['master_build_project'],
                $ini['master_build_state']),                // managed as objects
            'testset' => Factory::createTestset(
                $testset,
                $project,
                $ini['master_build_project'],
                $ini['master_build_state']),                // managed as object
            'testfunctionRuns' => Factory::createTestfunctionRunsInConf(
                $testset,
                $project,
                $conf,
                $ini['master_build_project'],
                $ini['master_build_state'])                 // managed as objects
        ));
    } else {
        $app->render('empty.html', array(
            'root' => Slim\Slim::getInstance()->urlFor('root'),
            'dbStatus' => $dbStatus,
            'message' => '404 Not Found'
        ));
        $app->response()->status(404);
    }
})->name('testset_testfunctions');

/**
 * UI route: /testfunction/:testfunction/:testset/:project/:conf (GET)
 */

$app->get('/testfunction/:testfunction/:testset/:project/:conf', function($testfunction, $testset, $project, $conf) use($app)
{
    $testfunction = strip_tags($testfunction);
    $testset = strip_tags($testset);
    $project = strip_tags($project);
    $conf = strip_tags($conf);
    $dbStatus = Factory::db()->getDbRefreshStatus();
    if (Factory::checkTestset($testset)) {
        $testsetTestfunctionRoute = str_replace('/:testset/:project/:conf', '', Slim\Slim::getInstance()->urlFor('testset_testfunctions'));
        $testsetRoute = str_replace('/:testset/:project', '', Slim\Slim::getInstance()->urlFor('testset'));
        $testsetProjectRoute = str_replace('/:project', '', Slim\Slim::getInstance()->urlFor('testsetproject'));
        $confProjectRoute = str_replace('/:conf/:testsetproject', '', Slim\Slim::getInstance()->urlFor('conf_testsetproject'));
        $ini = Factory::conf();
        $breadcrumb = array(
            array('name' => 'home', 'link' => Slim\Slim::getInstance()->urlFor('root')),
            array('name' => 'overview', 'link' => Slim\Slim::getInstance()->urlFor('overview')),
            array('name' => $project, 'link' => $testsetProjectRoute . '/' . $project),
            array('name' => $conf, 'link' => $confProjectRoute . '/' . urlencode($conf) . '/' . $project),
            array('name' => $testset, 'link' => $testsetTestfunctionRoute . '/' . $testset . '/' . $project . '/' . urlencode($conf))
        );
        $app->render('testfunction.html', array(
            'root' => Slim\Slim::getInstance()->urlFor('root'),
            'dbStatus' => $dbStatus,
            'refreshed' => $dbStatus['refreshed'] . ' (GMT)',
            'breadcrumb' => $breadcrumb,
            'masterProject' => $ini['master_build_project'],
            'masterState' => $ini['master_build_state'],
            'conf' => $conf,
            'testset' => $testset,
            'testfunction' => $testfunction,
            'projectRuns' => Factory::createProjectRuns(
                $ini['master_build_project'],
                $ini['master_build_state']),                // managed as objects
            'testrowRuns' => Factory::createTestrowRunsInConf(
                $testfunction,
                $testset,
                $project,
                $conf,
                $ini['master_build_project'],
                $ini['master_build_state'])                 // managed as objects
        ));
    } else {
        $app->render('empty.html', array(
            'root' => Slim\Slim::getInstance()->urlFor('root'),
            'dbStatus' => $dbStatus,
            'message' => '404 Not Found'
        ));
        $app->response()->status(404);
    }
})->name('testfunction');

/**
 * UI route: /sitemap (GET)
 */

$app->get('/sitemap', function() use($app)
{
    $dbStatus = Factory::db()->getDbRefreshStatus();
    $breadcrumb = array(
        array('name' => 'home', 'link' => Slim\Slim::getInstance()->urlFor('root'))
    );
    $app->render('image.html', array(
        'root' => Slim\Slim::getInstance()->urlFor('root'),
        'dbStatus' => $dbStatus,
        'breadcrumb' => $breadcrumb,
        'title' => 'Site Map',
        'navi_title' => 'site map',
        'image' => 'images/site_map.png'
    ));
})->name('sitemap');

/**
 * UI route: /admin (GET) - authenticated
 */

$app->add(new HttpBasicAuthRoute('Protected Area', 'admin'));
$app->get('/admin', function() use($app)
{
    $dbStatus = Factory::db()->getDbRefreshStatus();
    $breadcrumb = array(
        array('name' => 'home', 'link' => Slim\Slim::getInstance()->urlFor('root'))
    );
    $app->render('admin.html', array(
        'root' => Slim\Slim::getInstance()->urlFor('root'),
        'dbStatus' => $dbStatus,
        'breadcrumb' => $breadcrumb,
        'adminRoute' => Slim\Slim::getInstance()->urlFor('admin'),
        'adminBranchesRoute' => Slim\Slim::getInstance()->urlFor('admin_branches'),
        'adminDataRoute' => Slim\Slim::getInstance()->urlFor('admin_data'),
        'tables' => Factory::dbAdmin()->getTablesStatistics()
    ));
})->name('admin');

/**
 * UI route: /admin/branches (GET) - authenticated
 */

$app->add(new HttpBasicAuthRoute('Protected Area', 'admin/branches'));
$app->get('/admin/branches', function() use($app)
{
    $dbStatus = Factory::db()->getDbRefreshStatus();
    $breadcrumb = array(
        array('name' => 'home', 'link' => Slim\Slim::getInstance()->urlFor('root')),
        array('name' => 'admin', 'link' => Slim\Slim::getInstance()->urlFor('admin'))
    );
    $app->render('admin_branches.html', array(
        'root' => Slim\Slim::getInstance()->urlFor('root'),
        'dbStatus' => $dbStatus,
        'breadcrumb' => $breadcrumb,
        'adminRoute' => Slim\Slim::getInstance()->urlFor('admin'),
        'adminBranchesRoute' => Slim\Slim::getInstance()->urlFor('admin_branches'),
        'adminDataRoute' => Slim\Slim::getInstance()->urlFor('admin_data'),
        'branches' => Factory::dbAdmin()->getBranchesStatistics()
    ));
})->name('admin_branches');

/**
 * UI route: /admin/data (GET) - authenticated
 */

$app->add(new HttpBasicAuthRoute('Protected Area', 'admin/data'));
$app->get('/admin/data', function() use($app)
{
    $dbStatus = Factory::db()->getDbRefreshStatus();
    $breadcrumb = array(
        array('name' => 'home', 'link' => Slim\Slim::getInstance()->urlFor('root')),
        array('name' => 'admin', 'link' => Slim\Slim::getInstance()->urlFor('admin'))
    );
    $app->render('admin_data.html', array(
        'root' => Slim\Slim::getInstance()->urlFor('root'),
        'dbStatus' => $dbStatus,
        'breadcrumb' => $breadcrumb,
        'adminRoute' => Slim\Slim::getInstance()->urlFor('admin'),
        'adminBranchesRoute' => Slim\Slim::getInstance()->urlFor('admin_branches'),
        'adminDataRoute' => Slim\Slim::getInstance()->urlFor('admin_data'),
        'projectRuns' => Factory::dbAdmin()->getProjectRunsStatistics()
    ));
})->name('admin_data');

/**
 * API route: /api/branch (DELETE) - authenticated
 */

$app->add(new HttpBasicAuthRoute('Protected Area', 'api/branch'));
$app->delete('/api/branch/:branch', function($branch) use($app)
{
    $branch = strip_tags($branch);
    $branches = array();
    $query = Factory::db()->getBranches();
    foreach($query as $item) {
        $branches[] = $item['name'];
    }
    if (in_array($branch, $branches)) {
        $result = Factory::dbAdmin()->deleteBranch($branch);
        if ($result)
            $app->response()->status(200);
        else
            $app->response()->status(404);
    } else {
        $app->response()->status(404);
    }
})->name('delete_branch');

/**
 * API route: /api/branch/archive (PUT) - authenticated
 */

$app->add(new HttpBasicAuthRoute('Protected Area', 'api/branch/archive'));
$app->put('/api/branch/archive/:branch', function($branch) use($app)
{
    $branch = strip_tags($branch);
    $branches = array();
    $query = Factory::db()->getBranches();
    foreach($query as $item) {
        $branches[] = $item['name'];
    }
    if (in_array($branch, $branches)) {
        $result = Factory::dbAdmin()->archiveBranch($branch);
        if ($result)
            $app->response()->status(200);
        else
            $app->response()->status(404);
    } else {
        $app->response()->status(404);
    }
})->name('archive_branch');

/**
 * API route: /api/branch/restore (PUT) - authenticated
 */

$app->add(new HttpBasicAuthRoute('Protected Area', 'api/branch/restore'));
$app->put('/api/branch/restore/:branch', function($branch) use($app)
{
    $branch = strip_tags($branch);
    $branches = array();
    $query = Factory::db()->getBranches();
    foreach($query as $item) {
        $branches[] = $item['name'];
    }
    if (in_array($branch, $branches)) {
        $result = Factory::dbAdmin()->restoreBranch($branch);
        if ($result)
            $app->response()->status(200);
        else
            $app->response()->status(404);
    } else {
        $app->response()->status(404);
    }
})->name('restore_branch');

/**
 * API route: /api/data (DELETE) - authenticated
 */

$app->add(new HttpBasicAuthRoute('Protected Area', 'api/data'));
$app->delete('/api/data/:state/:date', function($state, $date) use($app)
{
    $state = strip_tags($state);
    $date = strip_tags($date);
    $result = Factory::dbAdmin()->deleteRunsData($state, $date);
    if ($result)
        $app->response()->status(200);
    else
        $app->response()->status(404);
})->name('delete_data');


/**
 * Start Slim
 */

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
        header('Location: ' . Slim\Slim::getInstance()->urlFor('root') . 'testsetproject/' . urlencode($project));
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
        header('Location: ' . Slim\Slim::getInstance()->urlFor('root') . 'testset/' . urlencode($testset) . '/' . urlencode($project));
        exit();
    }
}

?>
