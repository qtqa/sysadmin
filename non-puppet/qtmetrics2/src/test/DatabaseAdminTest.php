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
 * DatabaseAdmin unit test class
 * Some of the tests require the test data as inserted into database with qtmetrics_insert.sql
 * @example   To run (in qtmetrics root directory): php <path-to-phpunit>/phpunit.phar ./src/test
 * @since     19-08-2015
 * @author    Juha Sippola
 */

class DatabaseAdminTest extends PHPUnit_Framework_TestCase
{

    /**
     * Type for delete tests (only one can be tested at a time)
     */
    const DELETE_NONE               = 0;                    // Do not delete (default to enable unit testing all classes)
    const DELETE_FROM_RUN_TABLE     = 1;                    // Delete by id from each xxx_run table *)
    const DELETE_PROJECT_RUN_DATA   = 2;                    // Delete by project_run id *)
    const DELETE_BRANCH             = 3;                    // Delete by branch *)
    const DELETE_RUN_DATA           = 4;                    // Delete by project_run state and date *)
                                                            // *) Note: The data must be re-inserted into the database after each test run
    const DELETE_TEST_TYPE = self::DELETE_NONE;

    /**
     * Test getTablesStatistics
     * @dataProvider testGetTablesStatisticsData
     */
    public function testGetTablesStatistics($exp_table)
    {
        $items = array();
        $dbAdmin = Factory::dbAdmin();
        $result = $dbAdmin->getTablesStatistics();
        $this->assertNotEmpty($result);
        foreach($result as $row) {
            $this->assertArrayHasKey('name', $row);
            $this->assertArrayHasKey('rowCount', $row);
            $items[] = $row['name'];
        }
        $this->assertContains($exp_table, $items);
    }
    public function testGetTablesStatisticsData()
    {
        return array(
            array('branch'),
            array('compiler'),
            array('conf'),
            array('conf_run'),
            array('db_status'),
            array('phase'),
            array('phase_run'),
            array('platform'),
            array('project'),
            array('project_run'),
            array('state'),
            array('testfunction'),
            array('testfunction_run'),
            array('testrow'),
            array('testrow_run'),
            array('testset'),
            array('testset_run')
        );
    }

    /**
     * Test getBranchesStatistics
     * @dataProvider testGetBranchesStatisticsData
     */
    public function testGetBranchesStatistics($exp_branch)
    {
        $items = array();
        $dbAdmin = Factory::dbAdmin();
        $result = $dbAdmin->getBranchesStatistics();
        $this->assertNotEmpty($result);
        foreach($result as $row) {
            $this->assertArrayHasKey('name', $row);
            $this->assertArrayHasKey('runCount', $row);
            $this->assertArrayHasKey('latestRun', $row);
            $items[] = $row['name'];
        }
        $this->assertContains($exp_branch, $items);
    }
    public function testGetBranchesStatisticsData()
    {
        return array(
            array('dev'),
            array('stable')
        );
    }

    /**
     * Test getProjectRunsStatistics
     */
    public function testGetProjectRunsStatistics()
    {
        $items = array();
        $dbAdmin = Factory::dbAdmin();
        $result = $dbAdmin->getProjectRunsStatistics();
        $this->assertNotEmpty($result);
        foreach($result as $row) {
            $this->assertArrayHasKey('state', $row);
            $this->assertArrayHasKey('year', $row);
            $this->assertArrayHasKey('month', $row);
            $this->assertArrayHasKey('day', $row);
            $this->assertArrayHasKey('runCount', $row);
        }
    }

    /**
     * Test getProjectRunIdsBranch
     * @dataProvider testGetProjectRunIdsBranchData
     */
    public function testGetProjectRunIdsBranch($branch, $has_data)
    {
        $dbAdmin = Factory::dbAdmin();
        $result = $dbAdmin->getProjectRunIdsBranch($branch);
        foreach($result as $row) {
            $this->assertArrayHasKey('id', $row);
        }
        if ($has_data) {
            $this->assertNotEmpty($result);
        } else {
            $this->assertEmpty($result);
        }
    }
    public function testGetProjectRunIdsBranchData()
    {
        return array(
            array('dev', 1),
            array('stable', 1),
            array('invalid', 0)
        );
    }

