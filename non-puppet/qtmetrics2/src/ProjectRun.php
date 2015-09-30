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
 * @since     30-09-2015
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
     * If the build key is long, a shorter version of the key can be requested
     */
    const SHORT_BUILDKEY_LENGTH = 6;

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
     * Build key.
     * @var string
     */
    private $buildKey;

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
     * @param int $buildKey
     * @param string $result
     * @param string $timestamp
     * @param int $duration
     */
    public function __construct($projectName, $branchName, $stateName, $buildKey, $result, $timestamp, $duration)
    {
        $this->projectName = $projectName;
        $this->branchName = $branchName;
        $this->stateName = $stateName;
        $this->buildKey = $buildKey;
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
     * Get build key.
     * @return string
     */
    public function getBuildKey()
    {
        return $this->buildKey;
    }

    /**
     * Get build key short version.
     * @return string
     */
    public function getShortBuildKey()
    {
        if (strlen($this->buildKey) > self::SHORT_BUILDKEY_LENGTH)
            return substr($this->buildKey, 0, self::SHORT_BUILDKEY_LENGTH - 2) . '...';
        else
            return $this->buildKey;
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

    /**
     * Convert the numeric build key to a five digit string needed for directory links (e.g. 123 to 00123)
     * @return string
     */
    public function getBuildKeyString()
    {
        $buildString = $this->buildKey;
        if (is_numeric($buildString)) {
            if ($this->buildKey < 10000)
                $buildString = '0' . $this->buildKey;
            if ($this->buildKey < 1000)
                $buildString = '00' . $this->buildKey;
            if ($this->buildKey < 100)
                $buildString = '000' . $this->buildKey;
            if ($this->buildKey < 10)
                $buildString = '0000' . $this->buildKey;
        }
        return $buildString;
    }

    /**
     * Get build directory link.
     * @return string
     */
    public function getBuildLink()
    {
        return Factory::getCiLogPath()
            . urlencode(self::getFullProjectName())
            . '/build_' . self::getBuildKeyString();
    }

    /**
     * Get log file link.
     * @return string
     */
    public function getLogLink()
    {
        return Factory::getCiLogPath()
            . urlencode(self::getFullProjectName())
            . '/build_' . self::getBuildKeyString()
            . '/log.txt.gz';
    }

}

?>
