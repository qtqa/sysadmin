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

require_once(__DIR__.'/../Factory.php');

/**
 * Factory unit test class
 * @example   To run (in qtmetrics root directory): php <path-to-phpunit>/phpunit.phar ./src/test
 * @version   0.7
 * @since     24-07-2015
 * @author    Juha Sippola
 */

class FactoryTest extends PHPUnit_Framework_TestCase
{

    /**
     * Test Factory
     */
    public function testSingleton()
    {
        $db = Factory::db();
        $this->assertEquals(Factory::db(), $db);
        $conf = Factory::conf();
        $this->assertEquals(Factory::conf(), $conf);
    }

    /**
     * Test conf
     */
    public function testConf()
    {
        Factory::setRuntimeConf('my_key', 'my_value');
        $conf = Factory::conf();
        $this->assertEquals('my_value', $conf['my_key']);
        Factory::setRuntimeConf('my_key', null);
        $conf = Factory::conf();
        $this->assertEquals(null, $conf['my_key']);
    }

    /**
     * Test db
     */
    public function testDb()
    {
        $db = Factory::db();
        $this->assertTrue($db instanceof Database);
    }

    /**
     * Test checkTestset
     * @dataProvider testCheckTestsetData
     */
    public function testCheckTestset($testset, $exp_match)
    {
        $booMatch = Factory::checkTestset($testset);
        $this->assertEquals($exp_match, $booMatch);
    }
    public function testCheckTestsetData()
    {
        return array(
            array('tst_qftp', true),
            array('tst_qfont', true),
            array('tst_qfon', false),
            array('tst_qfontt', false),
            array('qfont', false),
            array('invalid-name', false)
        );
    }

    /**
     * Test getProjectsFiltered
     * @dataProvider testGetProjectsFilteredData
     */
    public function testGetProjectsFiltered($project, $exp_count)
    {
        $result = Factory::getProjectsFiltered($project);
        $this->assertEquals($exp_count, count($result));
    }
    public function testGetProjectsFilteredData()
    {
        return array(
            array('', 35),                          // test data includes 35 projects
            array('qt',35),                         // all
            array('ba', 3),
            array('bas', 1),
            array('base', 1),
            array('qtbase', 1),
            array('QtBase', 1),
            array('invalid-name', 0)
        );
    }

    /**
     * Test getTestsetsFiltered
     * @dataProvider testGetTestsetsFilteredData
     */
    public function testGetTestsetsFiltered($testset, $exp_count)
    {
        $result = Factory::getTestsetsFiltered($testset);
        $this->assertEquals($exp_count, count($result));
    }
    public function testGetTestsetsFilteredData()
    {
        return array(
            array('', 4),                           // test data includes four testsets
            array('f', 4),                          // all
            array('ft', 3),                         // tst_qftp (twice) and tst_networkselftest
            array('ftp', 2),                        // tst_qftp (twice)
            array('tst_qftp', 2),
            array('tst_qfont', 1),
            array('tst_qfon', 1),
            array('tst_qfontt', 0),
            array('qfont', 1),
            array('invalid-name', 0)
        );
    }

    /**
     * Test createProject
     * @dataProvider testCreateProjectData
     */
    public function testCreateProject($project, $runProject, $runState)
    {
        $project = Factory::createProject($project, $runProject, $runState);
        $this->assertTrue($project instanceof Project);
        if ($project->getName() === $runProject) {                      // check only the projects with project_run data
            $this->assertNotEmpty($project->getStatus());
        }
    }
    public function testCreateProjectData()
    {
        return array(
            array('Qt5', 'Qt5', 'state',)                               // project with project_run data
        );
    }

    /**
     * Test createConf
     * @dataProvider testCreateConfData
     */
    public function testCreateConf($conf, $runProject, $runState)
    {
        $conf = Factory::createConf($conf, $runProject, $runState);
        $this->assertTrue($conf instanceof Conf);
        $this->assertNotEmpty($conf->getStatus());
    }
    public function testCreateConfData()
    {
        return array(
            array('win32-msvc2010_developer-build_angle_Windows_7', 'Qt5', 'state',)
        );
    }

    /**
     * Test createTestsets
     * @dataProvider testCreateTestsetsData
     */
    public function testCreateTestsets($listType, $runProject, $runState, $status_check)
    {
        $testsets = Factory::createTestsets($listType, $runProject, $runState);
        foreach($testsets as $testset) {
            $this->assertTrue($testset instanceof Testset);
            $status = $testset->getStatus();
            if (in_array($testset->getName(), $status_check)) {
                $this->assertNotEmpty($status);
            } else {
                $this->assertEmpty($status);
            }
            $result = $testset->getTestsetResultCounts();
            $this->assertNotNull($result);
            $this->assertArrayHasKey('passed', $result);
            $this->assertArrayHasKey('failed', $result);
            $flaky = $testset->getTestsetFlakyCounts();
            $this->assertNotNull($flaky);
            $this->assertArrayHasKey('flaky', $flaky);
            $this->assertArrayHasKey('total', $flaky);
        }
    }
    public function testCreateTestsetsData()
    {
        return array(
            array(
                Factory::LIST_FAILURES,
                'Qt5',
                'state',
                array('tst_qftp', 'tst_qfont', 'tst_networkselftest')   // check only the testsets with testset_run data
            ),
            array(
                Factory::LIST_FLAKY,
                'Qt5',
                'state',
                array('not-set')                                        // status not set for flaky list
            )
        );
    }

