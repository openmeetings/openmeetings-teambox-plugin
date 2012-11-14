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

require_once('RestService.php');
require_once('TeamboxUtils.php');

class OpenMeetingsRestService extends RestService {
    
    private $mysqlLogin;
    private $mysqlPassword;
    private $mysqlDatabse;
    
    private $adminLogin;
    private $adminPassword;
    
    private $restApiUrl;
    private $omHashUrl;
    
    private $sessionId;

    public function OpenMeetingsRestService() {
        parent::RestService('OpenMeetings');
        
        global $CFG;
        $this->restApiUrl = $CFG[om_api_url];
        $this->omHashUrl = $CFG[om_hash_url];
        $this->adminLogin = $CFG[admin_login];
        $this->adminPassword = $CFG[admin_password];
        
        $this->mysqlLogin = $CFG[mysql_login];
        $this->mysqlPassword = $CFG[mysql_password];
        $this->mysqlDatabse = $CFG[mysql_databse];
    }
    
    public function loginAdmin() {
        $request = $this->restApiUrl . 'UserService/getSession';
        $response = $this->call($request);
        $xml = $this->getXmlContent($response);
        $this->sessionId = $xml->children('ns', true)->return->children('ax24', true)->session_id;
        
        $request = $this->restApiUrl.'UserService/loginUser?'
                .'SID='.$this->sessionId
                .'&username=' . $this->adminLogin
                .'&userpass=' . $this->adminPassword;
        $response = $this->call($request);
        $xml = $this->getXmlContent($response);
        $returnValue = $xml->children('ns', true)->return[0];

        return ($returnValue>0);
    }
    
    public function getInvitationsMap($projects, $account) {
        $link = mysql_connect('localhost', $this->mysqlLogin, $this->mysqlPassword);
        if (!$link) {
            die("Can't create a connection to the teambox databse");
        }
        
        $ret = array();

        foreach ($projects as $proj) {
            $projectId = $proj->id;
            $query = 'select roomId from '.$this->mysqlDatabse.'.ProjectRooms where projectId = '.$projectId;
            $result = mysql_query($query);
            
            $createRoom = true;
            if ($row = mysql_fetch_assoc($result)) {
                $roomId = $row['roomId'];
                $createRoom = false;
            }
            mysql_free_result($result);

            if ($createRoom) {
                $roomId = $this->createRoom($proj->name);
                if ($roomId < 0) {
                    die("Can't create a room: ".$proj->name);
                }
                $query = 'insert into '.$this->mysqlDatabse.'.ProjectRooms values('.$projectId.', '.$roomId.')';
                mysql_query($query);
            }
            
            $hash = $this->getRoomHash($roomId, $account);
            if ($hash < 0) {
                die("Can't create a secure hash: ".$proj->name);
            }
            
            $url = $this->omHashUrl.$hash;
            $ret[$proj->name] = $url; 
        }

        mysql_close($link);
        return $ret;
    }
    
    public function getInvitationForProject($organization, $project, $account) {
        $projectId = $organization->id."_".$project->id;
        $projectName = $organization->name."_".$project->name;
        
        $url = $this->getInvitationUrl($projectId, $projectName, $account);
        return $url;
    }
    
    public function getInvitationForOrganization($organization, $account) {
        $url = $this->getInvitationUrl($organization->id, $organization->name, $account);
        return $url;
    }

    public function getInvitationUrl($id, $name, $account) {
        $link = mysql_connect('localhost', $this->mysqlLogin, $this->mysqlPassword);
        if (!$link) {
            die("Can't create a connection to the teambox databse");
        }
        $query = 'select roomId from '.$this->mysqlDatabse.'.ProjectRooms where projectId = '.$id;
        $result = mysql_query($query);

        $createRoom = true;
        if ($row = mysql_fetch_assoc($result)) {
            $roomId = $row['roomId'];
            $createRoom = false;
        }
        mysql_free_result($result);

        if ($createRoom) {
            $roomId = $this->createRoom($name);
            if ($roomId < 0) {
                die("Can't create a room: ".$name);
            }
            $query = 'insert into '.$this->mysqlDatabse.'.ProjectRooms values('.$id.', '.$roomId.')';
            mysql_query($query);
        }

        $hash = $this->getRoomHash($roomId, $account);
        if ($hash < 0) {
            die("Can't create a secure hash: ".$name);
        }

        $langId = getLanguageId($account->locale);
        $url = $this->omHashUrl.$hash.'&language='.$langId;
    
        mysql_close($link);
        return $url;
    }
    
    public function createRoom($name) {
        $request = $this->restApiUrl."RoomService/addRoomWithModeration?"
            ."SID=".$this->sessionId
            ."&name=".$name
            ."&roomtypes_id=3"
            ."&numberOfPartizipants=10"
            ."&ispublic=false"
            ."&isModeratedRoom=false"
            ."&appointment=false"
            ."&isDemoRoom=false"
            ."&demoTime=0";
        
        $request = str_replace(' ', '_', $request);
        $response = $this->call($request);
        $xml = $this->getXmlContent($response);
        $roomId = $xml->children('ns', true)->return[0];
        
        return $roomId;
    }
    
    public function getRoomHash($roomId, $account) {
        $request = $this->restApiUrl."UserService/setUserObjectAndGenerateRoomHash?"
            ."SID=".$this->sessionId
            ."&username=".$account->last_name
            ."&firstname=".$account->first_name
            ."&lastname=".$account->last_name
            ."&email=".$account->email
            ."&room_id=".$roomId
            ."&becomeModeratorAsInt=1"
            ."&showAudioVideoTestAsInt=0";
        $request = str_replace(' ', '_', $request);
        $response = $this->call($request);
        $xml = $this->getXmlContent($response);
        $hash = $xml->children('ns', true)->return[0];
        
        return $hash;
    }
    
    private function getXmlContent($response) {
        if (!($xml = strstr($response, '<ns'))) {
            $xml = null;
        }

        // Create a SimpleXML object with XML response
        $simple_xml = simplexml_load_string($xml, "SimpleXMLElement", 0,"http://services.axis.openmeetings.org", true);

        return $simple_xml;
    }
}

?>
