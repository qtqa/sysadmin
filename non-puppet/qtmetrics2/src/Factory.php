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
 * Factory class
 * @version   0.5
 * @since     01-07-2015
 * @author    Juha Sippola
 */

require_once 'Database.php';
require_once 'Project.php';
require_once 'ProjectRun.php';
require_once 'ConfRun.php';
require_once 'Testset.php';
require_once 'TestsetRun.php';

class Factory {

    /**
     * Testset lists
     */
    const LIST_FAILURES  = 1;
    const LIST_FLAKY     = 2;

    /**
     * Configuration settings as specified in the ini file.
     * @var array
     */
    private $ini;

    /**
     * Database instance.
     * @var Database
     */
    private $db;

    /**
     * Create Factory instance
     * @return Factory
     */
    private static function singleton()
    {
        static $instance = null;
        if (!$instance)
            $instance = new Factory();
        return $instance;
    }

    /**
     * Read configuration settings
     * @return array
     */
    public static function conf()
    {
        $instance = self::singleton();
        if (!$instance->ini)
            $instance->ini = parse_ini_file('qtmetrics.ini');
        return $instance->ini;
    }

    /**
     * Get database instance
     * @return Database
     */
    public static function db()
    {
        $instance = self::singleton();
        if (!$instance->db) {
            $instance->db = new Database;
        }
        return $instance->db;
    }

    /**
     * Manipulate configuration settings runtime (for unit testing purposes)
     * @param string $key
     * @param string $value
     */
    public static function setRuntimeConf($key, $value)
    {
        self::conf();
        self::singleton()->ini[$key] = $value;
    }

    /**
     * Check if the testset exists in the database
     * @param string $name
     * @return boolean
     */
    public static function checkTestset($name)
    {
        $dbEntries = self::db()->getTestsetProject($name);
        return (count($dbEntries) > 0) ? true : false;
    }

    /**
     * Get list of projects matching the filter string.
     * @param string $filter
     * @return array (string name)
     */
    public static function getProjectsFiltered($filter)
    {
        $result = Factory::db()->getProjectsFiltered($filter);
        return $result;
    }

    /**
     * Get list of testsets matching the filter string.
     * @param string $filter
     * @return array (string name)
     */
    public static function getTestsetsFiltered($filter)
    {
        $result = Factory::db()->getTestsetsFiltered($filter);
        return $result;
    }

    /**
     * Create Project object for that in database
     * @param string $project
     * @param string $runProject
     * @param string $runState
     * @return array Project object
     */
    public static function createProject($project, $runProject, $runState)
    {
        $obj = new Project($project);
        $obj->setStatus($runProject, $runState);
        return $obj;
    }

    /**
     * Create Testset objects for those in database (with either result or flaky counts)
     * List is limited by date (since) and length, failure result list and counts for specified builds only
     * @param int $listType
     * @param string $runProject
     * @param string $runState
     * @return array Testset objects
     */
    public static function createTestsets($listType, $runProject, $runState)
    {
        $objects = array();
        $ini = self::conf();
        // Failure result list (from specified builds only)
        if ($listType === self::LIST_FAILURES) {
            $days = intval($ini['top_failures_last_days']) - 1;
            $since = self::getSinceDate($days);
            $limit = intval($ini['top_failures_n']);
            $dbEntries = self::db()->getTestsetsResultCounts($runProject, $runState, $since, $limit);
            foreach($dbEntries as $entry) {
                $obj = new Testset($entry['name'], $entry['project']);
                $obj->setStatus($runProject, $runState);
                $obj->setTestsetResultCounts($entry['passed'], $entry['failed']);
                $objects[] = $obj;
            }
        }
        // Flaky list (all builds)
        if ($listType === self::LIST_FLAKY) {
            $days = intval($ini['flaky_testsets_last_days']) - 1;
            $since = self::getSinceDate($days);
            $limit = intval($ini['flaky_testsets_n']);
            $dbEntries = self::db()->getTestsetsFlakyCounts($since, $limit);
            foreach($dbEntries as $entry) {
                $obj = new Testset($entry['name'], $entry['project']);
                $obj->setTestsetFlakyCounts($entry['flaky'], $entry['total']);
                $objects[] = $obj;
            }
        }
        return $objects;
    }

