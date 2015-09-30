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
 * SQL script to drop tables and indexes (in the order allowed regarding the foreign keys)
 * @since     10-09-2015
 * @author    Juha Sippola
 */

USE qtmetrics;

DROP INDEX by_timestamp ON project_run;
DROP INDEX by_state ON project_run;
DROP INDEX by_project_run ON conf_run;
DROP INDEX by_conf_run ON testset_run;
DROP INDEX by_testset ON testset_run;
DROP INDEX by_run ON testset_run;
DROP INDEX by_testset_run ON testfunction_run;
DROP INDEX by_testfunction_run ON testrow_run;

DROP TABLE IF EXISTS `testrow_run`;
DROP TABLE IF EXISTS `testrow`;

DROP TABLE IF EXISTS `testfunction_run`;
DROP TABLE IF EXISTS `testfunction`;

DROP TABLE IF EXISTS `testset_run`;
DROP TABLE IF EXISTS `testset`;

DROP TABLE IF EXISTS `phase_run`;
DROP TABLE IF EXISTS `phase`;

DROP TABLE IF EXISTS `conf_run`;
DROP TABLE IF EXISTS `conf`;
DROP TABLE IF EXISTS `compiler`;
DROP TABLE IF EXISTS `platform`;

DROP TABLE IF EXISTS `project_run`;
DROP TABLE IF EXISTS `project`;
DROP TABLE IF EXISTS `branch`;
DROP TABLE IF EXISTS `state`;

DROP TABLE IF EXISTS `db_status`;
