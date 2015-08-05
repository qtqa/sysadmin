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
 * Configuration class
 * @version   0.1
 * @since     15-07-2015
 * @author    Juha Sippola
 */

class Conf {

    /**
     * Conf status (starting from ok case and most fatal the last i.e. latter overwrites earlier)
     */
    const STATUS_EMPTY   = 0;
    const STATUS_SUCCESS = 1;
    const STATUS_UNDEF   = 2;
    const STATUS_ABORTED = 3;
    const STATUS_FAILURE = 4;

    /**
     * Configuration name.
     * @var string
     */
    private $name;

    /**
     * Configuration status calculated from the latest branch build results (in state builds only).
     * @var int
     */
    private $status;

    /**
     * Configuration constructor.
     * Configuration indicates the status in its latest runs in state builds in all branches
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
        $this->status = ConfRun::RESULT_EMPTY;              // not initially set
    }

    /**
     * Get name of the configuration.
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get status of the configuration calculated from the latest branch build results.
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set status of the configuration calculated from the testset results in the latest branch builds (in specified builds only).
     * @param string $runProject
     * @param string $runState
     */
    public function setStatus($runProject, $runState)
    {
        $status = self::STATUS_EMPTY;
        $statusText = ConfRun::RESULT_EMPTY;
        $builds = Factory::db()->getLatestConfBranchBuildResults( $this->name, $runProject, $runState);
        foreach ($builds as $build) {
            if ($build['result'] == ConfRun::RESULT_SUCCESS AND $status <= self::STATUS_SUCCESS) {
                $status = self::STATUS_SUCCESS;
                $statusText = ConfRun::RESULT_SUCCESS;
            }
            if ($build['result'] == ConfRun::RESULT_FAILURE AND $status <= self::STATUS_FAILURE) {
                $status = self::STATUS_FAILURE;
                $statusText = ConfRun::RESULT_FAILURE;
            }
            if ($build['result'] == ConfRun::RESULT_ABORTED AND $status <= self::STATUS_ABORTED) {
                $status = self::STATUS_ABORTED;
                $statusText = ConfRun::RESULT_ABORTED;
            }
            if ($build['result'] == ConfRun::RESULT_UNDEF AND $status <= self::STATUS_UNDEF) {
                $status = self::STATUS_UNDEF;
                $statusText = ConfRun::RESULT_UNDEF;
            }
        }
        $this->status = $statusText;
        return;
    }

}

?>
