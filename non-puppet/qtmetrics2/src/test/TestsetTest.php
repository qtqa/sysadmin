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
 * Testset unit test class
 * @example   To run (in qtmetrics root directory): php <path-to-phpunit>/phpunit.phar ./src/test
 * @since     11-06-2015
 * @author    Juha Sippola
 */

class TestsetTest extends PHPUnit_Framework_TestCase
{

    /**
     * Test getName, getProjectName
     * @dataProvider testGetNameData
     */
    public function testGetName($name, $project)
    {
        $testset = new Testset($name, $project);
        $this->assertEquals($name, $testset->getName());
        $this->assertEquals($project, $testset->getProjectName());
    }
    public function testGetNameData()
    {
        return array(
            array('tst_qftp', 'QtBase'),
            array('tst_my_test', 'myProject')
        );
    }

    /**
     * Test setStatus and getStatus
     * @dataProvider testGetStatusData
     */
    public function testGetStatus($name, $project, $runProject, $runState, $exp_results)
    {
        $testset = new Testset($name, $project);
        $testset->setStatus($runProject, $runState);
        $this->assertContains($testset->getStatus(), $exp_results);
    }
    public function testGetStatusData()
    {
        return array(
            array('tst_qftp', 'QtBase', 'Qt5', 'state', array('passed', 'failed', 'ipassed', 'ifailed')),
            array('tst_invalid', 'QtBase', 'Qt5', 'state', array(''))
        );
    }

    /**
     * Test setTestsetResultCounts and getTestsetResultCounts
     * @dataProvider testGetTestsetResultCountsData
     */
    public function testGetTestsetResultCounts($name, $project, $passed, $failed)
    {
        $testset = new Testset($name, $project);
        // Counts not set
        $result = $testset->getTestsetResultCounts();
        $this->assertArrayHasKey('passed', $result);
        $this->assertArrayHasKey('failed', $result);
        $this->assertNull($result['passed']);
        $this->assertNull($result['failed']);
        // Counts set
        $testset->setTestsetResultCounts($passed, $failed);
        $result = $testset->getTestsetResultCounts();
        $this->assertArrayHasKey('passed', $result);
        $this->assertArrayHasKey('failed', $result);
        $this->assertEquals($passed, $result['passed']);
        $this->assertEquals($failed, $result['failed']);
    }
    public function testGetTestsetResultCountsData()
    {
        return array(
            array('tst_qftp', 'QtBase', 1, 2),
            array('tst_qftp', 'Qt5', 123456, 654321),
            array('tst_my_test', 'myProject', 7, 14)
        );
    }

    /**
     * Test setTestsetFlakyCounts and getTestsetFlakyCounts
     * @dataProvider testGetTestsetFlakyCountsData
     */
    public function testGetTestsetFlakyCounts($name, $project, $flaky, $total)
    {
        $testset = new Testset($name, $project);
        // Counts not set
        $result = $testset->getTestsetFlakyCounts();
        $this->assertArrayHasKey('flaky', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertNull($result['flaky']);
        $this->assertNull($result['total']);
        // Counts set
        $testset->setTestsetFlakyCounts($flaky, $total);
        $result = $testset->getTestsetFlakyCounts();
        $this->assertArrayHasKey('flaky', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertEquals($flaky, $result['flaky']);
        $this->assertEquals($total, $result['total']);
    }
    public function testGetTestsetFlakyCountsData()
    {
        return array(
            array('tst_qftp', 'QtBase', 1, 2),
            array('tst_qftp', 'Qt5', 123456, 654321),
            array('tst_my_test', 'myProject', 7, 14)
        );
    }

}

?>
