<?php
#############################################################################
##
## Copyright (C) 2013 Digia Plc and/or its subsidiary(-ies).
## Contact: http://www.qt-project.org/legal
##
## This file is part of the Qt Metrics web portal.
##
## $QT_BEGIN_LICENSE:LGPL$
## Commercial License Usage
## Licensees holding valid commercial Qt licenses may use this file in
## accordance with the commercial license agreement provided with the
## Software or, alternatively, in accordance with the terms contained in
## a written agreement between you and Digia.  For licensing terms and
## conditions see http://qt.digia.com/licensing.  For further information
## use the contact form at http://qt.digia.com/contact-us.
##
## GNU Lesser General Public License Usage
## Alternatively, this file may be used under the terms of the GNU Lesser
## General Public License version 2.1 as published by the Free Software
## Foundation and appearing in the file LICENSE.LGPL included in the
## packaging of this file.  Please review the following information to
## ensure the GNU Lesser General Public License version 2.1 requirements
## will be met: http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
##
## In addition, as a special exception, Digia gives you certain additional
## rights.  These rights are described in the Digia Qt LGPL Exception
## version 1.1, included in the file LGPL_EXCEPTION.txt in this package.
##
## GNU General Public License Usage
## Alternatively, this file may be used under the terms of the GNU
## General Public License version 3.0 as published by the Free Software
## Foundation and appearing in the file LICENSE.GPL included in the
## packaging of this file.  Please review the following information to
## ensure the GNU General Public License version 3.0 requirements will be
## met: http://www.gnu.org/copyleft/gpl.html.
##
##
## $QT_END_LICENSE$
##
#############################################################################
?>

<?php

/* Platform grouping by Configurations */

$arrayPlatform = array(
    //     Platform name        String in Configuration
    array( "All",               "*" ),              // Index 0 reserved for 'not selected'
    array( "Linux all",         "*linux*" ),
    array( "Linux OpenSuSE",    "*linux*OpenSuSE*" ),
    array( "Linux RHEL",        "*linux*RHEL*" ),
    array( "Linux Ubuntu",      "*linux*Ubuntu*" ),
    array( "Mac all",           "macx*" ),
    array( "Mac OS X 10.6",     "macx*OSX_10.6" ),
    array( "Mac OS X 10.7",     "macx*OSX_10.7" ),
    array( "Mac OS X 10.8",     "macx*OSX_10.8" ),
    array( "Mac OS X 10.9",     "macx*OSX_10.9" ),
    array( "Windows all",       "*Windows*" ),
    array( "Windows 7",         "*Windows_7" ),
    array( "Windows 8",         "*Windows_8" ),
    array( "Windows 8.1",       "*Windows_81" ),
);

/* Project dashboard definitions */

if (!defined("HISTORYBUILDCOUNT"))
    define("HISTORYBUILDCOUNT", 20);                // Number of builds to be shown in Project and Configuration build history graphs
                                                    // (for consistency it may be reasonable to set this same as AUTOTEST_LATESTBUILDCOUNT)

if (!defined("AUTOTEST_LATESTBUILDCOUNT"))
    define("AUTOTEST_LATESTBUILDCOUNT", 20);        // Number of latest builds to be checked for each Project

if (!defined("FLAGON"))
    define("FLAGON", "Yes");                        // Flag display tags (e.g. for Configuration force success)
if (!defined("FLAGOFF"))
    define("FLAGOFF", "-");                         // Flag display tags (e.g. for Configuration force success)

/* Build phases in the order of execution */
define("BUILDMAINPHASEPREFIX", "testing");          // The checked phases appear under this main phase (starting with this string)
define("PHASEQUICKWITHIDLE", 99999);                // Tag phase that was delayed (has an idle time) but was completed in 0s (encode in calculation, decode in printing)
define("BUILDPHASESID", 0);
define("BUILDPHASESDISPLAYNAME", 1);
define("BUILDPHASESFULLNAMEPREFIX", 2);
$arrayAllBuildPhases = array(
    //     Id                       Displayed name       Phase name in database (starting with)
    array( "PHASECLEANINGIDLE",     "",                  "idle" ),                                   // The idle time before starting the next phase
    array( "PHASECLEANING",         "cleaning",          "cleaning existing target directories" ),
    array( "PHASEGITREPOSIDLE",     "",                  "idle" ),
    array( "PHASEGITREPOS",         "git repos",         "setting up git repositories" ),
    array( "PHASECONFIGURINGIDLE",  "",                  "idle" ),
    array( "PHASECONFIGURING",      "configuring Qt",    "configuring Qt" ),
    array( "PHASEQTQATESTS1IDLE",   "",                  "idle" ),
    array( "PHASEQTQATESTS1",       "qtqa tests (1)",    "running the qtqa tests" ),                 // Exception: qtqa tests may be run either after 'configuring Qt' or 'running the autotests' *)
    array( "PHASECOMPILINGIDLE",    "",                  "idle" ),
    array( "PHASECOMPILING",        "compiling Qt",      "compiling Qt" ),
  //array( "PHASECHECKINSTIDLE",    "",                  "idle" ),
  //array( "PHASECHECKINST",        "xxx",               "checking the installation" ),              // Skipped because typically 0s
    array( "PHASEINSTALLINGIDLE",   "",                  "idle" ),
    array( "PHASEINSTALLING",       "installing Qt",     "installing Qt" ),
  //array( "PHASEMAKINGDOCSIDLE",   "",                  "idle" ),
  //array( "PHASEMAKINGDOCS",       "xxx",               "making Qt docs" ),                         // Skipped because typically 0s
    array( "PHASEAUTOTESTSIDLE",    "",                  "idle" ),
    array( "PHASEAUTOTEST",         "autotests",         "running the autotests" ),
    array( "PHASEQTQATESTS2IDLE",   "",                  "idle" ),
    array( "PHASEQTQATESTS2",       "qtqa tests (2)",    "running the qtqa tests" ),                 // *)
);

/* Autotest dashboard definitions */

if (!defined("ZIPTESTFILENAMEIDENTIFIERS"))
    define("ZIPTESTFILENAMEIDENTIFIERS", "-testresults;.exe-testresults");    // To compare the autotest name "tst_xxx" against the file name in zip "tst_xxx-testresults-00.xml" or "tst_xxx.exe-testresults-00.xml"

if (!defined("CIBUILDDIRECTORYPREFIX"))
    define("CIBUILDDIRECTORYPREFIX", "build_");     // The Projects build directory starts with this (e.g. build_00001 or build_03681)

if (!defined("CITESTRESULTSFILE"))
    define("CITESTRESULTSFILE", "test-logs.zip");   // The autotest result zip file

if (!defined("AUTOTESTFAILUREWARNINGLEVEL"))
    define("AUTOTESTFAILUREWARNINGLEVEL", 80);      // Highlight the failure percentage (failures vs all) when higher than this

if (!defined("MAXCIBUILDNUMBER"))
    define("MAXCIBUILDNUMBER", 99999);              // The max number used for the builds, used also to check the string length of the plain build number in directory names

if (!defined("CITESTRESULTBUILDCOUNT"))
    define("CITESTRESULTBUILDCOUNT", 1);            // The default number of builds to be checked for test results unless time scale filter used (performance issue when scanning and opening the zip files)

?>
