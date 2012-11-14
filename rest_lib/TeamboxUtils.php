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

function isArchivedProject($project) {
    if (null == $project->archived) {
        return false;
    } else {
        return $project->archived;
    }
}

function getFilteredOrganizationProjects($organization, $projects) {
    $result = array();
    foreach ($projects as $project) {
        if (isArchivedProject($project)) {
            continue;
        }
        if ($project->organization_id === $organization->id) {
            $idx = strtoupper($project->name . $project->id);
            $result[$idx] = $project;
        }
    }

    ksort($result);
    return $result;
}

function getSortedOrganizations($organizations) {
    $result = array();
    foreach ($organizations as $org) {
        $idx = strtoupper($org->name . $org->id);
        $result[$idx] = $org;
    }

    ksort($result);
    return $result;
}

function getLanguageId($lang) {
    $languages = array(
        'ar'     => 14,
        'bt'     => 1,
        'ca'     => 29,
        'de'     => 2,
        'en'     => 1,
        'es'     => 8,
        'fr'     => 4,
        'it'     => 5,
        'ko'     => 13,
        'pl'     => 25,
        'pt-BR'  => 7,
        'ru'     => 9,
        'si'     => 1,
        'tr'     => 18,
        'zh'     => 22
    );

    if (array_key_exists($lang, $languages)) {
        return $languages[$lang];
    } else {
        return 0;
    }
}

?>
