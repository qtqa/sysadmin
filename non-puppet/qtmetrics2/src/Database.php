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
 * Database class
 * @version   0.2
 * @since     11-06-2015
 * @author    Juha Sippola
 */

class Database {

    /**
     * Database instance
     * @var PDO
     */
    private $db;

    /**
     * Database constructor
     */
    public function __construct()
    {
        $ini = Factory::conf();
        $this->db = new PDO(
            $ini['dsn'],
            $ini['username'],
            $ini['password']
        );
    }

    /**
     * Get list of projects
     * @return array (string name)
     */
    public function getProjects()
    {
        $result = array();
        $query = $this->db->prepare("SELECT name FROM project ORDER BY name");
        $query->execute(array());
        while($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $result[] = array('name' => $row['name']);
        }
        return $result;
    }

    /**
     * Get list of branches
     * @return array (string name)
     */
    public function getBranches()
    {
        $result = array();
        $query = $this->db->prepare("SELECT name FROM branch ORDER BY name");
        $query->execute(array());
        while($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $result[] = array('name' => $row['name']);
        }
        return $result;
    }

    /**
     * Get list of states
     * @return array (string name)
     */
    public function getStates()
    {
        $result = array();
        $query = $this->db->prepare("SELECT name FROM state ORDER BY name");
        $query->execute(array());
        while($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $result[] = array('name' => $row['name']);
        }
        return $result;
    }