    /**
     * Test createTestset
     * @dataProvider testCreateTestsetData
     */
    public function testCreateTestset($name, $project, $runProject, $runState)
    {
        $testsets = Factory::createTestset($name, $project, $runProject, $runState);
        foreach($testsets as $testset) {
            $this->assertTrue($testset instanceof Testset);
            if ($testset->getProjectName() === $project) {
                $status = $testset->getStatus();
                $this->assertNotEmpty($status);
                $result = $testset->getTestsetResultCounts();
                $this->assertNotNull($result);
                $this->assertArrayHasKey('passed', $result);
                $this->assertArrayHasKey('failed', $result);
                $flaky = $testset->getTestsetFlakyCounts();
                $this->assertNotNull($flaky);
                $this->assertArrayHasKey('flaky', $flaky);
                $this->assertArrayHasKey('total', $flaky);
            }
        }
    }
    public function testCreateTestsetData()
    {
        return array(
            array('tst_qftp', 'qtbase', 'Qt5', 'state',),               // testset with testset_run data
            array('tst_qfont', 'qtbase', 'Qt5', 'state',)               // testset with testset_run data
        );
    }

    /**
     * Test createProjectRuns
     * @dataProvider testCreateProjectRunsData
     */
    public function testCreateProjectRuns($runProject, $runState, $exp_branch, $exp_buildKey, $has_data)
    {
        $branches = array();
        $buildKeys = array();
        $runs = Factory::createProjectRuns($runProject, $runState);
        foreach($runs as $run) {
            $this->assertTrue($run instanceof ProjectRun);
            $branches[] = $run->getBranchName();
            $buildKeys[] = $run->getBuildKey();
        }
        if ($has_data) {
            $this->assertContains($exp_branch, $branches);
            $this->assertContains($exp_buildKey, $buildKeys);
        } else {
            $this->assertEmpty($runs);
        }
    }
    public function testCreateProjectRunsData()
    {
        return array(
            array('Qt5', 'state', 'stable', '1348', 1),
            array('Qt5', 'state', 'dev', 'BuildKeyInStringFormat12345', 1),
            array('invalid', 'state', '', '', '', 0),
            array('Qt5', 'invalid', '', '', '', 0)
        );
    }

    /**
     * Test createConfRuns
     * @dataProvider testCreateConfRunsData
     */
    public function testCreateConfRuns($runProject, $runState, $targetOs, $conf, $exp_branch, $exp_buildKey, $exp_conf, $has_data)
    {
        $branches = array();
        $buildKeys = array();
        $confs = array();
        $runs = Factory::createConfRuns($runProject, $runState, $targetOs, $conf);
        foreach($runs as $run) {
            $this->assertTrue($run instanceof ConfRun);
            $branches[] = $run->getBranchName();
            $buildKeys[] = $run->getBuildKey();
            $confs[] = $run->getName();
        }
        if ($has_data) {
            $this->assertContains($exp_branch, $branches);
            $this->assertContains($exp_buildKey, $buildKeys);
            $this->assertContains($exp_conf, $confs);
        } else {
            $this->assertEmpty($runs);
        }
    }
    public function testCreateConfRunsData()
    {
        return array(
            array('Qt5', 'state', '', '', 'stable', '1348', 'win64-msvc2012_developer-build_qtnamespace_Windows_8', 1),
            array('Qt5', 'state', '', '', 'stable', '1348', 'linux-g++-32_developer-build_Ubuntu_10.04_x86', 1),
            array('Qt5', 'state', 'windows', '', 'stable', '1348', 'win64-msvc2012_developer-build_qtnamespace_Windows_8', 1),
            array('Qt5', 'state', 'linux', '', 'stable', '1348', 'linux-g++-32_developer-build_Ubuntu_10.04_x86', 1),
            array('Qt5', 'state', 'linux', '', 'dev', 'BuildKeyInStringFormat12345', 'linux-g++-32_developer-build_Ubuntu_10.04_x86', 1),
            array('Qt5', 'state', 'invalid', '', '', '', '', 0),
            array('Qt5', 'state', '', 'win64-msvc2012_developer-build_qtnamespace_Windows_8', 'stable', '1348', 'win64-msvc2012_developer-build_qtnamespace_Windows_8', 1),
            array('Qt5', 'state', '', 'linux-g++-32_developer-build_Ubuntu_10.04_x86', 'stable', '1348', 'linux-g++-32_developer-build_Ubuntu_10.04_x86', 1),
            array('Qt5', 'state', '', 'linux-g++-32_developer-build_Ubuntu_10.04_x86', 'dev', 'BuildKeyInStringFormat12345', 'linux-g++-32_developer-build_Ubuntu_10.04_x86', 1),
            array('Qt5', 'state', '', 'invalid', '', '', '', 0)
        );
    }

