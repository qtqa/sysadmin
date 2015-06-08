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
 * Database unit test class
 * Some of the tests require the test data as inserted into database with qtmetrics_insert.sql
 * @example   To run (in qtmetrics root directory): php <path-to-phpunit>/phpunit.phar ./src/test
 * @version   0.1
 * @since     09-06-2015
 * @author    Juha Sippola
 */

class DatabaseTest extends PHPUnit_Framework_TestCase
{

    /**
     * Test getProjects
     * @dataProvider testGetProjectsData
     */
    public function testGetProjects($project)
    {
        $items = array();
        $db = Factory::db();
        $result = $db->getProjects();
        $this->assertNotEmpty($result);
        foreach($result as $row) {
            $this->assertArrayHasKey('name', $row);
            $items[] = $row['name'];
        }
        $this->assertContains($project, $items);
    }
    public function testGetProjectsData()
    {
        return array(
            array('QtBase')
        );
    }

    /**
     * Test getBranches
     * @dataProvider testGetBranchesData
     */
    public function testGetBranches($branch)
    {
        $items = array();
        $db = Factory::db();
        $result = $db->getBranches();
        $this->assertNotEmpty($result);
        foreach($result as $row) {
            $this->assertArrayHasKey('name', $row);
            $items[] = $row['name'];
        }
        $this->assertContains($branch, $items);
    }
    public function testGetBranchesData()
    {
        return array(
            array('dev')
        );
    }

    /**
     * Test getStates
     * @dataProvider testGetStateData
     */
    public function testGetStates($state)
    {
        $items = array();
        $db = Factory::db();
        $result = $db->getStates();
        $this->assertNotEmpty($result);
        foreach($result as $row) {
            $this->assertArrayHasKey('name', $row);
            $items[] = $row['name'];
        }
        $this->assertContains($state, $items);
    }
    public function testGetStateData()
    {
        return array(
            array('state')
        );
    }

    /**
     * Test getTestsetsFiltered
     * @dataProvider testGetTestsetsFilteredData
     */
    public function testGetTestsetsFiltered($filter, $exp_match_count_min)
    {
        $db = Factory::db();
        $result = $db->getTestsetsFiltered($filter);
        $this->assertGreaterThanOrEqual($exp_match_count_min, count($result));
    }
    public function testGetTestsetsFilteredData()
    {
        return array(
            array('ftp', 1),
            array('ft', 2),
            array('tst', 3),
            array('invalid-name', 0)
        );
    }

    /**
     * Test getTestsetProject
     * @dataProvider testGetTestsetProjectData
     */
    public function testGetTestsetProject($testset, $exp_project, $exp_count)
    {
        $projects = array();
        $db = Factory::db();
        $result = $db->getTestsetProject($testset);
        foreach($result as $row) {
            $this->assertArrayHasKey('name', $row);
            $this->assertArrayHasKey('project', $row);
            $projects[] = $row['project'];
        }
        $this->assertEquals($exp_count, count($projects));
        if ($exp_count > 0) {
            $this->assertNotEmpty($result);
            $this->assertContains($exp_project, $projects);
        }
    }
    public function testGetTestsetProjectData()
    {
        return array(
            array('tst_qfont', 'QtBase', 1),
            array('tst_qftp', 'QtBase', 2),
            array('tst_qftp', 'Qt5', 2),
            array('invalid-name', '', 0)
        );
    }

    /**
     * Test getTargetPlatforms
     * @dataProvider testGetTargetPlatformsData
     */
    public function testGetTargetPlatforms($exp_os, $exp_os_version, $exp_count_min)
    {
        $db = Factory::db();
        $result = $db->getTargetPlatforms();
        $this->assertNotEmpty($result);
        $osCount = 0;
        $versionCount = 0;
        foreach($result as $row) {
            $this->assertArrayHasKey('os', $row);
            $this->assertArrayHasKey('os_version', $row);
            if ($row['os'] == $exp_os)
                $osCount++;
            if ($row['os_version'] == $exp_os_version)
                $versionCount++;
        }
        $this->assertGreaterThanOrEqual($exp_count_min, $osCount);
        $this->assertGreaterThanOrEqual($exp_count_min, $versionCount);
    }
    public function testGetTargetPlatformsData()
    {
        return array(
            array('linux', 'android', 1),
            array('windows', 'win64', 1),
            array('windows', 'invalid', 0)
        );
    }

