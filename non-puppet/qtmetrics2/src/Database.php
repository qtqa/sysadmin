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
 * @since     17-09-2015
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
     * @return array (string name, bool archived)
     */
    public function getBranches()
    {
        $result = array();
        $query = $this->db->prepare("SELECT name, archived FROM branch ORDER BY name");
        $query->execute(array());
        while($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $result[] = array(
                'name' => $row['name'],
                'archived' => $row['archived']
            );
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
     * Get list of projects matching the filter string.
     * @param string $filter
     * @return array (string name)
     */
    public function getProjectsFiltered($filter)
    {
        $result = array();
        $query = $this->db->prepare("
            SELECT name
            FROM project
            WHERE name LIKE ?
            ORDER BY name;
        ");
        $query->execute(array(
            '%' . $filter . '%'
        ));
        while($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $result[] = array(
                'name' => $row['name']
            );
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
            SELECT testset.name AS testset, project.name AS project
            FROM testset
                INNER JOIN project ON testset.project_id = project.id
            WHERE testset.name LIKE ?
            ORDER BY testset.name;
        ");
        $query->execute(array(
            '%' . $filter . '%'
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
     * Get list of target platform os's
     * @return array (string os)
     */
    public function getTargetPlatformOs()
    {
        $result = array();
        $query = $this->db->prepare("
            SELECT DISTINCT platform.os
            FROM conf
                INNER JOIN platform ON conf.target_id = platform.id
            ORDER BY platform.os;
        ");
        $query->execute(array());
        while($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $result[] = array(
                'os' => $row['os']
            );
        }
        return $result;
    }

    /**
     * Get the latest build key for given project, branch and state
     * @param string $runProject
     * @param string $runBranch
     * @param string $runState
     * @return string
     */
    public function getLatestProjectBranchBuildKey($runProject, $runBranch, $runState)
    {
        $result = array();
        $query = $this->db->prepare("
            SELECT build_key AS latest_build
            FROM project_run
                INNER JOIN branch ON project_run.branch_id = branch.id
            WHERE
                project_id = (SELECT id FROM project WHERE name = ?) AND
                branch_id = (SELECT id FROM branch WHERE name = ?) AND
                state_id = (SELECT id FROM state WHERE name = ?) AND
                branch.archived = 0
            ORDER BY timestamp DESC
            LIMIT 1
        ");
        $query->execute(array(
            $runProject,
            $runBranch,
            $runState
        ));
        while($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $result= $row['latest_build'];
        }
        return $result;
    }

    /**
     * Get the latest build keys by branch for given project and state
     * @param string $runProject
     * @param string $runState
     * @return array (string name, string key)
     */
    public function getLatestProjectBranchBuildKeys($runProject, $runState)
    {
        $result = array();

        $branches = self::getBranches();
        foreach ($branches as $branch) {
            $key = self::getLatestProjectBranchBuildKey($runProject, $branch['name'], $runState);
            if ($key) {
                $result[] = array(
                    'name' => $branch['name'],
                    'key' => $key
                );
            }
        }
        return $result;
    }

    /**
     * Get the latest build result by branch for given project and state
     * @param string $runProject
     * @param string $runState
     * @return array (string name, string result, string buildKey, string timestamp, string duration)
     */
    public function getLatestProjectBranchBuildResults($runProject, $runState)
    {
        $result = array();
        $builds = self::getLatestProjectBranchBuildKeys($runProject, $runState);
        foreach ($builds as $build) {
            $query = $this->db->prepare("
                SELECT
                    branch.name,
                    project_run.result,
                    project_run.build_key,
                    project_run.timestamp,
                    project_run.duration
                FROM project_run
                    INNER JOIN branch ON project_run.branch_id = branch.id
                WHERE
                    project_id = (SELECT id FROM project WHERE name = ?) AND
                    state_id = (SELECT id FROM state WHERE name = ?) AND
                    branch_id = (SELECT id FROM branch WHERE name = ?) AND
                    build_key = ? AND
                    branch.archived = 0;
            ");
            $query->execute(array(
                $runProject,
                $runState,
                $build['name'],
                $build['key']
            ));
            while($row = $query->fetch(PDO::FETCH_ASSOC)) {
                $result[] = array(
                    'name' => $row['name'],
                    'result' => $row['result'],
                    'buildKey' => $row['build_key'],
                    'timestamp' => $row['timestamp'],
                    'duration' => $row['duration']
                );
            }
        }
        return $result;
    }

    /**
     * Get the latest configuration build result by branch for given project and state
     * @param string $conf
     * @param string $runProject
     * @param string $runState
     * @return array (string name, string result, string buildKey, string timestamp, string duration)
     */
    public function getLatestConfBranchBuildResults($conf, $runProject, $runState)
    {
        $result = array();
        $builds = self::getLatestProjectBranchBuildKeys($runProject, $runState);
        foreach ($builds as $build) {
            $query = $this->db->prepare("
                SELECT
                    branch.name,
                    conf_run.result,
                    project_run.build_key,
                    conf_run.forcesuccess,
                    conf_run.insignificant,
                    conf_run.timestamp,
                    conf_run.duration
                FROM conf_run
                    INNER JOIN conf ON conf_run.conf_id = conf.id
                    INNER JOIN project_run ON conf_run.project_run_id = project_run.id
                    INNER JOIN branch ON project_run.branch_id = branch.id
                WHERE
                    conf.name = ? AND
                    project_run.project_id = (SELECT id FROM project WHERE name = ?) AND
                    project_run.state_id = (SELECT id FROM state WHERE name = ?) AND
                    project_run.branch_id = (SELECT id from branch WHERE name = ?) AND
                    project_run.build_key = ? AND
                    branch.archived = 0;
            ");
            $query->execute(array(
                $conf,
                $runProject,
                $runState,
                $build['name'],
                $build['key']
            ));
            while($row = $query->fetch(PDO::FETCH_ASSOC)) {
                $result[] = array(
                    'name' => $row['name'],
                    'result' => $row['result'],
                    'buildKey' => $row['build_key'],
                    'forcesuccess' => $row['forcesuccess'],
                    'insignificant' => $row['insignificant'],
                    'timestamp' => $row['timestamp'],
                    'duration' => $row['duration']
                );
            }
        }
        return $result;
    }

    /**
     * Get the latest testset result by branch for given project and state
     * @param string $runProject
     * @param string $runState
     * @return array (string project, string branch, string buildKey, string timestamp, int passed, int failed)
     */
    public function getLatestProjectBranchTestsetResults($runProject, $runState)
    {
        $result = array();
        $builds = self::getLatestProjectBranchBuildKeys($runProject, $runState);
        foreach ($builds as $build) {
            $query = $this->db->prepare("
                SELECT
                    project.name AS project,
                    branch.name AS branch,
                    project_run.build_key,
                    project_run.timestamp,
                    COUNT(CASE WHEN testset_run.result LIKE '%passed' THEN testset_run.result END) AS passed,
                    COUNT(CASE WHEN testset_run.result LIKE '%failed' THEN testset_run.result END) AS failed
                FROM testset_run
                    INNER JOIN testset ON testset_run.testset_id = testset.id
                    INNER JOIN project ON testset.project_id = project.id
                    INNER JOIN conf_run ON testset_run.conf_run_id = conf_run.id
                    INNER JOIN conf ON conf_run.conf_id = conf.id
                    INNER JOIN project_run ON conf_run.project_run_id = project_run.id
                    INNER JOIN branch ON project_run.branch_id = branch.id
                WHERE
                    project_run.project_id = (SELECT id FROM project WHERE name = ?) AND
                    project_run.state_id = (SELECT id FROM state WHERE name = ?) AND
                    project_run.branch_id = (SELECT id from branch WHERE name = ?) AND
                    project_run.build_key = ? AND
                    branch.archived = 0
                GROUP BY project.name;
            ");
            $query->execute(array(
                $runProject,
                $runState,
                $build['name'],
                $build['key']
            ));
            while($row = $query->fetch(PDO::FETCH_ASSOC)) {
                $result[] = array(
                    'project' => $row['project'],
                    'branch' => $row['branch'],
                    'buildKey' => $row['build_key'],
                    'timestamp' => $row['timestamp'],
                    'passed' => $row['passed'],
                    'failed' => $row['failed']
                );
            }
        }
        return $result;
    }

    /**
     * Get the latest testset result by branch for given project and state, for selected testset project.
     * Similar to getLatestProjectBranchTestsetResults but listing only the selected testset project.
     * @param string $testsetProject
     * @param string $runProject
     * @param string $runState
     * @return array (string project, string branch, string buildKey, string timestamp, int passed, int failed)
     */
    public function getLatestTestsetProjectBranchTestsetResults($testsetProject, $runProject, $runState)
    {
        $result = array();
        $builds = self::getLatestProjectBranchBuildKeys($runProject, $runState);
        foreach ($builds as $build) {
            $query = $this->db->prepare("
                SELECT
                    project.name AS project,
                    branch.name AS branch,
                    project_run.build_key,
                    project_run.timestamp,
                    COUNT(CASE WHEN testset_run.result LIKE '%passed' THEN testset_run.result END) AS passed,
                    COUNT(CASE WHEN testset_run.result LIKE '%failed' THEN testset_run.result END) AS failed
                FROM testset_run
                    INNER JOIN testset ON testset_run.testset_id = testset.id
                    INNER JOIN project ON testset.project_id = project.id
                    INNER JOIN conf_run ON testset_run.conf_run_id = conf_run.id
                    INNER JOIN conf ON conf_run.conf_id = conf.id
                    INNER JOIN project_run ON conf_run.project_run_id = project_run.id
                    INNER JOIN branch ON project_run.branch_id = branch.id
                WHERE
                    project.name = ? AND
                    project_run.project_id = (SELECT id FROM project WHERE name = ?) AND
                    project_run.state_id = (SELECT id FROM state WHERE name = ?) AND
                    project_run.branch_id = (SELECT id from branch WHERE name = ?) AND
                    project_run.build_key = ? AND
                    branch.archived = 0
                GROUP BY project.name;
            ");
            $query->execute(array(
                $testsetProject,
                $runProject,
                $runState,
                $build['name'],
                $build['key']
            ));
            while($row = $query->fetch(PDO::FETCH_ASSOC)) {
                $result[] = array(
                    'project' => $row['project'],
                    'branch' => $row['branch'],
                    'buildKey' => $row['build_key'],
                    'timestamp' => $row['timestamp'],
                    'passed' => $row['passed'],
                    'failed' => $row['failed']
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
        $builds = self::getLatestProjectBranchBuildKeys($runProject, $runState);
        foreach ($builds as $build) {
            $query = $this->db->prepare("
                SELECT
                    conf.name AS conf,
                    branch.name AS branch,
                    testset_run.result
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
                    project_run.build_key = ? AND
                    branch.archived = 0;
            ");
            $query->execute(array(
                $testset,
                $testsetProject,
                $runProject,
                $runState,
                $build['name'],
                $build['key']
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
                INNER JOIN branch ON project_run.branch_id = branch.id
                INNER JOIN state ON project_run.state_id = state.id
            WHERE
                project_run.project_id = (SELECT id FROM project WHERE name = ?) AND
                project_run.state_id = (SELECT id FROM state WHERE name = ?) AND
                project_run.timestamp >= ? AND
                branch.archived = 0
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
     * @param string $testset
     * @param string $testsetProject
     * @param string $runProject
     * @param string $runState
     * @param string $date
     * @return array (string name, string project, int passed, int failed)
     */
    public function getTestsetResultCounts($testset, $testsetProject, $runProject, $runState, $date)
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
                INNER JOIN branch ON project_run.branch_id = branch.id
                INNER JOIN state ON project_run.state_id = state.id
            WHERE
                project.name = ? AND
                testset.name = ? AND
                project_run.project_id = (SELECT id FROM project WHERE name = ?) AND
                project_run.state_id = (SELECT id FROM state WHERE name = ?) AND
                project_run.timestamp >= ? AND
                branch.archived = 0
            GROUP BY testset.name
            ORDER BY project.name;
        ");
        $query->execute(array(
            $testsetProject,
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
        // Get all flaky test runs
        $query = $this->db->prepare("
            SELECT
                testset.name AS testset,
                project.name AS project
            FROM testset_run
                INNER JOIN testset ON testset_run.testset_id = testset.id
                INNER JOIN project ON testset.project_id = project.id
                INNER JOIN conf_run ON testset_run.conf_run_id = conf_run.id
                INNER JOIN project_run ON conf_run.project_run_id = project_run.id
                INNER JOIN branch ON project_run.branch_id = branch.id
            WHERE
                project_run.timestamp >= ? AND
                testset_run.run > 1 AND
                testset_run.result LIKE '%passed' AND
                branch.archived = 0
            ORDER BY project.name, testset.name;
        ");
        $query->execute(array(
            $date
        ));
        // Calculate flaky count per testset (calculated here instead of in the query above for performance reasons)
        $testset = '';
        $testsets = array();
        $projects = array();
        $counts = array();
        while($row = $query->fetch(PDO::FETCH_ASSOC)) {
            if ($testset === '') {                                                  // Initialize
                $key = 0;
                $flaky = 0;
                $testset = $row['testset'];
                $project = $row['project'];
            }
            if ($row['testset'] !== $testset OR $row['project'] !== $project) {    // New testset
                $key++;
                $flaky = 0;
                $testset = $row['testset'];
                $project = $row['project'];
            }
            $flaky++;
            $testsets[$key] = $row['testset'];
            $projects[$key] = $row['project'];
            $counts[$key] = $flaky;
        }
        // List top n flaky testsets
        arsort($counts);
        $i = 0;
        foreach ($counts as $key => $value) {
            $data = self::getTestsetFlakyCounts($testsets[$key], $projects[$key], $date);
            foreach($data as $row) {
                $total = $row['total'];
            }
            $result[] = array(
                'name' => $testsets[$key],
                'project' => $projects[$key],
                'flaky' => $value,
                'total' => $total
            );
            $i++;
            if ($i >= $limit)
                break;
        }
        return $result;
    }

    /**
     * Get counts of flaky runs for a testset since specified date
     * Scope is all builds (state and any)
     * @param string $testset
     * @param string $testsetProject
     * @param string $date
     * @return array (string name, string project, int flaky, int total)
     */
    public function getTestsetFlakyCounts($testset, $testsetProject, $date)
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
                INNER JOIN branch ON project_run.branch_id = branch.id
            WHERE
                project.name = ? AND
                testset.name = ? AND
                project_run.timestamp >= ? AND
                branch.archived = 0
            GROUP BY testset.name
            ORDER BY project.name;
        ');
        $query->execute(array(
            $testsetProject,
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
     * Get project run data by branch
     * @param string $runProject
     * @param string $runState
     * @return array (string branch, string build_key, string timestamp)
     */
    public function getProjectBuildsByBranch($runProject, $runState)
    {
        $result = array();
        $query = $this->db->prepare("
            SELECT
                branch.name AS branch,
                project_run.build_key,
                project_run.timestamp
            FROM project_run
                INNER JOIN branch ON project_run.branch_id = branch.id
            WHERE
                project_run.project_id = (SELECT id FROM project WHERE name = ?) AND
                project_run.state_id = (SELECT id FROM state WHERE name = ?) AND
                branch.archived = 0
            ORDER BY branch.name, project_run.timestamp DESC;
        ");
        $query->execute(array(
            $runProject,
            $runState
        ));
        while($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $result[] = array(
                'branch' => $row['branch'],
                'buildKey' => $row['build_key'],
                'timestamp' => $row['timestamp']
            );
        }
        return $result;
    }

    /**
     * Get conf run data by branch
     * @param string $runProject
     * @param string $runState
     * @return array (string branch, string conf, string build_key, bool forcesuccess, bool insignificant, string result, string timestamp, string duration)
     */
    public function getConfBuildsByBranch($runProject, $runState)
    {
        $result = array();
        $query = $this->db->prepare("
            SELECT
                branch.name AS branch,
                conf.name AS conf,
                project_run.build_key,
                conf_run.forcesuccess,
                conf_run.insignificant,
                conf_run.result,
                conf_run.timestamp,
                conf_run.duration
            FROM conf_run
                INNER JOIN conf ON conf_run.conf_id = conf.id
                INNER JOIN project_run ON conf_run.project_run_id = project_run.id
                INNER JOIN branch ON project_run.branch_id = branch.id
            WHERE
                project_run.project_id = (SELECT id FROM project WHERE name = ?) AND
                project_run.state_id = (SELECT id FROM state WHERE name = ?) AND
                branch.archived = 0
            ORDER BY branch.name, conf, project_run.timestamp DESC;
        ");
        $query->execute(array(
            $runProject,
            $runState
        ));
        while($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $result[] = array(
                'branch' => $row['branch'],
                'conf' => $row['conf'],
                'buildKey' => $row['build_key'],
                'forcesuccess' => $row['forcesuccess'],
                'insignificant' => $row['insignificant'],
                'result' => $row['result'],
                'timestamp' => $row['timestamp'],
                'duration' => $row['duration']
            );
        }
        return $result;
    }

    /**
     * Get conf run data for selected target os by branch
     * @param string $runProject
     * @param string $runState
     * @param string $targetOs
     * @return array (string branch, string conf, string build_key, bool forcesuccess, bool insignificant, string result, string timestamp, string duration)
     */
    public function getConfOsBuildsByBranch($runProject, $runState, $targetOs)
    {
        $result = array();
        $query = $this->db->prepare("
            SELECT
                branch.name AS branch,
                conf.name AS conf,
                project_run.build_key,
                conf_run.forcesuccess,
                conf_run.insignificant,
                conf_run.result,
                conf_run.timestamp,
                conf_run.duration
            FROM conf_run
                INNER JOIN conf ON conf_run.conf_id = conf.id
                INNER JOIN project_run ON conf_run.project_run_id = project_run.id
                INNER JOIN branch ON project_run.branch_id = branch.id
            WHERE
                project_run.project_id = (SELECT id FROM project WHERE name = ?) AND
                project_run.state_id = (SELECT id FROM state WHERE name = ?) AND
                conf.target_id IN (SELECT id FROM platform WHERE os = ?) AND
                branch.archived = 0
            ORDER BY branch.name, conf, project_run.timestamp DESC;
        ");
        $query->execute(array(
            $runProject,
            $runState,
            $targetOs
        ));
        while($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $result[] = array(
                'branch' => $row['branch'],
                'conf' => $row['conf'],
                'buildKey' => $row['build_key'],
                'forcesuccess' => $row['forcesuccess'],
                'insignificant' => $row['insignificant'],
                'result' => $row['result'],
                'timestamp' => $row['timestamp'],
                'duration' => $row['duration']
            );
        }
        return $result;
    }

    /**
     * Get conf run data for selected conf by branch
     * @param string $runProject
     * @param string $runState
     * @param string $conf
     * @return array (string branch, string conf, string build_key, bool forcesuccess, bool insignificant, string result, string timestamp, string duration)
     */
    public function getConfBuildByBranch($runProject, $runState, $conf)
    {
        $result = array();
        $query = $this->db->prepare("
            SELECT
                branch.name AS branch,
                conf.name AS conf,
                project_run.build_key,
                conf_run.forcesuccess,
                conf_run.insignificant,
                conf_run.result,
                conf_run.timestamp,
                conf_run.duration
            FROM conf_run
                INNER JOIN conf ON conf_run.conf_id = conf.id
                INNER JOIN project_run ON conf_run.project_run_id = project_run.id
                INNER JOIN branch ON project_run.branch_id = branch.id
            WHERE
                project_run.project_id = (SELECT id FROM project WHERE name = ?) AND
                project_run.state_id = (SELECT id FROM state WHERE name = ?) AND
                conf.name = ? AND
                branch.archived = 0
            ORDER BY branch.name, conf, project_run.timestamp DESC;
        ");
        $query->execute(array(
            $runProject,
            $runState,
            $conf
        ));
        while($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $result[] = array(
                'branch' => $row['branch'],
                'conf' => $row['conf'],
                'buildKey' => $row['build_key'],
                'forcesuccess' => $row['forcesuccess'],
                'insignificant' => $row['insignificant'],
                'result' => $row['result'],
                'timestamp' => $row['timestamp'],
                'duration' => $row['duration']
            );
        }
        return $result;
    }

    /**
     * Get run results for a testset in specified builds by branch and configuration
     * @param string $testset
     * @param string $testsetProject
     * @param string $runProject
     * @param string $runState
     * @return array (string branch, string conf, string build_key, string result, string timestamp, string duration, int run)
     */
    public function getTestsetResultsByBranchConf($testset, $testsetProject, $runProject, $runState)
    {
        $result = array();
        $query = $this->db->prepare("
            SELECT
                branch.name AS branch,
                conf.name AS conf,
                project_run.build_key,
                testset_run.result,
                project_run.timestamp,
                testset_run.duration,
                testset_run.run
            FROM testset_run
                INNER JOIN testset ON testset_run.testset_id = testset.id
                INNER JOIN project ON testset.project_id = project.id
                INNER JOIN conf_run ON testset_run.conf_run_id = conf_run.id
                INNER JOIN conf ON conf_run.conf_id = conf.id
                INNER JOIN project_run ON conf_run.project_run_id = project_run.id
                INNER JOIN branch ON project_run.branch_id = branch.id
            WHERE
                testset.name = ? AND
                project.name = ? AND
                project_run.project_id = (SELECT id FROM project WHERE name = ?) AND
                project_run.state_id = (SELECT id FROM state WHERE name = ?) AND
                branch.archived = 0
            ORDER BY branch.name, conf.name, project_run.timestamp DESC;
        ");
        $query->execute(array(
            $testset,
            $testsetProject,
            $runProject,
            $runState
        ));
        while($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $result[] = array(
                'branch' => $row['branch'],
                'conf' => $row['conf'],
                'buildKey' => $row['build_key'],
                'result' => $row['result'],
                'timestamp' => $row['timestamp'],
                'duration' => $row['duration'],
                'run' => $row['run']
            );
        }
        return $result;
    }

    /**
     * Get result counts for a testset project in specified builds by branch and configuration
     * @param string $testsetProject
     * @param string $runProject
     * @param string $runState
     * @return array (string branch, string conf, string build_key, int passed, int ipassed, int failed, int ifailed)
     */
    public function getTestsetProjectResultsByBranchConf($testsetProject, $runProject, $runState)
    {
        $result = array();
        $query = $this->db->prepare("
            SELECT
                branch.name AS branch,
                conf.name AS conf,
                project_run.build_key,
                COUNT(CASE WHEN testset_run.result = 'passed' THEN testset_run.result END) AS passed,
                COUNT(CASE WHEN testset_run.result = 'ipassed' THEN testset_run.result END) AS ipassed,
                COUNT(CASE WHEN testset_run.result = 'failed' THEN testset_run.result END) AS failed,
                COUNT(CASE WHEN testset_run.result = 'ifailed' THEN testset_run.result END) AS ifailed
            FROM testset_run
                INNER JOIN testset ON testset_run.testset_id = testset.id
                INNER JOIN project ON testset.project_id = project.id
                INNER JOIN conf_run ON testset_run.conf_run_id = conf_run.id
                INNER JOIN conf ON conf_run.conf_id = conf.id
                INNER JOIN project_run ON conf_run.project_run_id = project_run.id
                INNER JOIN branch ON project_run.branch_id = branch.id
            WHERE
                project.name = ? AND
                project_run.project_id = (SELECT id FROM project WHERE name = ?) AND
                project_run.state_id = (SELECT id FROM state WHERE name = ?) AND
                branch.archived = 0
            GROUP BY branch.name, project_run.build_key, conf.name
            ORDER BY branch.name, conf.name, project_run.build_key DESC;
        ");
        $query->execute(array(
            $testsetProject,
            $runProject,
            $runState
        ));
        while($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $result[] = array(
                'branch' => $row['branch'],
                'conf' => $row['conf'],
                'buildKey' => $row['build_key'],
                'passed' => $row['passed'],
                'ipassed' => $row['ipassed'],
                'failed' => $row['failed'],
                'ifailed' => $row['ifailed']
            );
        }
        return $result;
    }

    /**
     * Get results for failed testsets in specified configuration builds by branch
     * Only the failures are listed
     * @param string $conf
     * @param string $runProject
     * @param string $runState
     * @return array (string branch, string build_key, string testset, string project, string result, string timestamp, string duration, int run)
     */
    public function getTestsetConfResultsByBranch($conf, $runProject, $runState)
    {
        $result = array();
        $query = $this->db->prepare("
            SELECT
                branch.name AS branch,
                project_run.build_key,
                testset.name AS testset,
                project.name AS project,
                testset_run.result,
                project_run.timestamp,
                testset_run.duration,
                testset_run.run
            FROM testset_run
                INNER JOIN testset ON testset_run.testset_id = testset.id
                INNER JOIN project ON testset.project_id = project.id
                INNER JOIN conf_run ON testset_run.conf_run_id = conf_run.id
                INNER JOIN conf ON conf_run.conf_id = conf.id
                INNER JOIN project_run ON conf_run.project_run_id = project_run.id
                INNER JOIN branch ON project_run.branch_id = branch.id
            WHERE
                testset_run.result LIKE '%failed' AND
                conf.name = ? AND
                project_run.project_id = (SELECT id FROM project WHERE name = ?) AND
                project_run.state_id = (SELECT id FROM state WHERE name = ?) AND
                branch.archived = 0
            ORDER BY branch.name, project.name, testset.name, project_run.build_key DESC;
        ");
        $query->execute(array(
            $conf,
            $runProject,
            $runState
        ));
        while($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $result[] = array(
                'branch' => $row['branch'],
                'buildKey' => $row['build_key'],
                'testset' => $row['testset'],
                'project' => $row['project'],
                'result' => $row['result'],
                'timestamp' => $row['timestamp'],
                'duration' => $row['duration'],
                'run' => $row['run']
            );
        }
        return $result;
    }

    /**
     * Get results for failed testsets in specified configuration builds and project by branch
     * Only the failures are listed
     * @param string $conf
     * @param string $testsetProject
     * @param string $runProject
     * @param string $runState
     * @return array (string branch, string build_key, string testset, string project, string result, string timestamp, string duration, int run)
     */
    public function getTestsetConfProjectResultsByBranch($conf, $testsetProject, $runProject, $runState)
    {
        $result = array();
        $query = $this->db->prepare("
            SELECT
                branch.name AS branch,
                project_run.build_key,
                testset.name AS testset,
                project.name AS project,
                testset_run.result,
                project_run.timestamp,
                testset_run.duration,
                testset_run.run
            FROM testset_run
                INNER JOIN testset ON testset_run.testset_id = testset.id
                INNER JOIN project ON testset.project_id = project.id
                INNER JOIN conf_run ON testset_run.conf_run_id = conf_run.id
                INNER JOIN conf ON conf_run.conf_id = conf.id
                INNER JOIN project_run ON conf_run.project_run_id = project_run.id
                INNER JOIN branch ON project_run.branch_id = branch.id
            WHERE
                testset_run.result LIKE '%failed' AND
                project.name = ? AND
                conf.name = ? AND
                project_run.project_id = (SELECT id FROM project WHERE name = ?) AND
                project_run.state_id = (SELECT id FROM state WHERE name = ?) AND
                branch.archived = 0
            ORDER BY branch.name, testset.name, project_run.build_key DESC;
        ");
        $query->execute(array(
            $testsetProject,
            $conf,
            $runProject,
            $runState
        ));
        while($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $result[] = array(
                'branch' => $row['branch'],
                'buildKey' => $row['build_key'],
                'testset' => $row['testset'],
                'project' => $row['project'],
                'result' => $row['result'],
                'timestamp' => $row['timestamp'],
                'duration' => $row['duration'],
                'run' => $row['run']
            );
        }
        return $result;
    }

    /**
     * Get results for failed and skipped testfunctions in specified configuration builds and project by branch
     * Only the fail/skip and xpass/xfail results are listed
     * @param string $testset
     * @param string $testsetProject
     * @param string $conf
     * @param string $runProject
     * @param string $runState
     * @return array (string branch, string build_key, string testfunction, string result, string timestamp, string duration)
     */
    public function getTestfunctionConfResultsByBranch($testset, $testsetProject, $conf, $runProject, $runState)
    {
        $result = array();
        $query = $this->db->prepare("
            SELECT
                branch.name AS branch,
                project_run.build_key,
                testfunction.name AS testfunction,
                testfunction_run.result,
                project_run.timestamp,
                testfunction_run.duration
            FROM testfunction_run
                INNER JOIN testfunction ON testfunction_run.testfunction_id = testfunction.id
                INNER JOIN testset_run ON testfunction_run.testset_run_id = testset_run.id
                INNER JOIN testset ON testset_run.testset_id = testset.id
                INNER JOIN project ON testset.project_id = project.id
                INNER JOIN conf_run ON testset_run.conf_run_id = conf_run.id
                INNER JOIN conf ON conf_run.conf_id = conf.id
                INNER JOIN project_run ON conf_run.project_run_id = project_run.id
                INNER JOIN branch ON project_run.branch_id = branch.id
            WHERE
                (testfunction_run.result LIKE '%fail' OR testfunction_run.result LIKE '%skip' OR testfunction_run.result LIKE '%x%') AND
                testset.name = ? AND
                project.name = ? AND
                conf.name = ? AND
                project_run.project_id = (SELECT id FROM project WHERE name = ?) AND
                project_run.state_id = (SELECT id FROM state WHERE name = ?) AND
                branch.archived = 0
            ORDER BY branch.name, testfunction.name, project_run.build_key DESC;
        ");
        $query->execute(array(
            $testset,
            $testsetProject,
            $conf,
            $runProject,
            $runState
        ));
        while($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $result[] = array(
                'branch' => $row['branch'],
                'buildKey' => $row['build_key'],
                'testfunction' => $row['testfunction'],
                'result' => $row['result'],
                'timestamp' => $row['timestamp'],
                'duration' => $row['duration']
            );
        }
        return $result;
    }

    /**
     * Get results for failed and skipped testrows in specified configuration builds and project by branch
     * Only the fail/skip and xpass/xfail results are listed
     * @param string $testfunction
     * @param string $testset
     * @param string $testsetProject
     * @param string $conf
     * @param string $runProject
     * @param string $runState
     * @return array (string branch, string build_key, string testrow, string result, string timestamp)
     */
    public function getTestrowConfResultsByBranch($testfunction, $testset, $testsetProject, $conf, $runProject, $runState)
    {
        $result = array();
        $query = $this->db->prepare("
            SELECT
                branch.name AS branch,
                project_run.build_key,
                testrow.name AS testrow,
                testrow_run.result,
                project_run.timestamp
            FROM testrow_run
                INNER JOIN testrow ON testrow_run.testrow_id = testrow.id
                INNER JOIN testfunction_run ON testrow_run.testfunction_run_id = testfunction_run.id
                INNER JOIN testfunction ON testfunction_run.testfunction_id = testfunction.id
                INNER JOIN testset_run ON testfunction_run.testset_run_id = testset_run.id
                INNER JOIN testset ON testset_run.testset_id = testset.id
                INNER JOIN project ON testset.project_id = project.id
                INNER JOIN conf_run ON testset_run.conf_run_id = conf_run.id
                INNER JOIN conf ON conf_run.conf_id = conf.id
                INNER JOIN project_run ON conf_run.project_run_id = project_run.id
                INNER JOIN branch ON project_run.branch_id = branch.id
            WHERE
                (testrow_run.result LIKE '%fail' OR testrow_run.result LIKE '%skip' OR testrow_run.result LIKE '%x%') AND
                testfunction.name = ? AND
                testset.name = ? AND
                project.name = ? AND
                conf.name = ? AND
                project_run.project_id = (SELECT id FROM project WHERE name = ?) AND
                project_run.state_id = (SELECT id FROM state WHERE name = ?) AND
                branch.archived = 0
            ORDER BY branch.name, testrow.name, project_run.build_key DESC;
        ");
        $query->execute(array(
            $testfunction,
            $testset,
            $testsetProject,
            $conf,
            $runProject,
            $runState
        ));
        while($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $result[] = array(
                'branch' => $row['branch'],
                'buildKey' => $row['build_key'],
                'testrow' => $row['testrow'],
                'result' => $row['result'],
                'timestamp' => $row['timestamp']
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
        $query = $this->db->prepare("
            SELECT refreshed
            FROM db_status
            ORDER BY refreshed DESC LIMIT 1");
        $query->execute(array());
        $row = $query->fetch(PDO::FETCH_ASSOC);
        $timestamp = $row['refreshed'];
        return $timestamp;
    }

    /**
     * Get the database refresh status
     * @return array (bool in_progress, int current, int total)
     */
    public function getDbRefreshStatus()
    {
        $result = array();
        $query = $this->db->prepare("
            SELECT refreshed, refresh_in_progress, logs_current, logs_total
            FROM db_status
            ORDER BY refreshed DESC LIMIT 1;
        ");
        $query->execute(array());
        $row = $query->fetch(PDO::FETCH_ASSOC);
        $result = array(
            'refreshed' => $row['refreshed'],
            'in_progress' => $row['refresh_in_progress'],
            'current' => $row['logs_current'],
            'total' => $row['logs_total']
        );
        return $result;
    }

}

?>