    /**
     * Create Testset object for that in database
     * Counts are limited by date (since) and length, failure result counts for specified builds only
     * @param string $name
     * @param string $testsetProject
     * @param string $runProject
     * @param string $runState
     * @return array Testset object(s)
     */
    public static function createTestset($name, $testsetProject, $runProject, $runState)
    {
        $ini = self::conf();
        $obj = new Testset($name, $testsetProject);
        $obj->setStatus($runProject, $runState);
        // Failure result counts (from specified builds only)
        $days = intval($ini['top_failures_last_days']) - 1;
        $since = self::getSinceDate($days);
        $dbTestsetDetails = self::db()->getTestsetResultCounts($name, $runProject, $runState, $since);
        foreach($dbTestsetDetails as $detail) {
            if ($detail['project'] === $testsetProject)
                $obj->setTestsetResultCounts($detail['passed'], $detail['failed']);
        }
        // Flaky counts (all builds)
        $days = intval($ini['flaky_testsets_last_days']) - 1;
        $since = self::getSinceDate($days);
        $dbTestsetDetails = self::db()->getTestsetFlakyCounts($name, $since);
        foreach($dbTestsetDetails as $detail) {
            if ($detail['project'] === $testsetProject)
                $obj->setTestsetFlakyCounts($detail['flaky'], $detail['total']);
        }
        return $obj;
    }

/* NEW */
    /**
     * Create ConfRun objects for those in database
     * @param string $runProject
     * @param string $runState
     * @param string $targetOs
     * @return array ConfRun objects
     */
    public static function createConfRuns($runProject, $runState, $targetOs, $conf)
    {
        $objects = array();
        if (empty($targetOs) and empty($conf))
            $dbEntries = self::db()->getConfBuildsByBranch($runProject, $runState);
        else if (!empty($targetOs))
            $dbEntries = self::db()->getConfOsBuildsByBranch($runProject, $runState, $targetOs);
        else
            $dbEntries = self::db()->getConfBuildByBranch($runProject, $runState, $conf);
        foreach($dbEntries as $entry) {
            $obj = new ConfRun(
                $entry['conf'],
                $runProject,
                $entry['branch'],
                $runState,
                $entry['buildKey'],
                $entry['result'],
                $entry['forcesuccess'],
                $entry['insignificant'],
                $entry['timestamp'],
                $entry['duration']
            );
            $objects[] = $obj;
        }
        return $objects;
    }

    /**
     * Create TestsetRun objects for those in database
     * @param string $testset
     * @param string $testsetProject
     * @param string $runProject
     * @param string $runState
     * @return array TestsetRun objects
     */
    public static function createTestsetRuns($testset, $testsetProject, $runProject, $runState)
    {
        $objects = array();
        $dbEntries = self::db()->getTestsetResultsByBranchConf($testset, $testsetProject, $runProject, $runState);
        foreach($dbEntries as $entry) {
            $obj = new TestsetRun(
                $testset,
                $testsetProject,
                $runProject,
                $entry['branch'],
                $runState,
                $entry['buildKey'],
                $entry['conf'],
                $entry['run'],
                TestsetRun::stripResult($entry['result']),
                TestsetRun::isInsignificant($entry['result']),
                $entry['timestamp'],
                $entry['duration']
            );
            $objects[] = $obj;
        }
        return $objects;
    }

    /**
     * Get the date that was n days before the last database refresh date.
     * @param int $days
     * @return string (date in unix date format)
     */
    public static function getSinceDate($days)
    {
        $last = strtotime(self::db()->getDbRefreshed());
        $since = date('Y-m-d', strtotime('-' . $days . ' day', $last));
        return $since;
    }

}

?>
