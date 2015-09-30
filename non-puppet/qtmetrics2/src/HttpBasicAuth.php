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
 * Basic HTTP authentication
 * @since     30-09-2015
 * @author    Juha Sippola
 */

class HttpBasicAuth extends \Slim\Middleware
{
    /**
     * @var string
     */
    protected $realm;

    /**
     * Constructor
     * @param   string  $realm      The HTTP Authentication realm
     */
    public function __construct($realm = 'Protected Area')
    {
        $this->realm = $realm;
    }

    /**
     * Deny Access
     */
    public function deny_access() {
        $res = $this->app->response();
        $res->status(401);
        $res->header('WWW-Authenticate', sprintf('Basic realm="%s"', $this->realm));
    }

    /**
     * Authenticate
     * @param   string  $username   The HTTP Authentication username
     * @param   string  $password   The HTTP Authentication password
     */
    public function authenticate($username, $password) {
        $ini = Factory::conf();

        // The username must be alphanumeric
        if(!ctype_alnum($username))
            return false;

        // Check username and password
        if(isset($username) && isset($password)) {
            if (($username === $ini['admin_username'] AND $password === $ini['admin_password'])) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * Call
     * This method will check the HTTP request headers for previous authentication. If
     * the request has already authenticated, the next middleware is called. Otherwise,
     * a 401 Authentication Required response is returned to the client.
     */
    public function call()
    {
        $req = $this->app->request();
        $res = $this->app->response();
        $authUser = strip_tags($req->headers('PHP_AUTH_USER'));
        $authPass = strip_tags($req->headers('PHP_AUTH_PW'));

        if ($this->authenticate($authUser, $authPass)) {
            $this->next->call();
        } else {
            $this->deny_access();
        }
    }
}