    /**
     * Test getLatestProjectBranchBuildNumbers
     * @dataProvider testGetLatestProjectBranchBuildNumbersData
     */
    public function testGetLatestProjectBranchBuildNumbers($project, $state, $exp_branch, $exp_build_number_min)
    {
        $branches = array();
        $db = Factory::db();
        $result = $db->getLatestProjectBranchBuildNumbers($project, $state);
        $this->assertNotEmpty($result);
        foreach($result as $row) {
            $this->assertArrayHasKey('name', $row);
            $this->assertArrayHasKey('number', $row);
            $this->assertGreaterThan($exp_build_number_min, $row['number']);
            $branches[] = $row['name'];
        }
        $this->assertContains($exp_branch, $branches);
    }
    public function testGetLatestProjectBranchBuildNumbersData()
    {
        return array(
            array('Qtbase', 'state', 'dev', 100)                    // Assuming any dev build has number > 100
        );
    }

    /**
     * Test getLatestProjectBranchBuildNumber
     * @dataProvider testGetLatestProjectBranchBuildNumberData
     */
    public function testGetLatestProjectBranchBuildNumber($project, $branch, $state, $exp_build_number_min)
    {
        $db = Factory::db();
        $result = $db->getLatestProjectBranchBuildNumber($project, $branch, $state);
        $this->assertNotEmpty($result);
        $this->assertGreaterThan($exp_build_number_min, $result);
    }
    public function testGetLatestProjectBranchBuildNumberData()
    {
        return array(
            array('Qtbase', 'dev', 'state', 100)                    // Assuming any dev build has number > 100
        );
    }

    /**
     * Test getLatestProjectBranchBuildResults
     * @dataProvider testGetLatestProjectBranchBuildResultsData
     */
    public function testGetLatestProjectBranchBuildResults($project, $state, $exp_branch, $exp_results)
    {
        $branches = array();
        $db = Factory::db();
        $result = $db->getLatestProjectBranchBuildResults($project, $state);
        $this->assertNotEmpty($result);
        foreach($result as $row) {
            $this->assertArrayHasKey('name', $row);
            $this->assertArrayHasKey('result', $row);
            $this->assertContains($row['result'], $exp_results);
            $branches[] = $row['name'];
        }
        $this->assertContains($exp_branch, $branches);
    }
    public function testGetLatestProjectBranchBuildResultsData()
    {
        return array(
            array('Qtbase', 'state', 'dev', array('SUCCESS', 'FAILURE', 'ABORTED'))
        );
    }

    /**
     * Test getLatestTestsetConfBuildResults
     * @dataProvider testGetLatestTestsetConfBuildResultsData
     */
    public function testGetLatestTestsetConfBuildResults($testset, $project, $state, $exp_conf, $exp_branches, $exp_results)
    {
        $confs = array();
        $db = Factory::db();
        $result = $db->getLatestTestsetConfBuildResults($testset, $project, $state);
        $this->assertNotEmpty($result);
        foreach($result as $row) {
            $this->assertArrayHasKey('name', $row);
            $this->assertArrayHasKey('branch', $row);
            $this->assertArrayHasKey('result', $row);
            $this->assertContains($row['branch'], $exp_branches);
            $this->assertContains($row['result'], $exp_results);
            $confs[] = $row['name'];
        }
        $this->assertContains($exp_conf, $confs);
    }
    public function testGetLatestTestsetConfBuildResultsData()
    {
        return array(
            array('tst_qftp', 'Qtbase', 'state', 'linux-g++_developer-build_qtnamespace_qtlibinfix_Ubuntu_11.10_x64', array('dev', 'stable', 'master'), array('passed', 'failed', 'ipassed', 'ifailed')),
            array('tst_qftp', 'Qtbase', 'state', 'linux-g++_developer-build_qtnamespace_qtlibinfix_Ubuntu_11.10_x64', array('dev', 'stable', 'master'), array('passed', 'failed', 'ipassed', 'ifailed')),
            array('tst_qfont', 'Qtbase', 'state', 'macx-clang_developer-build_OSX_10.8', array('dev', 'stable', 'master'), array('passed', 'failed', 'ipassed', 'ifailed'))
        );
    }

