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
 * TestfunctionRun class
 * @since     08-09-2015
 * @author    Juha Sippola
 */

class TestfunctionRun extends ProjectRun {

    /**
     * Testfunction results (these must follow the enumeration in the database; excluding the blacklisted flag)
     */
    const RESULT_NOT_SET            = NULL;
    const RESULT_EMPTY              = "";
    const RESULT_NA                 = "na";
    const RESULT_SUCCESS            = "pass";
    const RESULT_SUCCESS_UNEXPECTED = "xpass";
    const RESULT_FAILURE            = "fail";
    const RESULT_FAILURE_EXPECTED   = "xfail";
    const RESULT_SKIP               = "skip";

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
     * Testset name.
     * @var string
     */
    private $testsetName;

    /**
     * Testset project name.
     * @var string
     */
    private $testsetProjectName;

    /**
     * Configuration name.
     * @var string
     */
    private $confName;

    /**
     * Blacklisted flag (true = blacklisted).
     * @var bool
     */
    private $blacklisted;

    /**
     * TestfunctionRun constructor.
     * @param string $name
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
     * @param int $duration (in deciseconds)
     */
    public function __construct($name, $testsetName, $testsetProjectName, $projectName, $branchName, $stateName, $buildKey, $confName, $result, $blacklisted, $timestamp, $duration) {
        parent::__construct($projectName, $branchName, $stateName, $buildKey, $result, $timestamp, $duration);
        $this->name = $name;
        $this->testsetName = $testsetName;
        $this->testsetProjectName = $testsetProjectName;
        $this->confName = $confName;
        $this->blacklisted = $blacklisted;
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
     * Get name of the testset project.
     * @return string
     */
    public function getTestsetProjectName()
    {
        return $this->testsetProjectName;
    }

    /**
     * Get name of the testset.
     * @return string
     */
    public function getTestsetName()
    {
        return $this->testsetName;
    }

    /**
     * Get configuration name.
     * @return string
     */
    public function getConfName()
    {
        return $this->confName;
    }

    /**
     * Get blacklisted flag.
     * @return bool (true = blacklisted)
     */
    public function getBlacklisted()
    {
        return $this->blacklisted;
    }

    /**
     * Strip the result from the combined blacklisted-result string
     * @param string $resultString
     * @return string
     */
    public static function stripResult($resultString)
    {
        $resultString = str_replace('bpass', 'pass', $resultString);        // remove the possible blacklisted flag
        $resultString = str_replace('bfail', 'fail', $resultString);        // remove the possible blacklisted flag
        $resultString = str_replace('bx', 'x', $resultString);              // remove the possible blacklisted flag
        $resultString = str_replace('bskip', 'skip', $resultString);        // remove the possible blacklisted flag
        return $resultString;
    }

    /**
     * Check the blacklisted flag from the combined blacklisted-result string
     * @param string $resultString
     * @return bool (true = blacklisted)
     */
    public static function isBlacklisted($resultString)
    {
        $flag = false;
        if (strpos($resultString, 'b') === 0)                               // begins with 'b'
            $flag = true;
        return $flag;
    }

}

?>
