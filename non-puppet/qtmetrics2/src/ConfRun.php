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
 * ConfRun class
 * @version   0.2
 * @since     24-07-2015
 * @author    Juha Sippola
 */

class ConfRun extends ProjectRun {

    /**
     * Conf build results (these must follow the enumeration in the database)
     */
    const RESULT_NOT_SET = NULL;
    const RESULT_EMPTY   = "";
    const RESULT_SUCCESS = "SUCCESS";
    const RESULT_FAILURE = "FAILURE";
    const RESULT_ABORTED = "ABORTED";
    const RESULT_UNDEF = "undef";

    /**
     * Conf name.
     * @var string
     */
    private $name;

    /**
     * Forcesuccess flag (true = forcesuccess on).
     * @var bool
     */
    private $forcesuccess;

    /**
     * Insignificance flag (true = insignificant).
     * @var bool
     */
    private $insignificant;

    /**
     * ConfRun constructor.
     * @param string $name
     * @param string $projectName
     * @param string $branchName
     * @param string $stateName
     * @param int $buildKey
     * @param string $result
     * @param bool $forcesuccess (true = forcesuccess on)
     * @param bool $insignificant (true = insignificant)
     * @param int $timestamp
     * @param int $duration
     */
    public function __construct($name, $projectName, $branchName, $stateName, $buildKey, $result, $forcesuccess, $insignificant, $timestamp, $duration) {
        parent::__construct($projectName, $branchName, $stateName, $buildKey, $result, $timestamp, $duration);
        $this->name = $name;
        $this->forcesuccess = $forcesuccess;
        $this->insignificant = $insignificant;
    }

    /**
     * Get name of the conf.
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get forcesuccess flag.
     * @return bool (true = forcesuccess on)
     */
    public function getForcesuccess()
    {
        return $this->forcesuccess;
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
     * Get log file link.
     * @return string
     */
    public function getLogLink()
    {
        return Factory::getCiLogPath()
            . urlencode(parent::getFullProjectName())
            . '/build_' . parent::getBuildKeyString()
            . '/' . urlencode($this->name)
            . '/log.txt.gz';
    }

}

?>
