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
 * Speedo chart functions
 * @since     27-09-2015
 * @author    Juha Sippola
 */

$(function () {

    // Get all div ids on a page
    var divs = [];
    $(".chartSpeedo").find("div").each(function(){ divs.push(this.id); });

    // Draw the charts
    jQuery.each( divs, function( i, val ) {
        var percent = Math.round(($("#" + val + "Value").html()/100) * 100) / 100;
        drawSpeedo("#" + val, percent);
    });

});

function drawSpeedo(divId, percent) {

    var Needle, arc, arcEndRad, arcStartRad, barWidth, chart, chartInset,
        degToRad, el, endPadRad, height, margin, needle, numSections, padRad,
        percToDeg, percToRad, radius, sectionIndx, sectionPerc, startPadRad,
        svg, totalPercent, width, _i;

    console.log(divId + ": " + percent);

    barWidth = 30;
    numSections = 10;
    sectionPerc = 1 / numSections / 2;
    padRad = 0.05;
    chartInset = 0;
    totalPercent = 0.75;

    el = d3.select(divId);

    margin = {
        top: 90,
        right: 0,
        bottom: 0,
        left: 0
    };

    // width = el[0][0].offsetWidth + margin.left;
    // height = el[0][0].offsetWidth + margin.top;
    // radius = Math.min(width, height) / 2;
    width = 150;
    height = 150;
    radius = 75;

    percToDeg = function(perc) {
        return perc * 360;
    };

    percToRad = function(perc) {
        return degToRad(percToDeg(perc));
    };

    degToRad = function(deg) {
        return deg * Math.PI / 180;
    };

    svg = el.append('svg').attr('width', width + margin.left + margin.right).attr('height', height);
    chart = svg.append('g').attr('transform', "translate(" + ((width / 2) + margin.left) + ", " + margin.top + ")");
    for (sectionIndx = _i = 1; 1 <= numSections ? _i <= numSections : _i >= numSections; sectionIndx = 1 <= numSections ? ++_i : --_i) {
        arcStartRad = percToRad(totalPercent);
        arcEndRad = arcStartRad + percToRad(sectionPerc);
        totalPercent += sectionPerc;
        startPadRad = sectionIndx === 0 ? 0 : padRad / 2;
        endPadRad = sectionIndx === numSections ? 0 : padRad / 2;
        arc = d3.svg.arc().outerRadius(radius - chartInset).innerRadius(radius - chartInset - barWidth).startAngle(arcStartRad + startPadRad).endAngle(arcEndRad - endPadRad);
        chart.append('path').attr('class', "arc chart-color" + sectionIndx).attr('d', arc);
    }

    Needle = (function() {

        function Needle(len, radius) {
            this.len = len;
            this.radius = radius;
        }

        Needle.prototype.drawOn = function(el, perc) {
            el.append('circle').attr('class', 'needle-center').attr('cx', 0).attr('cy', 0).attr('r', this.radius);
            return el.append('path').attr('class', 'needle').attr('d', this.mkCmd(perc));
        };

        Needle.prototype.animateOn = function(el, perc) {
            var self;
            self = this;
            return el.transition().delay(500).ease('elastic').duration(4000).selectAll('.needle').tween('progress', function() {
                return function(percentOfPercent) {
                    var progress;
                    progress = percentOfPercent * perc;
                    return d3.select(this).attr('d', self.mkCmd(progress));
                };
            });
        };

        Needle.prototype.mkCmd = function(perc) {
            var centerX, centerY, leftX, leftY, rightX, rightY, thetaRad, topX, topY;
            thetaRad = percToRad(perc / 2);
            centerX = 0;
            centerY = 0;
            topX = centerX - this.len * Math.cos(thetaRad);
            topY = centerY - this.len * Math.sin(thetaRad);
            leftX = centerX - this.radius * Math.cos(thetaRad - Math.PI / 2);
            leftY = centerY - this.radius * Math.sin(thetaRad - Math.PI / 2);
            rightX = centerX - this.radius * Math.cos(thetaRad + Math.PI / 2);
            rightY = centerY - this.radius * Math.sin(thetaRad + Math.PI / 2);
            return "M " + leftX + " " + leftY + " L " + topX + " " + topY + " L " + rightX + " " + rightY;
        };

        return Needle;

    })();

    needle = new Needle(85, 8);
    needle.drawOn(chart, 0);
    needle.animateOn(chart, percent);

}
