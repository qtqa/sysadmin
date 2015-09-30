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
 * Configuration unit test class
 * @example   To run (in qtmetrics root directory): php <path-to-phpunit>/phpunit.phar ./src/test
 * @since     16-07-2015
 * @author    Juha Sippola
 */

class ConfTest extends PHPUnit_Framework_TestCase
{

    /**
     * Test getName
     * @dataProvider testGetNameData
     */
    public function testGetName($name)
    {
        $conf = new Conf($name);
        $this->assertEquals($name, $conf->getName());
    }
    public function testGetNameData()
    {
        return array(
            array('linux-android-g++_Ubuntu_12.04_x64'),
            array('win32-msvc2010_developer-build_angle_Windows_7'),
            array('MyConf')
        );
    }

    /**
     * Test getStatus and setStatus
     * @dataProvider testGetStatusData
     */
    public function testGetStatus($name, $runProject, $runState, $exp_build_results, $has_data)
    {
        $conf = new Conf($name);
        if ($has_data) {
            $conf->setStatus($runProject, $runState);
            $this->assertContains($conf->getStatus(), $exp_build_results);
        } else {
            $this->assertEmpty($conf->getStatus());
        }
    }
    public function testGetStatusData()
    {
        return array(
            array('linux-android-g++_Ubuntu_12.04_x64', 'Qt5', 'state', array('SUCCESS', 'FAILURE', 'ABORTED', 'undef'), 1),
            array('win32-msvc2010_developer-build_angle_Windows_7', 'Qt5', 'state', array('SUCCESS', 'FAILURE', 'ABORTED', 'undef'), 1),
            array('InvalidConf', 'Qt5', 'state', array(), 0)
        );
    }

}

?>
