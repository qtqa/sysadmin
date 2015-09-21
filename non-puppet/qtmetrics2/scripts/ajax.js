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
 * Ajax route calls
 * @since     21-09-2015
 * @author    Juha Sippola
 */

$(function () {

    // Get all div ids on a page to call correct routes
    var div;
    var divs = [];
    $(".container-fluid").find("div").each(function(){ divs.push(this.id); });

    // Testset project / latest status
    var project;
    if ($.inArray('testset_project_data_latest', divs) > -1) {
        project = $('#project').html();
        $.ajax({
            url: "data/testsetproject/latest/" + project,
            dataType: "html",
            cache: true
        })
        .done(function( html ) {
            console.log(this.url + " done");
            $('#testset_project_data_latest').html(html);
        });
    }

    // Testset project / results in branches
    if ($.inArray('testset_project_data_results', divs) > -1) {
        project = $('#project').html();
        $.ajax({
            url: "data/testsetproject/results/" + project,
            dataType: "html",
            cache: true
        })
        .done(function( html ) {
            console.log(this.url + " done");
            $('#testset_project_data_results').html(html);
        });
    }

    // Top testset failures
    if ($.inArray('testsets_top_data', divs) > -1) {
        $.ajax({
            url: "data/test/top",
            dataType: "html",
            cache: true
        })
        .done(function( html ) {
            console.log(this.url + " done");
            $('#testsets_top_data').html(html);
        });
    }

    // Flaky testsets
    if ($.inArray('flaky_testsets_data', divs) > -1) {
        $.ajax({
            url: "data/test/flaky",
            dataType: "html",
            cache: true
        })
        .done(function( html ) {
            console.log(this.url + " done");
            $('#flaky_testsets_data').html(html);
        });
    }

    // Top testfunction failures
    if ($.inArray('testfunctions_top_data', divs) > -1) {
        $.ajax({
            url: "data/test/toptestfunctions",
            dataType: "html",
            cache: true
        })
        .done(function( html ) {
            console.log(this.url + " done");
            $('#testfunctions_top_data').html(html);
        });
    }

    // Blacklisted passed testfunctions
    if ($.inArray('testfunctions_blacklisted_passed_data', divs) > -1) {
        $.ajax({
            url: "data/test/bpassedtestfunctions",
            dataType: "html",
            cache: true
        })
        .done(function( html ) {
            console.log(this.url + " done");
            $('#testfunctions_blacklisted_passed_data').html(html);
        });
    }

});
