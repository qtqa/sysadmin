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
 * @version   0.2
 * @since     12-06-2015
 * @author    Juha Sippola
 */

require 'src/Factory.php';
require 'lib/Slim/Slim/Slim.php';

\Slim\Slim::registerAutoloader();
$app = new \Slim\Slim(array(
    'templates.path' => 'templates'
));


/**
 * UI route: / (GET)
 */

$app->get('/', function() use($app)
{
    $ini = Factory::conf();
    $app->render('home.php', array(
        'overviewRoute' => Slim\Slim::getInstance()->urlFor('root') . 'overview',
        'branchRoute' => Slim\Slim::getInstance()->urlFor('root') . 'branch',
        'platformRoute' => Slim\Slim::getInstance()->urlFor('root') . 'platform',
        'testRoute' => Slim\Slim::getInstance()->urlFor('root') . 'test',
        'refreshed' => Factory::db()->getDbRefreshed() . ' (GMT)',
        'states' => Factory::db()->getStates(),
        'branches' => Factory::db()->getBranches(),
        'projects' => Factory::createProjects(
            $ini['master_build_project'],
            $ini['master_build_state']),                    // managed as objects
        'platforms' => Factory::db()->getTargetPlatforms()
    ));
})->name('root');

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
    $app->render('testsets_top.php', array(
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
    $app->render('testsets_flaky.php', array(
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
 * UI route: /testset/:testset (GET)
 */

$app->get('/testset/:testset', function($testset) use($app)
{
    $ini = Factory::conf();
    $breadcrumb = array(
        array('name' => 'home', 'link' => Slim\Slim::getInstance()->urlFor('root'))
    );
    if (Factory::checkTestset($testset)) {
        $app->render('testset.php', array(
            'breadcrumb' => $breadcrumb,
            'refreshed' => Factory::db()->getDbRefreshed() . ' (GMT)',
            'lastDaysFailures' => $ini['top_failures_last_days'],
            'lastDaysFlaky' => $ini['flaky_testsets_last_days'],
            'masterProject' => $ini['master_build_project'],
            'masterState' => $ini['master_build_state'],
            'testset' => Factory::createTestset(
                $testset,
                $ini['master_build_project'],
                $ini['master_build_state'])                 // managed as objects
        ));
    } else {
        $app->render('empty.php', array(
            'message' => '404 Not Found'
        ));
        $app->response()->status(404);
    }
});


$app->run();


/**
 * Handle the testset name input selection on home page and redirect to testset page
 */

if (isset($_POST["testsetInputSubmit"])) {
    if (empty($_POST["testsetInputValue"])) {
        header('Location: ' . Slim\Slim::getInstance()->urlFor('root'));
        exit();
    }
    if (isset($_POST["testsetInputValue"])) {
        header('Location: ' . Slim\Slim::getInstance()->urlFor('root') . 'testset/' . htmlspecialchars($_POST['testsetInputValue']));
        exit();
    }
}

?>
