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
 * Testset class
 * @since     30-09-2015
 * @author    Juha Sippola
 */

class Testset {

    /**
     * Testset status (starting from ok case and most fatal the last i.e. latter overwrites earlier)
     */
    const STATUS_EMPTY   = 0;
    const STATUS_SUCCESS = 1;
    const STATUS_FAILURE = 2;

    /**
     * Testset name.
     * @var string
     */
    private $name;

    /**
     * Project name the testset belongs to.
     * @var string
     */
    private $projectName;

    /**
     * Testset status calculated from the latest configuration build results.
     * @var int
     */
    private $status;

    /**
     * Count of testset results in the Project builds run since the last n days (all configurations).
     * @var array (int passed, int failed)
     */
    private $testsetResultCounts;

    /**
     * Count of flaky testsets in the Project builds run since the last n days (all configurations).
     * @var array (int flaky, int total)
     */
    private $testsetFlakyCounts;

    /**
     * Testset constructor.
     * Testset indicates the status in its latest runs in state configuration in all branches
     * @param string $name
     * @param string $projectName
     */
    public function __construct($name, $projectName)
    {
        $this->name = $name;
        $this->projectName = $projectName;
        $this->status = TestsetRun::RESULT_EMPTY;                               // not initially set
        $this->testsetResultCounts = array('passed' => null, 'failed' => null); // not initially set
        $this->testsetFlakyCounts = array('flaky' => null, 'total' => null);    // not initially set
    }

    /**
     * Get name of the testset.
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get project name of the testset.
     * @return string
     */
    public function getProjectName()
    {
        return $this->projectName;
    }

    /**
     * Get status of the testset calculated from the latest configuration build results.
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set status of the testset calculated from the latest configuration build results (in specified builds only).
     * @param string $runProject
     * @param string $runState
     */
    public function setStatus($runProject, $runState)
    {
        $builds = Factory::db()->getLatestTestsetConfBuildResults($this->name, $this->projectName, $runProject, $runState);
        $status = self::STATUS_EMPTY;
        $statusText = TestsetRun::RESULT_EMPTY;
        foreach ($builds as $build) {
            if (TestsetRun::stripResult($build['result']) === TestsetRun::RESULT_SUCCESS AND $status <= self::STATUS_SUCCESS) {
                $status = self::STATUS_SUCCESS;
                $statusText = TestsetRun::RESULT_SUCCESS;
            }
            if (TestsetRun::stripResult($build['result']) === TestsetRun::RESULT_FAILURE AND $status <= self::STATUS_FAILURE) {
                $status = self::STATUS_FAILURE;
                $statusText = TestsetRun::RESULT_FAILURE;
            }
        }
        $this->status = $statusText;
        return;
    }

    /**
     * Get count of testset results in latest Project builds (all configurations, specified builds only).
     * @return array (int passed, int failed)
     */
    public function getTestsetResultCounts()
    {
        return $this->testsetResultCounts;
    }

    /**
     * Set count of testset results in latest Project builds (all configurations, specified builds only).
     */
    public function setTestsetResultCounts($passed, $failed)
    {
        $this->testsetResultCounts = array('passed' => $passed, 'failed' => $failed);
        return;
    }

    /**
     * Get count of flaky testsets in latest Project builds (all configurations, all builds).
     * @return array (int passed, int failed)
     */
    public function getTestsetFlakyCounts()
    {
        return $this->testsetFlakyCounts;
    }

    /**
     * Set count of flaky testsets in latest Project builds (all configurations, all builds).
     */
    public function setTestsetFlakyCounts($flaky, $total)
    {
        $this->testsetFlakyCounts = array('flaky' => $flaky, 'total' => $total);
        return;
    }

}

?>
