/*
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
*/

/*
 * SQL script to create tables and indexes
 * @since     25-09-2015
 * @author    Juha Sippola
 */


-- database

CREATE DATABASE IF NOT EXISTS qtmetrics
    DEFAULT CHARACTER SET = 'latin1' DEFAULT COLLATE 'latin1_general_ci';
USE qtmetrics;


-- tables

-- Table db_status
CREATE TABLE db_status (
    refreshed             TIMESTAMP             NOT NULL,
    refresh_in_progress   BOOL                  NOT NULL,
    logs_current          INT UNSIGNED          NOT NULL,
    logs_total            INT UNSIGNED          NOT NULL
) ENGINE MyISAM;

-- Table phase
CREATE TABLE phase (
    id                    TINYINT UNSIGNED      NOT NULL  AUTO_INCREMENT,
    name                  VARCHAR(100)          NOT NULL,
    UNIQUE INDEX unique_phase (name),
    CONSTRAINT phase_pk PRIMARY KEY (id)
) ENGINE MyISAM;

-- Table branch
CREATE TABLE branch (
    id                    TINYINT UNSIGNED      NOT NULL  AUTO_INCREMENT,
    name                  VARCHAR(20)           NOT NULL,
    archived              BOOL                  NOT NULL  DEFAULT 0,
    UNIQUE INDEX unique_branch (name),
    CONSTRAINT branch_pk PRIMARY KEY (id)
) ENGINE MyISAM;

-- Table compiler
CREATE TABLE compiler (
    id                    TINYINT UNSIGNED      NOT NULL  AUTO_INCREMENT,
    compiler              VARCHAR(20)           NULL DEFAULT NULL,
    UNIQUE INDEX unique_compiler (compiler),
    CONSTRAINT compiler_pk PRIMARY KEY (id)
) ENGINE MyISAM;

-- Table conf
CREATE TABLE conf (
    id                    SMALLINT UNSIGNED     NOT NULL  AUTO_INCREMENT,
    host_id               SMALLINT UNSIGNED     NOT NULL,
    target_id             SMALLINT UNSIGNED     NOT NULL,
    host_compiler_id      TINYINT UNSIGNED      NOT NULL,
    target_compiler_id    TINYINT UNSIGNED      NOT NULL,
    name                  VARCHAR(100)          NOT NULL,
    features              VARCHAR(100)          NULL DEFAULT NULL,
    UNIQUE INDEX unique_conf (name),
    CONSTRAINT conf_pk PRIMARY KEY (id)
) ENGINE MyISAM;

-- Table conf_run
CREATE TABLE conf_run (
    id                    MEDIUMINT UNSIGNED    NOT NULL  AUTO_INCREMENT,
    conf_id               SMALLINT UNSIGNED     NOT NULL,
    project_run_id        MEDIUMINT UNSIGNED    NOT NULL,
    forcesuccess          BOOL                  NOT NULL,
    insignificant         BOOL                  NOT NULL,
    result                ENUM('SUCCESS','FAILURE','ABORTED','undef')    NOT NULL,
    total_testsets        INT UNSIGNED          NOT NULL,
    timestamp             TIMESTAMP             NOT NULL,
    duration              TIME                  NOT NULL,
    CONSTRAINT conf_run_pk PRIMARY KEY (id)
) ENGINE MyISAM;

-- Table phase_run
CREATE TABLE phase_run (
    id                    MEDIUMINT UNSIGNED    NOT NULL  AUTO_INCREMENT,
    phase_id              TINYINT UNSIGNED      NOT NULL,
    conf_run_id           MEDIUMINT UNSIGNED    NOT NULL,
    start                 TIMESTAMP             NOT NULL,
    end                   TIMESTAMP             NOT NULL,
    CONSTRAINT phase_run_pk PRIMARY KEY (id)
) ENGINE MyISAM;

-- Table platform
CREATE TABLE platform (
    id                    SMALLINT UNSIGNED     NOT NULL  AUTO_INCREMENT,
    os                    VARCHAR(10)           NOT NULL,
    os_version            VARCHAR(20)           NULL DEFAULT NULL,
    arch                  VARCHAR(20)           NULL DEFAULT NULL,
    UNIQUE INDEX unique_platform (os,os_version,arch),
    CONSTRAINT platform_pk PRIMARY KEY (id)
) ENGINE MyISAM;

-- Table project
CREATE TABLE project (
    id                    TINYINT UNSIGNED      NOT NULL  AUTO_INCREMENT,
    name                  VARCHAR(30)           NOT NULL,
    UNIQUE INDEX unique_project (name),
    CONSTRAINT project_pk PRIMARY KEY (id)
) ENGINE MyISAM;

