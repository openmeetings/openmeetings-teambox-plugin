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

global $CFG;
$CFG = parse_ini_file('config/settings.ini');

require_once('rest_lib/TeamBoxRestService.php');
require_once('rest_lib/OpenMeetingsRestService.php');
require_once('rest_lib/TeamboxUtils.php');
require_once('oauthLogin.php');

$token = getTeamBoxAccessToken();

$tbService = new TeamBoxRestService($token);
$account = $tbService->getAccountInfo();
$projects = $tbService->getUserProjects();
$organizations = $tbService->getUserOrganizations();

$omService = new OpenMeetingsRestService();
$logged = $omService->loginAdmin();
if (!$logged) {
    print 'OpenMeetings internal error. Ask your system administrator.';
    exit(0);
}

?>

<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
</head>
<link rel="stylesheet" type="text/css" href="css/teambox-print.css" media="screen" />
<link rel="stylesheet" type="text/css" href="css/teambox.css" media="screen" />
<link rel="stylesheet" type="text/css" href="css/index.css" media="screen" />
<body>

<br>
<div class="main-div">
<?php

if (0 == count($organizations)) {
    echo '<p>You do not participate in any organization and project, so you can not enter any conference room</p>';
} else {
    echo '<p class="table-title">Please, choose the project or the organization which conference room you want to enter:</p>';
    echo '<br>';

    echo '<table class="table-with-borders">';
    echo '<tr>';
        echo '<td><p class="table-title">Organizations:</p></td>';
        echo '<td><p class="table-title">Projects:</p></td>';
    echo '</tr>';

    $sortedOrgs = getSortedOrganizations($organizations);

    foreach ($sortedOrgs as $organization) {
        $orgProjs = getFilteredOrganizationProjects($organization, $projects);
        $url = $omService->getInvitationForOrganization($organization, $account);

        echo '<tr>';
            echo '<td>';
                echo '<a class="button button-primary" href="'.$url.'"><span>'.$organization->name.'</span></a>';
            echo '</td>';

            echo '<td>';
                if (0 == count($orgProjs)) {
                    echo '&nbsp';
                }
                $first = true;
                foreach ($orgProjs as $project) {
                    if ($first) {
                        $first = false;
                    } else {
                        echo '<div class="space-div"></div>';
                    }
                    $url = $omService->getInvitationForProject($organization, $project, $account);
                    echo '<p><a class="button" href="'.$url.'"><span>'.$project->name.'</span></a></p>';
                }
            echo '</td>';
        echo '</tr>';
    }
    echo '</table>';
}

?>
</div>

<script language="javascript"> 
function hide() {
	var ele = document.getElementById("facebox");
	ele.style.display = "none";
} 
</script>

<div id="facebox" style="top: 45%; left: 50%; width: 45%; height: 40%" >
    <div class="content" style="width: 100%; height: 100%">
        <div class="help_box_options" style="width: 97%; height: 100%">
            <ul>
                This is the demo version of the Apache Openmeetings application for Teambox. With this application you can use all features of Apache Openmeetings software such as video conferencing, instant messaging, white board, collaborative document editing and other groupware.
                <br><br>
                The special conference rooms are created for all of your organizations and each organizations' projects. Click to the corresponding button and start conferencing with your teammates.
                <br><br>
                In this case, you will use the demo OpenMeetings server. If you want to set up your own server you should visit our site:
                <br>
                <a href="http://incubator.apache.org/openmeetings/">Apache Openmeetings</a>
                <br>
                and download the latest version of the software. Feel free to write us directly by e-mail:
                <br>
                <a href="mailto:openmeetings-user@incubator.apache.org">openmeetings-user@incubator.apache.org</a>
            </ul>
        </div>
    </div>
    <a class="close" href="javascript:hide();" style="display: block;">
        <img class="close_image" title="Close message" src="https://teambox.com/assets/facebox/fancy_closebox.png">
    </a>
</div>

</body>
</html>