    /**
     * Test getProjectRunIdsDate
     * @dataProvider testGetProjectRunIdsDateData
     */
    public function testGetProjectRunIdsDate($state, $date, $has_data)
    {
        $dbAdmin = Factory::dbAdmin();
        $result = $dbAdmin->getProjectRunIdsDate($state, $date);
        foreach($result as $row) {
            $this->assertArrayHasKey('id', $row);
        }
        if ($has_data) {
            $this->assertNotEmpty($result);
        } else {
            $this->assertEmpty($result);
        }
    }
    public function testGetProjectRunIdsDateData()
    {
        return array(
            array('state', '2013-05-20', 1),
            array('state', '2013-04-02', 1),
            array('state', '2013-03-20', 1),
            array('state', '2013-02-01', 0),
            array('state', '2012-12-01', 1),
            array('state', 'invalid', 0),
            array('invalid', '2013-05-20', 0),
        );
    }

    /**
     * Test getConfRunIds
     * @dataProvider testGetConfRunIdsData
     */
    public function testGetConfRunIds($projectRunId, $has_data)
    {
        $dbAdmin = Factory::dbAdmin();
        $result = $dbAdmin->getConfRunIds($projectRunId);
        foreach($result as $row) {
            $this->assertArrayHasKey('id', $row);
        }
        if ($has_data) {
            $this->assertNotEmpty($result);
        } else {
            $this->assertEmpty($result);
        }
    }
    public function testGetConfRunIdsData()
    {
        return array(
            array(140, 1),
            array(0, 0)
        );
    }

    /**
     * Test getTestsetRunIds
     * @dataProvider testGetTestsetRunIdsData
     */
    public function testGetTestsetRunIds($confRunId, $has_data)
    {
        $dbAdmin = Factory::dbAdmin();
        $result = $dbAdmin->getTestsetRunIds($confRunId);
        foreach($result as $row) {
            $this->assertArrayHasKey('id', $row);
        }
        if ($has_data) {
            $this->assertNotEmpty($result);
        } else {
            $this->assertEmpty($result);
        }
    }
    public function testGetTestsetRunIdsData()
    {
        return array(
            array(260, 1),
            array(0, 0)
        );
    }

    /**
     * Test getTestfunctionRunIds
     * @dataProvider testGetTestfunctionRunIdsData
     */
    public function testGetTestfunctionRunIds($testsetRunId, $has_data)
    {
        $dbAdmin = Factory::dbAdmin();
        $result = $dbAdmin->getTestfunctionRunIds($testsetRunId);
        foreach($result as $row) {
            $this->assertArrayHasKey('id', $row);
        }
        if ($has_data) {
            $this->assertNotEmpty($result);
        } else {
            $this->assertEmpty($result);
        }
    }
    public function testGetTestfunctionRunIdsData()
    {
        return array(
            array(95, 1),
            array(0, 0)
        );
    }

    /**
     * Test deleteProjectRun, deleteConfRuns, deletePhaseRuns, deleteTestsetRuns, deleteTestfunctionRuns, deleteTestrowRuns
     * @dataProvider testDeleteRunData
     */
    public function testDeleteRun($projectRunId, $confRunId, $phaseRunId, $testsetRunId, $testfunctionRunId, $valid)
    {
        if (self::DELETE_TEST_TYPE === self::DELETE_FROM_RUN_TABLE) {
            $dbAdmin = Factory::dbAdmin();
            $before = $dbAdmin->getTablesStatistics();
            $result = $dbAdmin->deleteProjectRun($projectRunId);
            $this->assertTrue($result);
            $result = $dbAdmin->deleteConfRuns($projectRunId);
            $this->assertTrue($result);
            $result = $dbAdmin->deletePhaseRuns($confRunId);
            $this->assertTrue($result);
            $result = $dbAdmin->deleteTestsetRuns($confRunId);
            $this->assertTrue($result);
            $result = $dbAdmin->deleteTestfunctionRuns($testsetRunId);
            $this->assertTrue($result);
            $result = $dbAdmin->deleteTestrowRuns($testfunctionRunId);
            $this->assertTrue($result);
            $after = $dbAdmin->getTablesStatistics();
            foreach($after as $key => $row) {
                if (strpos($row['name'],'_run') !== false) {
                    if ($valid) {
                        $this->assertLessThan($before[$key]['rowCount'], $row['rowCount']);
                    } else {
                        $this->assertEquals($before[$key]['rowCount'], $row['rowCount']);
                    }
                }
            }
        }
    }
    public function testDeleteRunData()
    {
        return array(
            array(140, 103, 280, 17, 23, 1),            // Test data for testfunction "defaultFamily"
            array(999, 999, 999, 999, 999, 0)
        );
    }

