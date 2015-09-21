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
 * @since     17-09-2015
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
    const RESULT_TESTROW_SUCCESS    = "tr_pass";
    const RESULT_TESTROW_FAILURE    = "tr_fail";
    const RESULT_TESTROW_SKIP       = "tr_skip";

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
     * Children (true = has children).
     * @var bool
     */
    private $children;

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
     * @param bool $children (true = has children)
     * @param string $timestamp
     * @param int $duration (in deciseconds)
     */
    public function __construct($name, $testsetName, $testsetProjectName, $projectName, $branchName, $stateName, $buildKey, $confName, $result, $blacklisted, $children, $timestamp, $duration) {
        parent::__construct($projectName, $branchName, $stateName, $buildKey, $result, $timestamp, $duration);
        $this->name = $name;
        $this->testsetName = $testsetName;
        $this->testsetProjectName = $testsetProjectName;
        $this->confName = $confName;
        $this->blacklisted = $blacklisted;
        $this->children = $children;
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
     * Get indication if children exist.
     * @return bool (true = has $children)
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Strip the result in database from the combined blacklisted-result string or testrow-result string
     * @param string $resultString
     * @return string
     */
    public static function stripResult($resultString)
    {
        $resultString = str_replace('bpass', 'pass', $resultString);        // remove the possible blacklisted flag
        $resultString = str_replace('bfail', 'fail', $resultString);        // remove the possible blacklisted flag
        $resultString = str_replace('bx', 'x', $resultString);              // remove the possible blacklisted flag
        $resultString = str_replace('bskip', 'skip', $resultString);        // remove the possible blacklisted flag
        $resultString = str_replace('tr_pass', 'pass', $resultString);      // replace the possible calculated testrow result
        $resultString = str_replace('tr_fail', 'fail', $resultString);      // replace the possible calculated testrow result
        $resultString = str_replace('tr_skip', 'skip', $resultString);      // replace the possible calculated testrow result
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

    /**
     * Check if the testfunction has children (testrows)
     * @param string $resultString
     * @return bool (true = has children)
     */
    public static function hasChildren($resultString)
    {
        $flag = false;
        if (strpos($resultString, 'tr_') === 0)                             // calculated testrow results begin with 'tr_'
            $flag = true;
        return $flag;
    }

    /**
     * Get build directory link.
     * @return string
     */
    public function getBuildLink()
    {
        return Factory::getCiLogPath()
            . urlencode(parent::getFullProjectName())
            . '/build_' . parent::getBuildKeyString()
            . '/' . urlencode($this->confName);
    }

}

?>