    /**
     * Get list of testsets matching the filter string.
     * @param string $filter
     * @return array (string name)
     */
    public function getTestsetsFiltered($filter)
    {
        $result = array();
        $query = $this->db->prepare("
            SELECT DISTINCT name
            FROM testset
            WHERE name LIKE ?
            ORDER BY name;
        ");
        $query->execute(array(
            '%' . $filter . '%'
        ));
        while($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $result[] = array('name' => $row['name']);
        }
        return $result;
    }

    /**
     * Get the project(s) of testset
     * If several testsets found with the same name in different projects, all are listed
     * @param string $testset
     * @return array (string name, string project)
     */
    public function getTestsetProject($testset)
    {
        $result = array();
        $query = $this->db->prepare("
            SELECT testset.name AS testset, project.name AS project
            FROM testset
                INNER JOIN project ON testset.project_id = project.id
            WHERE testset.name = ?
            ORDER BY project.name;
        ");
        $query->execute(array(
            $testset
        ));
        while($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $result[] = array(
                'name' => $row['testset'],
                'project' => $row['project']
            );
        }
        return $result;
    }

    /**
     * Get list of target platform os's and versions
     * @return array (string os, string os_version)
     */
    public function getTargetPlatforms()
    {
        $result = array();
        $query = $this->db->prepare("
            SELECT platform.os, platform.os_version
            FROM conf
                INNER JOIN platform ON conf.target_id = platform.id
            GROUP BY os_version;
        ");
        $query->execute(array());
        while($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $result[] = array(
                'os' => $row['os'],
                'os_version' => $row['os_version']
            );
        }
        return $result;
    }

    /**
     * Get the latest build number for given project, branch and state
     * @param string $project
     * @param string $branch
     * @param string $state
     * @return int
     */
    public function getLatestProjectBranchBuildNumber($project, $branch, $state)
    {
        $result = array();
        $query = $this->db->prepare("
            SELECT max(build_number) AS latest_build
            FROM project_run
            WHERE
                project_id = (SELECT id FROM project WHERE name = ?) AND
                branch_id = (SELECT id FROM branch WHERE name = ?) AND
                state_id = (SELECT id FROM state WHERE name = ?)
        ");
        $query->execute(array(
            $project,
            $branch,
            $state
        ));
        while($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $result= $row['latest_build'];
        }
        return $result;
    }

    /**
     * Get the latest build numbers by branch for given project and state
     * @param string $project
     * @param string $state
     * @return array (string name, int number)
     */
    public function getLatestProjectBranchBuildNumbers($project, $state)
    {
        $result = array();
        $query = $this->db->prepare("
            SELECT branch.name, max(build_number) AS latest_build
            FROM project_run
                INNER JOIN branch ON branch_id = branch.id
            WHERE
                project_id = (SELECT id FROM project WHERE name = ?) AND
                state_id = (SELECT id FROM state WHERE name = ?)
            GROUP BY branch_id
        ");
        $query->execute(array(
            $project,
            $state
        ));
        while($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $result[] = array(
                'name' => $row['name'],
                'number' => $row['latest_build']
            );
        }
        return $result;
    }

    /**
     * Get the latest build result by branch for given project and state
     * @param string $project
     * @param string $state
     * @return array (string name, string result)
     */
    public function getLatestProjectBranchBuildResults($project, $state)
    {
        $result = array();
        $builds = self::getLatestProjectBranchBuildNumbers($project, $state);
        foreach ($builds as $build) {
            $query = $this->db->prepare("
                SELECT branch.name, project_run.result
                FROM project_run
                    INNER JOIN branch ON branch_id = branch.id
                WHERE
                    project_id = (SELECT id FROM project WHERE name = ?) AND
                    state_id = (SELECT id FROM state WHERE name = ?) AND
                    branch_id = (SELECT id FROM branch WHERE name = ?) AND
                    build_number = ?;
            ");
            $query->execute(array(
                $project,
                $state,
                $build['name'],
                $build['number']
            ));
            while($row = $query->fetch(PDO::FETCH_ASSOC)) {
                $result[] = array(
                    'name' => $row['name'],
                    'result' => $row['result']
                );
            }
        }
        return $result;
    }

    /**
     * Get the latest build result by configuration and branch for given project and state
     * @param string $testset
     * @param string $testsetProject
     * @param string $runProject
     * @param string $runState
     * @return array (string name, string branch, string result)
     */
    public function getLatestTestsetConfBuildResults($testset, $testsetProject, $runProject, $runState)
    {
        $result = array();
        $builds = self::getLatestProjectBranchBuildNumbers($runProject, $runState);
        foreach ($builds as $build) {
            $query = $this->db->prepare("
                SELECT conf.name AS conf, branch.name AS branch, testset_run.result
                FROM testset_run
                    INNER JOIN conf_run ON testset_run.conf_run_id = conf_run.id
                    INNER JOIN conf ON conf_run.conf_id = conf.id
                    INNER JOIN project_run ON conf_run.project_run_id = project_run.id
                    INNER JOIN branch ON project_run.branch_id = branch.id
                WHERE
                    testset_run.testset_id = (SELECT testset.id FROM testset INNER JOIN project ON testset.project_id = project.id WHERE testset.name = ? AND project.name = ?) AND
                    project_run.project_id = (SELECT id FROM project WHERE name = ?) AND
                    project_run.state_id = (SELECT id FROM state WHERE name = ?) AND
                    project_run.branch_id = (SELECT id from branch WHERE name = ?) AND
                    project_run.build_number = ?;
            ");
            $query->execute(array(
                $testset,
                $testsetProject,
                $runProject,
                $runState,
                $build['name'],
                $build['number']
            ));
            while($row = $query->fetch(PDO::FETCH_ASSOC)) {
                $result[] = array(
                    'name' => $row['conf'],
                    'branch' => $row['branch'],
                    'result' => $row['result']
                );
            }
        }
        return $result;
    }

    /**
     * Get counts of all passed and failed runs by testset in specified builds since specified date (list length limited)
     * Only the testsets that have failed since the specified date are listed
     * @param string $runProject
     * @param string $runState
     * @param string $date
     * @param int $limit
     * @return array (string name, string project, int passed, int failed)
     */
    public function getTestsetsResultCounts($runProject, $runState, $date, $limit)
    {
        $result = array();
        $query = $this->db->prepare("
            SELECT
                testset.name AS testset,
                project.name AS project,
                COUNT(CASE WHEN testset_run.result LIKE '%passed' THEN testset_run.result END) AS passed,
                COUNT(CASE WHEN testset_run.result LIKE '%failed' THEN testset_run.result END) AS failed
            FROM testset_run
                INNER JOIN testset ON testset_run.testset_id = testset.id
                INNER JOIN project ON testset.project_id = project.id
                INNER JOIN conf_run ON testset_run.conf_run_id = conf_run.id
                INNER JOIN project_run ON conf_run.project_run_id = project_run.id
                INNER JOIN state ON project_run.state_id = state.id
            WHERE
                project_run.project_id = (SELECT id FROM project WHERE name = ?) AND
                project_run.state_id = (SELECT id FROM state WHERE name = ?) AND
                project_run.timestamp >= ?
            GROUP BY testset.name
            ORDER BY failed DESC, testset.name ASC
            LIMIT ?;
        ");
        $query->bindParam(1, $runProject);
        $query->bindParam(2, $runState);
        $query->bindParam(3, $date);
        $query->bindParam(4, $limit, PDO::PARAM_INT);       // int data type must be separately set
        $query->execute();
        while($row = $query->fetch(PDO::FETCH_ASSOC)) {
            if ($row['failed'] > 0) {                       // return only those where failures identified
                $result[] = array(
                    'name' => $row['testset'],
                    'project' => $row['project'],
                    'passed' => $row['passed'],
                    'failed' => $row['failed']
                );
            }
        }
        return $result;
    }

    /**
     * Get counts of all passed and failed runs for a testset in specified builds since specified date
     * If several testsets found with the same name in different projects, all are listed
     * @param string $testset
     * @param string $runProject
     * @param string $runState
     * @param string $date
     * @return array (string name, string project, int passed, int failed)
     */
    public function getTestsetResultCounts($testset, $runProject, $runState, $date)
    {
        $result = array();
        $query = $this->db->prepare("
            SELECT
                testset.name AS testset,
                project.name AS project,
                COUNT(CASE WHEN testset_run.result LIKE '%passed' THEN testset_run.result END) AS passed,
                COUNT(CASE WHEN testset_run.result LIKE '%failed' THEN testset_run.result END) AS failed
            FROM testset_run
                INNER JOIN testset ON testset_run.testset_id = testset.id
                INNER JOIN project ON testset.project_id = project.id
                INNER JOIN conf_run ON testset_run.conf_run_id = conf_run.id
                INNER JOIN project_run ON conf_run.project_run_id = project_run.id
                INNER JOIN state ON project_run.state_id = state.id
            WHERE
                testset.name = ? AND
                project_run.project_id = (SELECT id FROM project WHERE name = ?) AND
                project_run.state_id = (SELECT id FROM state WHERE name = ?) AND
                project_run.timestamp >= ?
            GROUP BY testset.name
            ORDER BY project.name;
        ");
        $query->execute(array(
            $testset,
            $runProject,
            $runState,
            $date
        ));
        while($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $result[] = array(
                'name' => $row['testset'],
                'project' => $row['project'],
                'passed' => $row['passed'],
                'failed' => $row['failed']
            );
        }
        return $result;
    }

    /**
     * Get counts of flaky runs by testset since specified date (list length limited)
     * Only the testsets that are flaky since the specified date are listed
     * Scope is all builds (state and any)
     * @param string $date
     * @param int $limit
     * @return array (string name, string project, int flaky, int total)
     */
    public function getTestsetsFlakyCounts($date, $limit)
    {
        $result = array();
        $query = $this->db->prepare('
            SELECT
                testset.name AS testset,
                project.name AS project,
                COUNT(CASE WHEN testset_run.run > 1 AND (testset_run.result = "passed" OR testset_run.result = "ipassed") THEN testset_run.run END) AS flaky,
                COUNT(testset_run.id) AS total
            FROM testset_run
                INNER JOIN testset ON testset_run.testset_id = testset.id
                INNER JOIN project ON testset.project_id = project.id
                INNER JOIN conf_run ON testset_run.conf_run_id = conf_run.id
                INNER JOIN project_run ON conf_run.project_run_id = project_run.id
            WHERE project_run.timestamp >= ?
            GROUP BY testset.name
            ORDER BY flaky DESC, testset.name ASC
            LIMIT ?;
        ');
        $query->bindParam(1, $date);
        $query->bindParam(2, $limit, PDO::PARAM_INT);       // int data type must be separately set
        $query->execute();
        while($row = $query->fetch(PDO::FETCH_ASSOC)) {
            if ($row['flaky'] > 0) {                        // return only those where flaky identified
                $result[] = array(
                    'name' => $row['testset'],
                    'project' => $row['project'],
                    'flaky' => $row['flaky'],
                    'total' => $row['total']
                );
            }
        }
        return $result;
    }

    /**
     * Get counts of flaky runs for a testset since specified date
     * Scope is all builds (state and any)
     * @param string $testset
     * @param string $date
     * @return array (string name, string project, int flaky, int total)
     */
    public function getTestsetFlakyCounts($testset, $date)
    {
        $result = array();
        $query = $this->db->prepare('
            SELECT
                testset.name AS testset,
                project.name AS project,
                COUNT(CASE WHEN testset_run.run > 1 AND (testset_run.result = "passed" OR testset_run.result = "ipassed") THEN testset_run.run END) AS flaky,
                COUNT(testset_run.id) AS total
            FROM testset_run
                INNER JOIN testset ON testset_run.testset_id = testset.id
                INNER JOIN project ON testset.project_id = project.id
                INNER JOIN conf_run ON testset_run.conf_run_id = conf_run.id
                INNER JOIN project_run ON conf_run.project_run_id = project_run.id
            WHERE testset.name = ? AND project_run.timestamp >= ?
            GROUP BY testset.name
            ORDER BY project.name;
        ');
        $query->execute(array(
            $testset,
            $date
        ));
        while($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $result[] = array(
                'name' => $row['testset'],
                'project' => $row['project'],
                'flaky' => $row['flaky'],
                'total' => $row['total']
            );
        }
        return $result;
    }

    /**
     * Get the timestamp when database last refreshed
     * @return string (timestamp)
     */
    public function getDbRefreshed()
    {
        $query = $this->db->prepare("SELECT refreshed FROM db_status ORDER BY refreshed DESC LIMIT 1");
        $query->execute(array());
        $row = $query->fetch(PDO::FETCH_ASSOC);
        $timestamp = $row['refreshed'];
        return $timestamp;
    }

}

?>
