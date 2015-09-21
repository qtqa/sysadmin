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
 * Test function class
 * @since     21-09-2015
 * @author    Juha Sippola
 */

class Testfunction {

    /**
     * If the testfunction name long, a shorter version of the name can be requested
     */
    const SHORT_NAME_LENGTH = 50;

    /**
     * Testfunction name.
     * @var string
     */
    private $name;

    /**
     * Testset name the testfunction belongs to.
     * @var string
     */
    private $testsetName;

    /**
     * Project name the testset belongs to.
     * @var string
     */
    private $testsetProjectName;

    /**
     * Configuration name.
     * @var string
     */
    private $confName;

    /**
     * Count of testfunction results in the Project builds run since the last n days (all configurations).
     * @var array (int passed, int failed, int skipped)
     */
    private $resultCounts;

    /**
     * Count of testfunction blacklisted results in the Project builds run since the last n days (all configurations).
     * @var array (int bpassed, int btotal)
     */
    private $blacklistedCounts;

    /**
     * Testfunction constructor.
     * @param string $name
     * @param string $testsetName
     * @param string $testsetProjectName
     */
    public function __construct($name, $testsetName, $testsetProjectName, $confName)
    {
        $this->name = $name;
        $this->testsetName = $testsetName;
        $this->testsetProjectName = $testsetProjectName;
        $this->confName = $confName;
        $this->resultCounts = array('passed' => null, 'failed' => null, 'skipped' => null); // not initially set
        $this->blacklistedCounts = array('bpassed' => null, 'btotal' => null);              // not initially set
    }

    /**
     * Get name of the testfunction.
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
        if (strlen($this->name) > self::SHORT_NAME_LENGTH)
            return substr($this->name, 0, self::SHORT_NAME_LENGTH - 10) . '...' . substr($this->name, -7);
        else
            return $this->name;
    }

    /**
     * Get testset name of the testfunction.
     * @return string
     */
    public function getTestsetName()
    {
        return $this->testsetName;
    }

    /**
     * Get project name of the testset.
     * @return string
     */
    public function getTestsetProjectName()
    {
        return $this->testsetProjectName;
    }

    /**
     * Get conf name.
     * @return string
     */
    public function getConfName()
    {
        return $this->confName;
    }

    /**
     * Get count of testfunction results in latest Project builds (all configurations, specified builds only).
     * @return array (int passed, int failed, int skipped)
     */
    public function getResultCounts()
    {
        return $this->resultCounts;
    }

    /**
     * Set count of testfunction results in latest Project builds (all configurations, specified builds only).
     */
    public function setResultCounts($passed, $failed, $skipped)
    {
        $this->resultCounts = array('passed' => $passed, 'failed' => $failed, 'skipped' => $skipped);
        return;
    }

    /**
     * Get count of testfunction blacklisted results in latest Project builds (all configurations, specified builds only).
     * @return array (int bpassed, int btotal)
     */
    public function getBlacklistedCounts()
    {
        return $this->blacklistedCounts;
    }

    /**
     * Set count of testfunction blacklisted results in latest Project builds (all configurations, specified builds only).
     */
    public function setBlacklistedCounts($bpassed, $btotal)
    {
        $this->blacklistedCounts = array('bpassed' => $bpassed, 'btotal' => $btotal);
        return;
    }

}

?>