    /**
     * Test getTestsetsResultCounts
     * @dataProvider testGetTestsetsResultCountsData
     */
    public function testGetTestsetsResultCounts($date, $limit, $exp_testset, $exp_excluded_testset, $exp_testset_count_min, $exp_failed_min)
    {
        $testsets = array();
        $failed = 0;
        $db = Factory::db();
        $result = $db->getTestsetsResultCounts($date, $limit);
        foreach($result as $row) {
            $this->assertArrayHasKey('name', $row);
            $this->assertArrayHasKey('project', $row);
            $this->assertArrayHasKey('passed', $row);
            $this->assertArrayHasKey('failed', $row);
            $testsets[] = $row['name'];
            $failed += $row['failed'];
        }
        $this->assertGreaterThanOrEqual($exp_testset_count_min, count($testsets));
        if ($exp_testset_count_min > 0) {
            $this->assertNotEmpty($result);
            $this->assertContains($exp_testset, $testsets);
            $this->assertNotContains($exp_excluded_testset, $testsets);
            $this->assertGreaterThanOrEqual($exp_failed_min, $failed);
        }
    }
    public function testGetTestsetsResultCountsData()
    {
        return array(
            array('2013-05-01', 10, 'tst_qftp', 'tst_networkselftest', 2, 1),   // in test data only tst_qfont and tst_qftp have failures
            array('2013-05-01', 1, 'tst_qftp', 'tst_networkselftest', 1, 1),
            array('2013-05-28', 10, 'tst_qftp', 'tst_networkselftest', 2, 1),
            array('2013-05-29', 10, '', '', 0, 0),
            array('2999-05-29', 10, '', '', 0, 0)
        );
    }

    /**
     * Test getTestsetResultCounts
     * @dataProvider testGetTestsetResultCountsData
     */
    public function testGetTestsetResultCounts($testset, $date, $exp_project, $exp_testset_count_min, $exp_failed_min)
    {
        $testsets = array();
        $failed = 0;
        $db = Factory::db();
        $result = $db->getTestsetResultCounts($testset, $date);
        foreach($result as $row) {
            $this->assertArrayHasKey('name', $row);
            $this->assertArrayHasKey('project', $row);
            $this->assertArrayHasKey('passed', $row);
            $this->assertArrayHasKey('failed', $row);
            $testsets[] = $row['name'];
            $projects[] = $row['project'];
            $failed += $row['failed'];
        }
        $this->assertGreaterThanOrEqual($exp_testset_count_min, count($testsets));
        if ($exp_testset_count_min > 0) {
            $this->assertNotEmpty($result);
            $this->assertContains($testset, $testsets);
            $this->assertContains($exp_project, $projects);
            $this->assertGreaterThanOrEqual($exp_failed_min, $failed);
        }
    }
    public function testGetTestsetResultCountsData()
    {
        return array(
            array('tst_qftp', '2013-05-01', 'QtBase', 1, 1),
            array('tst_qftp', '2013-05-28', 'QtBase', 1, 1),
            array('tst_qftp', '2013-05-29', 'QtBase', 0, 0),
            array('tst_qftp', '2999-05-29', 'QtBase', 0, 0),
            array('tst_networkselftest', '2013-05-01', 'QtBase', 1, 0), // tst_networkselftest has been run but not failed
            array('invalid-name', '2013-05-29', '', 0, 0)
        );
    }

