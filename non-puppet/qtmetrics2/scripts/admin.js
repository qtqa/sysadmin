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
 * Admin functions
 * @since     19-08-2015
 * @author    Juha Sippola
 */

// Catch action confirmation from the modal
$('.modal .modal-footer button').on('click', function (e) {
    var buttonDiv;
    var $target = $(e.target);
    $(this).closest('.modal').on('hidden.bs.modal', function (e) {

        // Remove branch
        if ($target[0].id.search("confirm_branch_remove") === 0) {
            var branchTag = $target[0].id.substring($target[0].id.lastIndexOf('_') + 1);
            var branchName = $target[0].name;
            buttonDiv = '#' + branchTag + 'Button';
            $(buttonDiv).html("Removing...");
            // Send delete request and handle the response
            $.ajax({
                url: "api/branch/" + branchName,
                type: 'DELETE',
                success: function(result) {
                    console.log(this.type + ": " + this.url + " done");
                    $(buttonDiv).html("Removed");
                },
                error: function (request, status, error) {
                    console.log(this.type + ": " + this.url + " error (" + error + ")");
                    $(buttonDiv).html("Error!");
                }
            });
        }

        // Remove data
        if ($target[0].id.search("confirm_data_remove") === 0) {
            var dataDate = $target[0].id.substring($target[0].id.lastIndexOf('_') + 1);
            var dataState = $target[0].name;
            buttonDiv = '#' + dataState + '-' + dataDate + 'Button';
            $(buttonDiv).html("Removing...");
            // Send delete request and handle the response
            $.ajax({
                url: "api/data/" + dataState + "/" + dataDate,
                type: 'DELETE',
                success: function(result) {
                    console.log(this.type + ": " + this.url + " done");
                    $(buttonDiv).html("Removed");
                },
                error: function (request, status, error) {
                    console.log(this.type + ": " + this.url + " error (" + error + ")");
                    $(buttonDiv).html("Error!");
                }
            });
        }

    });
});
