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
 * @version   0.1
 * @since     04-06-2015
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
            array('', 3),                           // test data includes three testsets
            array('f', 3),                          // all
            array('ft', 2),                         // tst_qftp and tst_networkselftest
            array('ftp', 1),                        // tst_qftp
            array('tst_qftp', 1),
            array('tst_qfont', 1),
            array('tst_qfon', 1),
            array('tst_qfontt', 0),
            array('qfont', 1),
            array('invalid-name', 0)
        );
    }

    /**
     * Test createProjects
     * @dataProvider testCreateProjectsData
     */
    public function testCreateProjects($status_check)
    {
        $projects = Factory::createProjects();
        foreach($projects as $project) {
            $this->assertTrue($project instanceof Project);
            if (in_array($project->getName(), $status_check)) {         // check only the projects with project_run data
                $this->assertNotEmpty($project->getStatus());
            }
        }
    }
    public function testCreateProjectsData()
    {
        return array(
            array(array('QtBase', 'Qt5', 'QtConnectivity'))
        );
    }

    /**
     * Test createTestsets
     * @dataProvider testCreateTestsetsData
     */
    public function testCreateTestsets($list_type, $status_check, $result_check, $flaky_check)
    {
        $testsets = Factory::createTestsets($list_type);
        foreach($testsets as $testset) {
            $this->assertTrue($testset instanceof Testset);
            $status = $testset->getStatus();
            if (in_array($testset->getName(), $status_check)) {
                $this->assertNotEmpty($status);
            } else {
                $this->assertEmpty($status);
            }
            $result = $testset->getTestsetResultCounts();
            if (in_array($testset->getName(), $result_check)) {
                $this->assertNotNull($result);
                $this->assertArrayHasKey('passed', $result);
                $this->assertArrayHasKey('failed', $result);
            } else {
                $this->assertNull($result);
            }
            $flaky = $testset->getTestsetFlakyCounts();
            if (in_array($testset->getName(), $flaky_check)) {
                $this->assertNotNull($flaky);
                $this->assertArrayHasKey('flaky', $flaky);
                $this->assertArrayHasKey('total', $flaky);
            } else {
                $this->assertNull($flaky);
            }
        }
    }
    public function testCreateTestsetsData()
    {
        return array(
            array(
                Factory::LIST_FAILURES,
                array('tst_qftp', 'tst_qfont', 'tst_networkselftest'),  // check only the testsets with testset_run data
                array('tst_qftp', 'tst_qfont', 'tst_networkselftest'),  // check only the testsets with testset_run data
                array('not-set')                                        // flaky data not set for failures list
            ),
            array(
                Factory::LIST_FLAKY,
                array('not-set'),                                       // status not set for flaky list
                array('not-set'),                                       // result data not set for flaky list
                array('tst_qftp', 'tst_qfont', 'tst_networkselftest')   // check only the testsets with testset_run data
            )
        );
    }

    /**
     * Test createTestset
     * @dataProvider testCreateTestsetData
     */
    public function testCreateTestset($testset, $project)
    {
        $testsets = Factory::createTestset($testset);
        foreach($testsets as $testset) {
            $this->assertTrue($testset instanceof Testset);
            if ($testset->getProjectName() == $project) {
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
            array('tst_qftp', 'QtBase'),                                // testset with testset_run data
            array('tst_qfont', 'QtBase')                                // testset with testset_run data
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
