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

if (!defined("TESTDOWNLOAD"))
    define("TESTDOWNLOAD", "suite_download_qt");            // The fixed test name for test download; results include e.g. installer build number

if (!defined("SUMMARYXMLFILENAME"))
    define("SUMMARYXMLFILENAME", "summary.xml");            // The result summary file name

if (!defined("RESULTXMLFILENAMEPREFIX"))
    define("RESULTXMLFILENAMEPREFIX", "result");            // The result file name starts with this string (e.g. "result_05_22_17.443.xml")

if (!defined("TARFILENAMEEXTENSION"))
    define("TARFILENAMEEXTENSION", ".tar.gz");              // Tar file name extension; tar file name used for configuration name by removing this extension

if (!defined("RTATESTHISTORYNUMBERMAX"))
    define("RTATESTHISTORYNUMBERMAX", 2000000);             // The biggest theoretic Jenkins build history number (to check the smallest and biggest number used currently)

if (!defined("TESTTYPESEPARATOR"))
    define("TESTTYPESEPARATOR", "_tests_");                 // String to separate the test type and platform (e.g. "Qt5_RTA_opensource_installer_tests_linux_32bit")

if (!defined("LICENSETYPESEPARATOR"))
    define("LICENSETYPESEPARATOR", "_RTA_");                // String to separate the license type (e.g. "Qt5_RTA_opensource_installer_tests_linux_32bit")

if (!defined("BUILDNUMBERTITLE"))
    define("BUILDNUMBERTITLE", "nstaller build number:");   // String to tag the build number; the leading "I" left out on purpose (e.g. "Installer build number: 216")

if (!defined("HISTORYJOBLISTCOUNT"))
    define("HISTORYJOBLISTCOUNT", 13);                      // The maximum number of test jobs to be shown in the history view

if (!defined("WORDWRAPCHARSNORMAL"))
    define("WORDWRAPCHARSNORMAL", 90);                      // Word wrapping for long text in failure description (normal style)

if (!defined("WORDWRAPCHARSBOLD"))
    define("WORDWRAPCHARSBOLD", 80);                        // Word wrapping for long text in failure description (bold style)

?>