    /**
     * Test deleteProjectRunData
     * @dataProvider testDeleteProjectRunDataData
     */
    public function testDeleteProjectRunData($projectRunId, $valid)
    {
        if (self::DELETE_TEST_TYPE === self::DELETE_PROJECT_RUN_DATA) {
            $dbAdmin = Factory::dbAdmin();
            $before = $dbAdmin->getTablesStatistics();
            $result = $dbAdmin->deleteProjectRunData($projectRunId);
            $this->assertTrue($result);
            $after = $dbAdmin->getTablesStatistics();
            foreach($after as $key => $row) {
                if (strpos($row['name'],'_run') !== false) {
                    if ($valid) {
                        $this->assertLessThan($before[$key]['rowCount'], $row['rowCount']);
                    } else {
                        $this->assertEquals($before[$key]['rowCount'], $row['rowCount']);
                    }
                }
            }
        }
    }
    public function testDeleteProjectRunDataData()
    {
        return array(
            array(140, 1),
            array(999, 0)
        );
    }

    /**
     * Test deleteBranch and deleteProjectRunData
     * @dataProvider testDeleteBranchData
     */
    public function testDeleteBranch($runProject, $runState, $branch, $has_data, $step)
    {
        if (self::DELETE_TEST_TYPE === self::DELETE_BRANCH) {
            $db = Factory::db();
            $dbAdmin = Factory::dbAdmin();
            // Check that xxx_run tables have data initially
            if ($step == 'first') {
                $result = $dbAdmin->getTablesStatistics();
                foreach($result as $row) {
                    if (strpos($row['name'],'_run') !== false) {
                        $this->assertGreaterThan(0, $row['rowCount']);
                    }
                }
            }
            if ($has_data) {
                // Check that project_runs exist for the branch initially
                $result = $db->getProjectBuildsByBranch($runProject, $runState);
                $branches = array();
                foreach($result as $row) {
                    $branches[] = $row['branch'];
                }
                $this->assertContains($branch, $branches);
                // Check that conf_runs exist for the branch initially
                $result = $db->getConfBuildsByBranch($runProject, $runState);
                $branches = array();
                foreach($result as $row) {
                    $branches[] = $row['branch'];
                }
                $this->assertContains($branch, $branches);
            }
            // Delete the branch data
            $branches = array();
            $result = $db->getBranches();
            foreach($result as $row) {
                $branches[] = $row['name'];
            }
            $this->assertContains($branch, $branches);
            $success = $dbAdmin->deleteBranch($branch);
            $this->assertTrue($success);
            $branches = array();
            $result = $db->getBranches();
            foreach($result as $row) {
                $branches[] = $row['name'];
            }
            $this->assertNotContains($branch, $branches);
            // Check if project_runs deleted
            $result = $db->getProjectBuildsByBranch($runProject, $runState);
            $branches = array();
            foreach($result as $row) {
                $branches[] = $row['branch'];
            }
            $this->assertNotContains($branch, $branches);
            // Check if conf_runs deleted
            $result = $db->getConfBuildsByBranch($runProject, $runState);
            $branches = array();
            foreach($result as $row) {
                $branches[] = $row['branch'];
            }
            $this->assertNotContains($branch, $branches);
            // Check that xxx_run tables are empty
            if ($step == 'last') {
                $result = $dbAdmin->getTablesStatistics();
                foreach($result as $row) {
                    if (strpos($row['name'],'_run') !== false) {
                        $this->assertEquals(0, $row['rowCount']);
                    }
                }
            }
        }
    }
    public function testDeleteBranchData()
    {
        return array(
            array('Qt5', 'state', 'dev', 1, 'first'),
            array('Qt5', 'state', 'stable', 1, ''),
            array('Qt5', 'state', 'release', 1, ''),
            array('Qt5', 'state', 'master', 1, ''),
            array('Qt5', 'state', '1.2.3', 0, 'last')
        );
    }

