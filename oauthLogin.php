<?php

/**
 * Licensed to the Apache Software Foundation (ASF) under one
 * or more contributor license agreements.  See the NOTICE file
 * distributed with this work for additional information
 * regarding copyright ownership.  The ASF licenses this file
 * to you under the Apache License, Version 2.0 (the
 * "License"); you may not use this file except in compliance
 * with the License.  You may obtain a copy of the License at
 *
 *    http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing,
 * software distributed under the License is distributed on an
 * "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied.  See the License for the
 * specific language governing permissions and limitations
 * under the License.
*/

require_once('rest_lib/TeamBoxRestService.php');

function validateAccessToken($token) {
    $tbService = new TeamBoxRestService($token);
    return $tbService->isValidAccessToken();
}

function getTeamBoxAccessToken() {
    global $CFG;
    $appKey = $CFG[app_key];
    $appSecret = $CFG[app_secret];
    if ($CFG[https]) {
        $callbackUrl = $CFG[callback_url_https];
    } else {
        $callbackUrl = $CFG[callback_url_http];
    }
    $authorizeEnd = $CFG[authorize_end];
    $tokenEnd = $CFG[token_end];
    $cookieDomain = $CFG[cookie_domain];
    $tokenCookieId = 'access_token';
    
    spl_autoload_register(function ($class) {
        require str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
    });
    
    $client = new OAuth2\Client($appKey, $appSecret, $callbackUrl);
    $configuration = new OAuth2\Service\Configuration($authorizeEnd, $tokenEnd);
    $dataStore = new OAuth2\DataStore\Session();
    $scope = 'read_projects';
    
    $service = new OAuth2\Service($client, $configuration, $dataStore, $scope);
    
    if (isset($_GET['action'])) {
        if ('authorize' == $_GET['action']) {
            if (isset($_COOKIE[$tokenCookieId])) {
                $tokenString = $_COOKIE[$tokenCookieId];
                $valid = validateAccessToken($tokenString);
                if ($valid) {
                    return $tokenString;
                }
            }

            // redirects to authorize endpoint
            $service->authorize();
        } else {
            exit(0);
        }
    }
    
    if (isset($_GET['code'])) {
        // retrieve access token from endpoint
        $service->getAccessToken();
        $token = $dataStore->retrieveAccessToken();
        $dataStore->storeAccessToken($token);
    }
    
    $token = $dataStore->retrieveAccessToken();
    if ($CFG[https]) {
        setcookie($tokenCookieId, $token->getAccessToken(), $token->getLifeTime(), '/', $cookieDomain, true);
    } else {
        setcookie($tokenCookieId, $token->getAccessToken(), $token->getLifeTime());
    }
    
    return $token->getAccessToken();
}

?>
