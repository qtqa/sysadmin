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
 * TestrowRun class
 * @since     09-09-2015
 * @author    Juha Sippola
 */

class TestrowRun extends TestfunctionRun {

    /**
     * Testfunction name.
     * @var string
     */
    private $testfunctionName;

    /**
     * TestrowRun constructor.
     * @param string $name
     * @param string $testfunctionName
     * @param string $testsetName
     * @param string $testsetProjectName
     * @param string $projectName
     * @param string $branchName
     * @param string $stateName
     * @param int $buildKey
     * @param string $confName
     * @param string $result (plain result without any possible flags)
     * @param bool $blacklisted (true = blacklisted)
     * @param string $timestamp
     */
    public function __construct($name, $testfunctionName, $testsetName, $testsetProjectName, $projectName, $branchName, $stateName, $buildKey, $confName, $result, $blacklisted, $timestamp) {
        parent::__construct($testfunctionName, $testsetName, $testsetProjectName, $projectName, $branchName, $stateName, $buildKey, $confName, $result, $blacklisted, $timestamp, 0);
        $this->name = $name;
        $this->testfunctionName = $testfunctionName;
    }

    /**
     * Get name of the testrow.
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get short name of the testfunction.
     * @return string
     */
    public function getShortName()
    {
        if (strlen($this->name) > parent::SHORT_NAME_LENGTH)
            return substr($this->name, 0, parent::SHORT_NAME_LENGTH - 10) . '...' . substr($this->name, -7);
        else
            return $this->name;
    }

    /**
     * Get name of the testfunction.
     * @return string
     */
    public function getTestfunctionName()
    {
        return $this->testfunctionName;
    }

}

?>
