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
 * TestsetRun class
 * @version   0.2
 * @since     12-06-2015
 * @author    Juha Sippola
 */

class TestsetRun extends ProjectRun {

    /**
     * Testset build results (these must follow the enumeration in the database; excluding the insignificance flag)
     */
    const RESULT_NOT_SET = NULL;
    const RESULT_EMPTY   = "";
    const RESULT_SUCCESS = "passed";
    const RESULT_FAILURE = "failed";

    /**
     * Testset name.
     * @var string
     */
    private $name;

    /**
     * Configuration name.
     * @var string
     */
    private $confName;

    /**
     * Run number (a failed test is repeated once).
     * @var int
     */
    private $run;

    /**
     * Insignificance flag (true = insignificant).
     * @var bool
     */
    private $insignificant;

    /**
     * TestsetRun constructor.
     * TestsetRun include the result in the project configuration build
     * @param string $testsetName
     * @param string $projectName
     * @param string $branchName
     * @param string $stateName
     * @param int $buildKey
     * @param string $configurationName
     * @param int $run (ordinal number)
     * @param string $result (plain result without any possible flags)
     * @param bool $insignificant (true = insignificant)
     * @param int $timestamp
     * @param int $duration
     */
    public function __construct($name, $projectName, $branchName, $stateName, $buildKey, $confName, $run, $result, $insignificant, $timestamp, $duration) {
        parent::__construct($projectName, $branchName, $stateName, $buildKey, $result, $timestamp, $duration);
        $this->name = $name;
        $this->confName = $confName;
        $this->run = $run;
        $this->insignificant = $insignificant;
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
     * Get insignificance flag.
     * @return bool (true = insignificant)
     */
    public function getInsignificant()
    {
        return $this->insignificant;
    }

    /**
     * Strip the result from the combined insignificance-result string
     * @param string $resultString
     * @return string
     */
    public static function stripResult($resultString)
    {
        $resultString = str_replace('ipass', 'pass', $resultString);        // remove the possible insignificant flag
        $resultString = str_replace('ifail', 'fail', $resultString);        // remove the possible insignificant flag
        return $resultString;
    }

    /**
     * Check the insignificance flag from the combined insignificance-result string
     * @param string $resultString
     * @return bool (true = insignificant)
     */
    public static function isInsignificant($resultString)
    {
        $flag = false;
        if (strpos($resultString, 'i') == 0)                                // begins with 'i'
            $flag = true;
        return $flag;
    }

}

?>
