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
 * DatabaseAdmin class
 * @since     17-09-2015
 * @author    Juha Sippola
 */

class DatabaseAdmin {

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
            $ini['username_admin'],
            $ini['password_admin']
        );
    }

    /**
     * Get list of tables with statistics
     * @return array (string name, int rowCount)
     */
    public function getTablesStatistics()
    {
        $result = array();
        // Tables to check (listed manually because database may contain additional tables)
        $tables = array();
        $tables[] = 'branch';
        $tables[] = 'compiler';
        $tables[] = 'conf';
        $tables[] = 'conf_run';
        $tables[] = 'db_status';
        $tables[] = 'phase';
        $tables[] = 'phase_run';
        $tables[] = 'platform';
        $tables[] = 'project';
        $tables[] = 'project_run';
        $tables[] = 'state';
        $tables[] = 'testfunction';
        $tables[] = 'testfunction_run';
        $tables[] = 'testrow';
        $tables[] = 'testrow_run';
        $tables[] = 'testset';
        $tables[] = 'testset_run';
        // Row counts
        foreach ($tables as $table) {
            $query = $this->db->prepare("SELECT COUNT(*) AS rowCount FROM $table");
            $query->execute(array());
            while($row = $query->fetch(PDO::FETCH_ASSOC)) {
                $result[] = array(
                    'name' => $table,
                    'rowCount' => $row['rowCount']
                );
            }
        }
        // Sort
        $count = array();
        foreach ($result as $key => $row)
        {
            $count[$key] = $row['rowCount'];
        }
        array_multisort($count, SORT_DESC, $result);
        return $result;
    }

    /**
     * Get list of branches with statistics
     * @return array (string name, int runCount, timestamp latestRun)
     */
    public function getBranchesStatistics()
    {
        $result = array();
        $branches = Factory::db()->getBranches();
        foreach ($branches as $branch) {
            $query = $this->db->prepare("
                SELECT
                    COUNT(*) AS runCount,
                    MAX(timestamp) AS latestRun
                FROM project_run
                WHERE
                    branch_id = (SELECT id FROM branch WHERE name = ?);
            ");
            $query->execute(array(
                $branch['name']
            ));
            while($row = $query->fetch(PDO::FETCH_ASSOC)) {
                $result[] = array(
                    'name' => $branch['name'],
                    'archived' => $branch['archived'],
                    'runCount' => $row['runCount'],
                    'latestRun' => $row['latestRun']
                );
            }
        }
        // Sort
        $date = array();
        foreach ($result as $key => $row)
        {
            $date[$key] = $row['latestRun'];
        }
        array_multisort($date, SORT_DESC, $result);
        return $result;
    }

    /**
     * Get project_run statistics
     * @return array (string state, int year, int month, int day, int runCount)
     */
    public function getProjectRunsStatistics()
    {
        $result = array();
        $query = $this->db->prepare("
            SELECT
                state.name as state,
                Year(timestamp) AS year,
                Month(timestamp) AS month,
                Day(timestamp) AS day,
                Count(*) as runCount
            FROM project_run
                INNER JOIN state ON project_run.state_id = state.id
            GROUP BY state, year, month, day
            ORDER BY state, year DESC, month DESC, day DESC;
        ");
        $query->execute(array());
        while($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $result[] = array(
                'state' => $row['state'],
                'year' => $row['year'],
                'month' => $row['month'],
                'day' => $row['day'],
                'runCount' => $row['runCount']
            );
        }
        return $result;
    }

    /**
     * Get project_runs for branch
     * @param string $branch
     * @return array (int id)
     */
    public function getProjectRunIdsBranch($branch)
    {
        $result = array();
        $query = $this->db->prepare("
            SELECT id FROM project_run WHERE branch_id = (SELECT id FROM branch WHERE name = ?);
        ");
        $query->execute(array(
            $branch
        ));
        while($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $result[] = array(
                'id' => $row['id']
            );
        }
        return $result;
    }

    /**
     * Get project_runs for state on a date
     * @param string $month
     * @return array (int id)
     */
    public function getProjectRunIdsDate($state, $date)
    {
        $result = array();
        $year = substr($date, 0, strpos($date, '-'));
        $month = substr($date, strpos($date, '-') + 1);
        $day = substr($month, strpos($month, '-') + 1);
        $month = substr($month, 0, strpos($month, '-'));
        $query = $this->db->prepare("
            SELECT project_run.id
            FROM project_run INNER JOIN state ON project_run.state_id = state.id
            WHERE state.name = ? AND Year(timestamp) = ? AND Month(timestamp) = ? AND Day(timestamp) = ?;
        ");
        $query->execute(array(
            $state,
            $year,
            $month,
            $day
        ));
        while($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $result[] = array(
                'id' => $row['id']
            );
        }
        return $result;
    }

    /**
     * Get conf_runs for project_run
     * @param int $projectRunId
     * @return array (int id)
     */
    public function getConfRunIds($projectRunId)
    {
        $result = array();
        $query = $this->db->prepare("
            SELECT id FROM conf_run WHERE project_run_id = ?;
        ");
        $query->bindParam(1, $projectRunId, PDO::PARAM_INT);
        $query->execute();
        while($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $result[] = array(
                'id' => $row['id']
            );
        }
        return $result;
    }

    /**
     * Get testset_runs for conf_run
     * @param int $confRunId
     * @return array (int id)
     */
    public function getTestsetRunIds($confRunId)
    {
        $result = array();
        $query = $this->db->prepare("
            SELECT id FROM testset_run WHERE conf_run_id = ?;
        ");
        $query->bindParam(1, $confRunId, PDO::PARAM_INT);
        $query->execute();
        while($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $result[] = array(
                'id' => $row['id']
            );
        }
        return $result;
    }

    /**
     * Get testfunction_runs for testset_run
     * @param int $testsetRunId
     * @return array (int id)
     */
    public function getTestfunctionRunIds($testsetRunId)
    {
        $result = array();
        $query = $this->db->prepare("
            SELECT id FROM testfunction_run WHERE testset_run_id = ?;
        ");
        $query->bindParam(1, $testsetRunId, PDO::PARAM_INT);
        $query->execute();
        while($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $result[] = array(
                'id' => $row['id']
            );
        }
        return $result;
    }

    /**
     * Delete project_run
     * @param int $projectRunId
     * @return bool
     */
    public function deleteProjectRun($projectRunId)
    {
        $query = $this->db->prepare("
            DELETE FROM project_run WHERE id = ?;
        ");
        $query->bindParam(1, $projectRunId, PDO::PARAM_INT);
        $result = $query->execute();
        return $result;
    }

    /**
     * Delete conf_runs for project_run
     * @param int $projectRunId
     * @return bool
     */
    public function deleteConfRuns($projectRunId)
    {
        $query = $this->db->prepare("
            DELETE FROM conf_run WHERE project_run_id = ?;
        ");
        $query->bindParam(1, $projectRunId, PDO::PARAM_INT);
        $result = $query->execute();
        return $result;
    }

    /**
     * Delete phase_runs for conf_run
     * @param int $confRunId
     * @return bool
     */
    public function deletePhaseRuns($confRunId)
    {
        $query = $this->db->prepare("
            DELETE FROM phase_run WHERE conf_run_id = ?;
        ");
        $query->bindParam(1, $confRunId, PDO::PARAM_INT);
        $result = $query->execute();
        return $result;
    }

    /**
     * Delete testset_runs for conf_run
     * @param int $confRunId
     * @return bool
     */
    public function deleteTestsetRuns($confRunId)
    {
        $query = $this->db->prepare("
            DELETE FROM testset_run WHERE conf_run_id = ?;
        ");
        $query->bindParam(1, $confRunId, PDO::PARAM_INT);
        $result = $query->execute();
        return $result;
    }

    /**
     * Delete testfunction_runs for testset_run
     * @param int $testsetRunId
     * @return bool
     */
    public function deleteTestfunctionRuns($testsetRunId)
    {
        $query = $this->db->prepare("
            DELETE FROM testfunction_run WHERE testset_run_id = ?;
        ");
        $query->bindParam(1, $testsetRunId, PDO::PARAM_INT);
        $result = $query->execute();
        return $result;
    }

    /**
     * Delete testrow_runs for testfunction_run
     * @param int $testfunctionRunId
     * @return bool
     */
    public function deleteTestrowRuns($testfunctionRunId)
    {
        $query = $this->db->prepare("
            DELETE FROM testrow_run WHERE testfunction_run_id = ?;
        ");
        $query->bindParam(1, $testfunctionRunId, PDO::PARAM_INT);
        $result = $query->execute();
        return $result;
    }

    /**
     * Delete project_run and all its linked data
     * @param int $projectRunId
     * @return bool
     */
    public function deleteProjectRunData($projectRunId)
    {
        $result = true;
        $confRuns = self::getConfRunIds($projectRunId);
        foreach ($confRuns as $confRun) {
            $testsetRuns = self::getTestsetRunIds($confRun['id']);
            foreach ($testsetRuns as $testsetRun) {
                $testfunctionRuns = self::getTestfunctionRunIds($testsetRun['id']);
                foreach ($testfunctionRuns as $testfunctionRun) {
                    // Delete related testrow_runs
                    if (!self::deleteTestrowRuns($testfunctionRun['id']))
                        $result = false;
                }
                // Delete related testfunction_runs
                if (!self::deleteTestfunctionRuns($testsetRun['id']))
                    $result = false;
            }
            // Delete related testset_runs
            if (!self::deleteTestsetRuns($confRun['id']))
                $result = false;
            // Delete related phase_runs
            if (!self::deletePhaseRuns($confRun['id']))
                $result = false;
        }
        // Delete related conf_runs
        if (!self::deleteConfRuns($projectRunId))
            $result = false;
        // Delete project_run
        if (!self::deleteProjectRun($projectRunId))
            $result = false;
        return $result;
    }

    /**
     * Delete branch and all its linked data
     * @param string $branch
     * @return bool
     */
    public function deleteBranch($branch)
    {
        $result = true;
        // Delete data from xxx_run tables
        $projectRuns = self::getProjectRunIdsBranch($branch);
        foreach ($projectRuns as $projectRun) {
            if (!self::deleteProjectRunData($projectRun['id']))
                $result = false;
        }
        // Delete branch
        $query = $this->db->prepare("
            DELETE FROM branch WHERE name = ?
        ");
        $result2 = $query->execute(array(
            $branch
        ));
        if (!$result2)
            $result = false;
        return $result;
    }

    /**
     * Set archived flag for the branch
     * @param string $branch
     * @return bool
     */
    public function archiveBranch($branch)
    {
        $query = $this->db->prepare("
            UPDATE branch SET archived = 1 WHERE name = ?
        ");
        $result = $query->execute(array(
            $branch
        ));
        return $result;
    }

    /**
     * Clear archived flag for the branch
     * @param string $branch
     * @return bool
     */
    public function restoreBranch($branch)
    {
        $query = $this->db->prepare("
            UPDATE branch SET archived = 0 WHERE name = ?
        ");
        $result = $query->execute(array(
            $branch
        ));
        return $result;
    }

    /**
     * Delete all build runs from selected date in selected state
     * @param string $state
     * @param string $date
     * @return bool
     */
    public function deleteRunsData($state, $date)
    {
        $result = true;
        $projectRuns = self::getProjectRunIdsDate($state, $date);
        foreach ($projectRuns as $projectRun) {
            if (!self::deleteProjectRunData($projectRun['id']))
                $result = false;
        }
        return $result;
    }

}

?>
