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

/**
 * Show loading progress bar
 * @since     08-07-2015
 * @author    Juha Sippola
 */

// Hide the progress bar when page loaded
$(window).load(function() {
    $('#link_loading').hide();
});

// Show the progress bar when reloading the page (this also hides the progress bar when pressing back/forward button)
$(window).on('beforeunload', function() {
    setTimeout(function() {
        $('#link_loading').fadeIn();
    }, 1000); // wait for 1s
});

// Show the progress bar when internal link clicked
$(function(){
    $('a').on('click', function(e) {
        if ( $(this).attr('target') !== '_blank' && $(this).attr('href') !== "" && $(this).attr('href') !== "#") {
            setTimeout(function() {
                $('#link_loading').fadeIn();
            }, 1000); // wait for 1s
        }
        // Fallback: hide after a timeout so that never left on by accident (e.g. due to browser differences)
        setTimeout(function() {
            $('#link_loading').fadeOut();
        }, 10000); // wait for 10s
    });
});
