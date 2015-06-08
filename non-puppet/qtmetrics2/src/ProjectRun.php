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
 * ProjectRun class
 * @version   0.1
 * @since     07-05-2015
 * @author    Juha Sippola
 */

class ProjectRun {

    /**
     * Project build results (these must follow the enumeration in the database)
     */
    const RESULT_NOT_SET = NULL;
    const RESULT_EMPTY   = "";
    const RESULT_SUCCESS = "SUCCESS";
    const RESULT_FAILURE = "FAILURE";
    const RESULT_ABORTED = "ABORTED";

    /**
     * Project name.
     * @var string
     */
    private $projectName;

    /**
     * Branch name.
     * @var string
     */
    private $branchName;

    /**
     * State name.
     * @var string
     */
    private $stateName;

    /**
     * Build number.
     * @var int
     */
    private $buildNumber;

    /**
     * Build result.
     * @var string
     */
    private $result;

    /**
     * Timestamp (Unix timestamp).
     * @var int
     */
    private $timestamp;

    /**
     * Duration (seconds).
     * @var int
     */
    private $duration;

    /**
     * ProjectRun constructor.
     * ProjectRun include the result of the project build
     * @param string $projectName
     * @param string $branchName
     * @param string $stateName
     * @param int $buildNumber
     * @param string $result
     * @param int $timestamp
     * @param int $duration
     */
    public function __construct($projectName, $branchName, $stateName, $buildNumber, $result, $timestamp, $duration)
    {
        $this->projectName = $projectName;
        $this->branchName = $branchName;
        $this->stateName = $stateName;
        $this->buildNumber = $buildNumber;
        $this->result = $result;
        $this->timestamp = $timestamp;
        $this->duration = $duration;
    }

    /**
     * Get name of the project.
     * @return string
     */
    public function getProjectName()
    {
        return $this->projectName;
    }

    /**
     * Get name of the branch.
     * @return string
     */
    public function getBranchName()
    {
        return $this->branchName;
    }

    /**
     * Get name of the state.
     * @return string
     */
    public function getStateName()
    {
        return $this->stateName;
    }

    /**
     * Get full name of the project build as used in CI.
     * @return string
     */
    public function getFullProjectName()
    {
        return $this->projectName . '_' . $this->branchName . '_' . $this->stateName;
    }

    /**
     * Get build number.
     * @return int
     */
    public function getBuildNumber()
    {
        return $this->buildNumber;
    }

    /**
     * Get result (plain result without any possible flags).
     * @return string
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * Get build timestamp.
     * @return int
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * Get build duration.
     * @return int
     */
    public function getDuration()
    {
        return $this->duration;
    }

}

?>