    /**
     * Test getTestsetsFlakyCounts
     * @dataProvider testGetTestsetsFlakyCountsData
     */
    public function testGetTestsetsFlakyCounts($date, $limit, $exp_testset, $exp_excluded_testset, $exp_testset_count_min, $exp_flaky_min)
    {
        $testsets = array();
        $flaky = 0;
        $db = Factory::db();
        $result = $db->getTestsetsFlakyCounts($date, $limit);
        foreach($result as $row) {
            $this->assertArrayHasKey('name', $row);
            $this->assertArrayHasKey('project', $row);
            $this->assertArrayHasKey('flaky', $row);
            $this->assertArrayHasKey('total', $row);
            $testsets[] = $row['name'];
            $flaky += $row['flaky'];
        }
        $this->assertGreaterThanOrEqual($exp_testset_count_min, count($testsets));
        if ($exp_testset_count_min > 0) {
            $this->assertNotEmpty($result);
            $this->assertContains($exp_testset, $testsets);
            $this->assertNotContains($exp_excluded_testset, $testsets);
            $this->assertGreaterThanOrEqual($exp_flaky_min, $flaky);
        }
    }
    public function testGetTestsetsFlakyCountsData()
    {
        return array(
            array('2013-05-01', 10, 'tst_qfont', 'tst_networkselftest', 2, 1),  // in test data only tst_qfont and tst_qftp are flaky
            array('2013-05-01', 1, 'tst_qfont', 'tst_networkselftest', 1, 1),
            array('2013-05-28', 10, 'tst_qfont', 'tst_qftp', 1, 1),             // in test data only tst_qfont is flaky
            array('2013-05-29', 10, '', '', 0, 0),
            array('2999-05-29', 10, '', '', 0, 0)
        );
    }

    /**
     * Test getTestsetFlakyCounts
     * @dataProvider testGetTestsetFlakyCountsData
     */
    public function testGetTestsetFlakyCounts($testset, $date, $exp_project, $exp_testset_count_min, $exp_flaky_min)
    {
        $testsets = array();
        $flaky = 0;
        $db = Factory::db();
        $result = $db->getTestsetFlakyCounts($testset, $date);
        foreach($result as $row) {
            $this->assertArrayHasKey('name', $row);
            $this->assertArrayHasKey('project', $row);
            $this->assertArrayHasKey('flaky', $row);
            $this->assertArrayHasKey('total', $row);
            $testsets[] = $row['name'];
            $projects[] = $row['project'];
            $flaky += $row['flaky'];
        }
        $this->assertGreaterThanOrEqual($exp_testset_count_min, count($testsets));
        if ($exp_testset_count_min > 0) {
            $this->assertNotEmpty($result);
            $this->assertContains($testset, $testsets);
            $this->assertContains($exp_project, $projects);
            $this->assertGreaterThanOrEqual($exp_flaky_min, $flaky);
        }
    }
    public function testGetTestsetFlakyCountsData()
    {
        return array(
            array('tst_qfont', '2013-05-01', 'QtBase', 1, 1),
            array('tst_qfont', '2013-05-28', 'QtBase', 1, 1),
            array('tst_qfont', '2013-05-29', 'QtBase', 0, 0),
            array('tst_qfont', '2999-05-29', 'QtBase', 0, 0),
            array('tst_networkselftest', '2013-05-01', 'QtBase', 1, 0), // tst_networkselftest has been run but not flaky
            array('invalid-name', '2013-05-29', '', 0, 0)
        );
    }

    /**
     * Test getDbRefreshed
     */
    public function testGetDbRefreshed()
    {
        $db = Factory::db();
        $timestamp = $db->getDbRefreshed();
        $this->assertNotEmpty($timestamp);
        $this->assertStringStartsWith('20', $timestamp);
        $this->assertEquals(19, strlen($timestamp));                // e.g. "2015-05-04 10:00:00"
    }

}

?>
