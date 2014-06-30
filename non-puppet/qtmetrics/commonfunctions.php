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

date_default_timezone_set("UTC");               // Set timezone

/* Converts UTC time to local time based on time offset
   Input:  $time is in UTC in format "Y-m-d H:i:s" e.g. "2013-06-07 04:02:06",
           $offset is e.g. "GMT+0300" or "GMT+0000" or "GMT-0600"
   Output: in UTC in format without the seconds (to save display space) "Y-m-d H:i" e.g. "2013-06-07 07:02" */
function getLocalTime($time, $offset)
{
    $originalTimestamp = strtotime($time . ' UTC');
    $offsetSign = substr($offset, 3, 1);
    $offsetHour = intval(substr($offset, 4, 2));
    $offsetMinute = intval(substr($offset, 6, 2));

    if ($offsetSign == "-") {
        $modifiedTimestamp = mktime(
            intval(date("H",$originalTimestamp)) - $offsetHour,
            intval(date("i",$originalTimestamp)) - $offsetMinute,
            intval(date("s",$originalTimestamp)),
            intval(date("m",$originalTimestamp)),
            intval(date("d",$originalTimestamp)),
            intval(date("Y",$originalTimestamp)));
    } else {
        $modifiedTimestamp = mktime(
            intval(date("H",$originalTimestamp)) + $offsetHour,
            intval(date("i",$originalTimestamp)) + $offsetMinute,
            intval(date("s",$originalTimestamp)),
            intval(date("m",$originalTimestamp)),
            intval(date("d",$originalTimestamp)),
            intval(date("Y",$originalTimestamp)));
    }

    $local = date("Y-m-d H:i", $modifiedTimestamp);
    return $local;
}

/* Checks the time between two timestamps (http://www.if-not-true-then-false.com/2010/php-calculate-real-differences-between-two-dates-or-timestamps/)
   Input: $time1, $time2 in UNIX timestamp format or PHP strtotime compatible strings
   Output: string in format "18 years, 11 months, 30 days, 23 hours, 59 minutes, 59 seconds" */
function dateDiff($time1, $time2, $precision = 6)
{
    // If not numeric then convert texts to unix timestamps
    if (!is_int($time1)) {
        $time1 = strtotime($time1);
    }
    if (!is_int($time2)) {
        $time2 = strtotime($time2);
    }
    // If time1 is bigger than time2 then swap time1 and time2
    if ($time1 > $time2) {
        $ttime = $time1;
        $time1 = $time2;
        $time2 = $ttime;
    }
    // Set up intervals and diffs arrays
    $intervals = array('year','month','day','hour','minute','second');
    $diffs = array();
    // Loop thru all intervals
    foreach ($intervals as $interval) {
        // Create temp time from time1 and interval
        $ttime = strtotime('+1 ' . $interval, $time1);
        // Set initial values
        $add = 1;
        $looped = 0;
        // Loop until temp time is smaller than time2
        while ($time2 >= $ttime) {
            // Create new temp time from time1 and interval
            $add++;
            $ttime = strtotime("+" . $add . " " . $interval, $time1);
            $looped++;
        }

        $time1 = strtotime("+" . $looped . " " . $interval, $time1);
        $diffs[$interval] = $looped;
    }
    $count = 0;
    $times = array();
    // Loop thru all diffs
    foreach ($diffs as $interval => $value) {
        // Break if we have needed precision
        if ($count >= $precision) {
            break;
        }
        // Add value and interval
        // if value is bigger than 0
        if ($value > 0) {
            // Add s if value is not 1
            if ($value != 1) {
                $interval .= "s";
            }
            // Add value and interval to times array
            $times[] = $value . " " . $interval;
            $count++;
        }
    }
    // Return string with times
    return implode(", ", $times);
}

/* Checks the time between two timestamps in seconds
   Input: $startTime is the earlier timestamp (2014-06-30 12:30:00)
          $endTime is the later timestamp (2014-06-30 12:45:15)
   Output: integer (seconds) */
function timeDiffSeconds($startTime, $endTime)
{
    // If not numeric then convert texts to unix timestamps
    if (!is_int($startTime)) {
      $startTime = strtotime($startTime);
    }
    if (!is_int($endTime)) {
      $endTime = strtotime($endTime);
    }
    // Time difference in seconds
    $difference = abs($endTime - $startTime);
    return $difference;
}

/* Checks if the code here is run on defined public server
   Input: $publicServer is the defined public server name
   Output: TRUE/FALSE */
function isPublicServer($publicServer)
{
    $result = FALSE;
    if ($_SERVER['SERVER_NAME'] == $publicServer)
        $result = TRUE;
    return $result;
}

/* Checks if client is connecting from a list of internal IP addresses
   Input: $internalIps is the list of internal IP addresses
   Output: TRUE/FALSE */
function isInternalClient($internalIps)
{
    $remoteAddress = $_SERVER['REMOTE_ADDR'];
    $result = FALSE;
    foreach($internalIps as $ip) {
        if($remoteAddress == $ip) {
            $result = TRUE;
            break;
        }
    }
    return $result;
}

?>
