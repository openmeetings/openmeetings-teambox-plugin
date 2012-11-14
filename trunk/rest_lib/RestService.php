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

class RestService {

    protected $serviceName;

    public function RestService($serviceName) {
        $this->serviceName = $serviceName;
    }

    public function call($url, $parameters = null, $method = 'GET', $dieOnError = true){
        // This will allow you to view errors in the browser
        // Note: set "display_errors" to 0 in production
        //ini_set('display_errors',1);
         
        // Report all PHP errors (notices, errors, warnings, etc.)
        //error_reporting(E_ALL);
         
        // URI used for making REST call. Each Web Service uses a unique URL.
        //$request
        	
        // Initialize the session by passing the request as a parameter
        $session = curl_init();
        
        if ($method === 'POST') {
            curl_setopt($session, CURLOPT_URL, $url);
            curl_setopt($session, CURLOPT_POST, true);
            curl_setopt($session, CURLOPT_POSTFIELDS, $parameters);
        } else {
            $request = $url . ($parameters ? '?' . $parameters : '');
            curl_setopt($session, CURLOPT_URL, $request);
        }

        // Set curl options by passing session and flags
        // CURLOPT_HEADER allows us to receive the HTTP header
        curl_setopt($session, CURLOPT_HEADER, true);
        	
        // CURLOPT_RETURNTRANSFER will return the response
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
        	
        // Make the request
        $response = curl_exec($session);
        	
        // Close the curl session
        curl_close($session);
        	
        // Confirm that the request was transmitted to the service! Image Search Service
        if(!$response) {
            if ($dieOnError) {
                die("Request ".$this->serviceName."! ".$this->serviceName." Service failed and no response was returned.");
            } else {
                return false;
            }
        }
        	
        // Create an array to store the HTTP response codes
        $status_code = array();
        	
        // Use regular expressions to extract the code from the header
        preg_match('/\d\d\d/', $response, $status_code);
        	
        // Check the HTTP Response code and display message if status code is not 200 (OK)
        switch( $status_code[0] ) {
            case 200:
                // Success
                break;
            case 503:
                if ($dieOnError) {
                    die('Your call to '.$this->serviceName.' Web Services failed and returned an HTTP status of 503.
                    That means: Service unavailable. An internal problem prevented us from returning'.
                    ' data to you.');
                } else {
                    return false;
                }
                break;
            case 403:
                if ($dieOnError) {
                    die('Your call to '.$this->serviceName.' Web Services failed and returned an HTTP status of 403.
                    That means: Forbidden. You do not have permission to access this resource, or are over'.
                    ' your rate limit.');
                } else {
                    return false;
                }
                break;
            case 400:
                // You may want to fall through here and read the specific XML error
                if ($dieOnError) {
                    die('Your call to '.$this->serviceName.' Web Services failed and returned an HTTP status of 400.
                    That means:  Bad request. The parameters passed to the service did not match as expected.
                    The exact error is returned in the XML response.');
                } else {
                    return false;
                }
                break;
            default:
                if ($dieOnError) {
                    die('Your call to '.$this->serviceName.' Web Services returned an unexpected HTTP status of: ' . $status_code[0]);
                } else {
                    return false;
                }
        }
        
        return $response;
    }
}

?>