    /**
     * Test deleteRunsData and deleteProjectRunData
     * @dataProvider testDeleteRunsDataData
     */
    public function testDeleteRunsData($runProject, $runState, $state, $date, $has_data, $step)
    {
        if (self::DELETE_TEST_TYPE === self::DELETE_RUN_DATA) {
            $db = Factory::db();
            $dbAdmin = Factory::dbAdmin();
            if ($step == 'first') {
                // Check that xxx_run tables have data
                $result = $dbAdmin->getTablesStatistics();
                foreach($result as $row) {
                    if (strpos($row['name'],'_run') !== false) {
                        $this->assertGreaterThan(0, $row['rowCount']);
                    }
                }
            }
            if ($has_data) {
                // Check that project_runs exist initially
                $result = $db->getProjectBuildsByBranch($runProject, $runState);
                $dates = array();
                foreach($result as $row) {
                    $dates[] = substr($row['timestamp'], 0, strlen('2015-08-01'));
                }
                $this->assertContains($date, $dates);
            }
            // Delete the data
            $dbAdmin->deleteRunsData($state, $date);
            // Check if project_runs deleted
            $result = $db->getProjectBuildsByBranch($runProject, $runState);
            $dates = array();
            foreach($result as $row) {
                $dates[] = substr($row['timestamp'], 0, strlen('2015-08-01'));
            }
            $this->assertNotContains($date, $dates);
            if ($step == 'last') {
                // Check that xxx_run tables are empty
                $result = $dbAdmin->getTablesStatistics();
                foreach($result as $row) {
                    if (strpos($row['name'],'_run') !== false) {
                        $this->assertEquals(0, $row['rowCount']);
                    }
                }
            }
        }
    }
    public function testDeleteRunsDataData()
    {
        return array(
            array('Qt5', 'state', 'state', '2013-05-20', '1', 'first'),
            array('Qt5', 'state', 'state', '2013-04-02', '1', ''),
            array('Qt5', 'state', 'state', '2013-03-20', '1', ''),
            array('Qt5', 'state', 'state', '2013-02-01', '0', ''),
            array('Qt5', 'state', 'state', '2012-12-01', '1', '')
        );
    }

    /**
     * Print info on the delete test type
     */
    public function testPrintDeleteTestInfo()
    {
        switch (self::DELETE_TEST_TYPE) {
            case self::DELETE_FROM_RUN_TABLE:
                echo ' Note: DatabaseAdmin delete test type DELETE_FROM_RUN_TABLE selected.';
                echo ' Please re-insert the data into the database before rerunning the tests!';
                break;
            case self::DELETE_PROJECT_RUN_DATA:
                echo ' Note: DatabaseAdmin delete test type DELETE_PROJECT_RUN_DATA selected.';
                echo ' Please re-insert the data into the database before rerunning the tests!';
                break;
            case self::DELETE_BRANCH:
                echo ' Note: DatabaseAdmin delete test type DELETE_BRANCH selected.';
                echo ' Please re-insert the data into the database before rerunning the tests!';
                break;
            case self::DELETE_RUN_DATA:
                echo ' Note: DatabaseAdmin delete test type DELETE_RUN_DATA selected.';
                echo ' Please re-insert the data into the database before rerunning the tests!';
                break;
        }
    }

}

?>