    /**
     * Test createTestsetRuns
     * @dataProvider testCreateTestsetRunsData
     */
    public function testCreateTestsetRuns($name, $testsetProject, $runProject, $runState, $exp_branch, $exp_buildKey, $exp_conf, $has_data)
    {
        $branches = array();
        $buildKeys = array();
        $confs = array();
        $runs = Factory::createTestsetRuns($name, $testsetProject, $runProject, $runState);
        foreach($runs as $run) {
            $this->assertTrue($run instanceof TestsetRun);
            $branches[] = $run->getBranchName();
            $buildKeys[] = $run->getBuildKey();
            $confs[] = $run->getConfName();
        }
        if ($has_data) {
            $this->assertContains($exp_branch, $branches);
            $this->assertContains($exp_buildKey, $buildKeys);
            $this->assertContains($exp_conf, $confs);
        } else {
            $this->assertEmpty($runs);
        }
    }
    public function testCreateTestsetRunsData()
    {
        return array(
            array('tst_qftp', 'qtbase', 'Qt5', 'state', 'stable', '1348', 'win64-msvc2012_developer-build_qtnamespace_Windows_8', 1),
            array('tst_qfont', 'qtbase', 'Qt5', 'state', 'dev', 'BuildKeyInStringFormat12345', 'linux-g++-32_developer-build_Ubuntu_10.04_x86', 1),
            array('invalid', 'qtbase', 'Qt5', 'state', '', '', '', 0),
            array('tst_qftp', 'invalid', 'Qt5', 'state', '', '', '', 0)
        );
    }

    /**
     * Test createTestsetRunsInConf
     * @dataProvider testCreateTestsetRunsInConfData
     */
    public function testCreateTestsetRunsInConf($conf, $testsetProject, $runProject, $runState, $exp_branch, $exp_buildKey, $exp_testset, $has_data)
    {
        $branches = array();
        $buildKeys = array();
        $testsets = array();
        $runs = Factory::createTestsetRunsInConf($conf, $testsetProject, $runProject, $runState);
        foreach($runs as $run) {
            $this->assertTrue($run instanceof TestsetRun);
            $branches[] = $run->getBranchName();
            $buildKeys[] = $run->getBuildKey();
            $testsets[] = $run->getName();
        }
        if ($has_data) {
            $this->assertContains($exp_branch, $branches);
            $this->assertContains($exp_buildKey, $buildKeys);
            $this->assertContains($exp_testset, $testsets);
        } else {
            $this->assertEmpty($runs);
        }
    }
    public function testCreateTestsetRunsInConfData()
    {
        return array(
            array('win64-msvc2012_developer-build_qtnamespace_Windows_8', '', 'Qt5', 'state', 'stable', '1348', 'tst_qftp', 1),
            array('linux-g++-32_developer-build_Ubuntu_10.04_x86', '', 'Qt5', 'state', 'stable', 'BuildKeyInStringFormat12345', 'tst_qftp', 1),
            array('invalid', '', 'Qt5', 'state', '', '', '', 0),
            array('win64-msvc2012_developer-build_qtnamespace_Windows_8', 'qtbase', 'Qt5', 'state', 'stable', '1348', 'tst_qftp', 1),
            array('linux-g++-32_developer-build_Ubuntu_10.04_x86', 'qtbase', 'Qt5', 'state', 'stable', 'BuildKeyInStringFormat12345', 'tst_qftp', 1),
            array('linux-g++-32_developer-build_Ubuntu_10.04_x86', 'invalid', 'Qt5', 'state', '', '', '', 0),
            array('invalid', 'qtbase', 'Qt5', 'state', '', '', '', 0)
        );
    }

    /**
     * Test getSinceDate
     * @dataProvider testGetSinceDateData
     */
    public function testGetSinceDate($since_days, $exp_date)
    {
        $date = Factory::getSinceDate($since_days);
        $this->assertEquals($exp_date, $date);
    }
    public function testGetSinceDateData()
    {
        return array(
            array(0, '2013-05-28'),                                    // test database refreshed 2013-05-28
            array(1, '2013-05-27'),
            array(7, '2013-05-21'),
            array(28, '2013-04-30'),
            array(365, '2012-05-28')
        );
    }

}

?>