-- Table project_run
CREATE TABLE project_run (
    id                    MEDIUMINT UNSIGNED    NOT NULL  AUTO_INCREMENT,
    project_id            TINYINT UNSIGNED      NOT NULL,
    branch_id             TINYINT UNSIGNED      NOT NULL,
    state_id              TINYINT UNSIGNED      NOT NULL,
    build_key             BIGINT UNSIGNED       NOT NULL,
    result                ENUM('SUCCESS','FAILURE','ABORTED')    NOT NULL,
    timestamp             TIMESTAMP             NOT NULL,
    duration              TIME                  NOT NULL,
    UNIQUE INDEX unique_project_run (project_id,branch_id,state_id,build_key),
    CONSTRAINT project_run_pk PRIMARY KEY (id)
) ENGINE MyISAM;

-- Table state
CREATE TABLE state (
    id                    TINYINT UNSIGNED      NOT NULL  AUTO_INCREMENT,
    name                  VARCHAR(30)           NOT NULL,
    UNIQUE INDEX unique_state (name),
    CONSTRAINT state_pk PRIMARY KEY (id)
) ENGINE MyISAM;

-- Table testfunction
CREATE TABLE testfunction (
    id                    MEDIUMINT UNSIGNED    NOT NULL  AUTO_INCREMENT,
    testset_id            SMALLINT UNSIGNED     NOT NULL,
    name                  VARCHAR(100)          CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
    UNIQUE INDEX unique_testfunction (testset_id,name),
    CONSTRAINT testfunction_pk PRIMARY KEY (id)
) ENGINE MyISAM;

-- Table testfunction_run
CREATE TABLE testfunction_run (
    id                    INT UNSIGNED          NOT NULL  AUTO_INCREMENT,
    testfunction_id       MEDIUMINT UNSIGNED    NOT NULL,
    testset_run_id        INT UNSIGNED          NOT NULL,
    result                ENUM('na','pass','fail','xpass','xfail','skip','bpass','bfail','bxpass','bxfail','bskip','tr_pass','tr_fail','tr_skip') NOT NULL DEFAULT 'na',
    duration              SMALLINT UNSIGNED     NOT NULL,
    CONSTRAINT testfunction_run_pk PRIMARY KEY (id)
) ENGINE MyISAM;

-- Table testrow
CREATE TABLE testrow (
    id                    MEDIUMINT UNSIGNED    NOT NULL  AUTO_INCREMENT,
    testfunction_id       MEDIUMINT UNSIGNED    NOT NULL,
    name                  VARCHAR(500)          CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
    UNIQUE INDEX unique_testdata (testfunction_id,name),
    CONSTRAINT testrow_pk PRIMARY KEY (id)
) ENGINE MyISAM;

-- Table testrow_run
CREATE TABLE testrow_run (
    testrow_id            MEDIUMINT UNSIGNED    NOT NULL,
    testfunction_run_id   INT UNSIGNED          NOT NULL,
    result                ENUM('pass','fail','xpass','xfail','skip','bpass','bfail','bxpass','bxfail','bskip')    NOT NULL,
    CONSTRAINT testrow_run_pk PRIMARY KEY (testrow_id,testfunction_run_id)
) ENGINE MyISAM;

-- Table testset
CREATE TABLE testset (
    id                    SMALLINT UNSIGNED     NOT NULL  AUTO_INCREMENT,
    project_id            TINYINT UNSIGNED      NOT NULL,
    name                  VARCHAR(50)           NOT NULL,
    UNIQUE INDEX unique_testset (project_id,name),
    CONSTRAINT testset_pk PRIMARY KEY (id)
) ENGINE MyISAM;

-- Table testset_run
CREATE TABLE testset_run (
    id                    INT UNSIGNED          NOT NULL  AUTO_INCREMENT,
    testset_id            SMALLINT UNSIGNED     NOT NULL,
    conf_run_id           MEDIUMINT UNSIGNED    NOT NULL,
    run                   TINYINT UNSIGNED      NOT NULL,
    result                ENUM('passed','failed','ipassed','ifailed')    NOT NULL,
    duration              SMALLINT UNSIGNED     NOT NULL,
    total_passed          SMALLINT UNSIGNED     NOT NULL,
    total_failed          SMALLINT UNSIGNED     NOT NULL,
    total_skipped         SMALLINT UNSIGNED     NOT NULL,
    total_blacklisted     SMALLINT UNSIGNED     NOT NULL,
    CONSTRAINT testset_run_pk PRIMARY KEY (id)
) ENGINE MyISAM;

-- indexes

-- project_run
CREATE INDEX by_timestamp ON project_run (timestamp, state_id, project_id);
CREATE INDEX by_state ON project_run (state_id, project_id, timestamp);

-- conf_run
CREATE INDEX by_project_run ON conf_run (project_run_id DESC, result);

-- testset_run
CREATE INDEX by_conf_run ON testset_run (conf_run_id DESC, run, result);
CREATE INDEX by_testset ON testset_run (testset_id, result);
CREATE INDEX by_run ON testset_run (run, result);

-- testfunction_run
CREATE INDEX by_testset_run ON testfunction_run (testset_run_id DESC, result, testfunction_id);

-- testrow_run
CREATE INDEX by_testfunction_run ON testrow_run (testfunction_run_id DESC, result, testrow_id);


-- End of file.
