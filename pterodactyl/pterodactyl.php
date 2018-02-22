<?php

/**
MIT License

Copyright (c) 2018 Stepan Fedotov <stepan@crident.com>

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
**/

if(!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function pterodactyl_API(array $params, $endpoint, array $data = [], $method = "GET") {
    $url = $params['serverhostname'] . '/api/application/' . $endpoint;

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($curl, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
    curl_setopt($curl, CURLOPT_USERAGENT, "Pterodactyl-WHMCS");

    $headers = [
        "Authorization: Bearer " . $params['serverpassword'],
        "Accept: Application/vnd.pterodactyl.v1+json",
    ];

    if($method === 'POST') {
        $jsonData = json_encode($data);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $jsonData);
        array_push($headers, "Content-Type: application/json");
        array_push($headers, "Content-Length: " . strlen($jsonData));
    }

    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($curl);
    if(!isset($response)) logModuleCall("Pterodactyl-WHMCS", "CURL ERROR", curl_error($curl), "");

    $responseData = json_decode($response, true);
    $responseData['status_code'] = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    curl_close($curl);

    logModuleCall("Pterodactyl-WHMCS", $method . " - " . $url,
        isset($data) ? json_encode($data) : "",
        print_r($responseData, true));

    return $responseData;
}

function pterodactyl_Error($func, $params, Exception $err) {
    logModuleCall("Pterodactyl-WHMCS", $func, $params, $err->getMessage(), $err->getTraceAsString());
}

function pterodactyl_MetaData() {
    return [
        "DisplayName" => "Pterodactyl",
        "APIVersion" => "1.1",
        "RequiresServer" => true,
    ];
}

function pterodactyl_ConfigOptions() {
    return [
        "cpu" => [
            "FriendlyName" => "CPU Limit (%)",
            "Description" => "Amount of CPU to assign to the created server.",
            "Type" => "text",
            "Size" => 10,
        ],
        "disk" => [
            "FriendlyName" => "Disk Space (MB)",
            "Description" => "Amount of Disk Space to assign to the created server.",
            "Type" => "text",
            "Size" => 10,
        ],
        "memory" => [
            "FriendlyName" => "Memory (MB)",
            "Description" => "Amount of Memory to assign to the created server.",
            "Type" => "text",
            "Size" => 10,
        ],
        "swap" => [
            "FriendlyName" => "Swap (MB)",
            "Description" => "Amount of Swap to assign to the created server.",
            "Type" => "text",
            "Size" => 10,
        ],
        "location_id" => [
            "FriendlyName" => "Location ID",
            "Description" => "ID of the Location to automatically deploy to.",
            "Type" => "text",
            "Size" => 10,
        ],
        "node_id" => [
            "FriendlyName" => "Node ID",
            "Description" => "ID of the Node to deploy the server to (optional)",
            "Type" => "text",
            "Size" => 10,
        ],
        "nest_id" => [
            "FriendlyName" => "Nest ID",
            "Description" => "ID of the Nest for the server to use.",
            "Type" => "text",
            "Size" => 10,
        ],
        "egg_id" => [
            "FriendlyName" => "Egg ID",
            "Description" => "ID of the Egg for the server to use.",
            "Type" => "text",
            "Size" => 10,
        ],
        "io" => [
            "FriendlyName" => "Block IO Weight",
            "Description" => "Block IO Adjustment number.",
            "Type" => "text",
            "Size" => 10,
            "Default" => "500",
        ],
        "pack_id" => [
            "FriendlyName" => "Pack ID",
            "Description" => "ID of the Pack to install the server with (optional)",
            "Type" => "text",
            "Size" => 10,
        ],
        "startup" => [
            "FriendlyName" => "Startup",
            "Description" => "Custom startup command to assign to the created server (optional)",
            "Type" => "text",
            "Size" => 25,
        ],
        "image" => [
            "FriendlyName" => "Image",
            "Description" => "Custom Docker image to assign to the created server (optional)",
            "Type" => "text",
            "Size" => 25,
        ],
    ];
}

function pterodactyl_TestConnection(array $params) {
    $solutions = [
        401 => "Authorization header either missing or not provided.",
        403 => "Double check the password (which should be the Application Key).",
        404 => "Result not found.",
        422 => "Validation error.",
        500 => "Panel errored, check panel logs.",
    ];

    $err = "";
    try {
        $response = pterodactyl_API($params, 'nodes');

        if($response['status_code'] !== 200) {
            $status_code = $response['status_code'];
            $err = "Invalid status_code received: " . $status_code . ". Possible solutions: "
                . (isset($solutions[$status_code]) ? $solutions[$status_code] : "None.");
        } else {
            if($response['meta']['pagination']['count'] === 0) {
                $err = "Authentication successful, but no nodes are available.";
            }
        }
    } catch(Exception $e) {
        pterodactyl_Error(__FUNCTION__, $params, $e);
        $err = $e->getMessage();
    }

    return [
        "success" => $err === "",
        "error" => $err,
    ];
}

function pterodactyl_GenerateUsername() {
    $returnable = false;
    while (!$returnable) {
        $generated = str_random(8);
        if (preg_match('/[A-Z]+[a-z]+[0-9]+/', $generated)) {
            $returnable = true;
        }
    }
    return $generated;
}

function pterodactyl_GetOption(array $params, $id, $default = NULL) {
    $options = pterodactyl_ConfigOptions();
    $friendlyName = $options[$id]['FriendlyName'];
    if(isset($params['configoptions'][$friendlyName]) && $params['configoptions'][$friendlyName] !== '') {
        return $params['configoptions'][$friendlyName];
    } else if(isset($params['customfields'][$friendlyName]) && $params['customfields'][$friendlyName] !== '') {
        return $params['customfields'][$friendlyName];
    }

    $i = 0;
    foreach(pterodactyl_ConfigOptions() as $key => $value) {
        $i++;
        if($key === $id) break;
    }

    if(isset($params['configoption' . $i]) && $params['configoption' . $i] !== '') {
        return $params['configoption' . $i];
    }

    return $default;
}

function pterodactyl_CreateAccount(array $params) {
    try {
        // $serverResult = pterodactyl_API($params, 'servers/external/');

        $userResult = pterodactyl_API($params, 'users/external/' . $params['clientsdetails']['id']);
        if($userResult['status_code'] === 404) {
            $userResult = pterodactyl_API($params, 'users', [
                'username' => pterodactyl_GenerateUsername(),
                'email' => $params['clientsdetails']['email'],
                'first_name' => $params['clientsdetails']['firstname'],
                'last_name' => $params['clientsdetails']['lastname'],
                'external_id' => $params['clientsdetails']['id'],
            ], 'POST');
        }

        if($userResult['status_code'] === 200 || $userResult['status_code'] === 201) {
            $userId = $userResult['attributes']['id'];
        } else {
            throw new Exception('Failed to create user, received error code: ' . $userResult['status_code'] . '. Enable module debug log for more info.');
        }

        $nestId = pterodactyl_GetOption($params, 'nest_id');
        $eggId = pterodactyl_GetOption($params, 'egg_id');

        // TODO: Replace with /eggs/{id} when fixed
        $eggs = pterodactyl_API($params, 'nests/' . $nestId . '/eggs');
        if($eggs['status_code'] !== 200) throw new Exception('Failed to get eggs, received error code: ' . $eggs['status_code'] . '. Enable module debug log for more info.');

        foreach($eggs['data'] as $key => $val) {
            if($val['attributes']['id'] == $eggId) {
                $egg = $val;
                break;
            }
        }

        if(!$egg) throw new Exception('Failed to find correct egg.');

        $memory = pterodactyl_GetOption($params, 'memory');
        $swap = pterodactyl_GetOption($params, 'swap');
        $io = pterodactyl_GetOption($params, 'io');
        $cpu = pterodactyl_GetOption($params, 'cpu');
        $disk = pterodactyl_GetOption($params, 'disk');
        $pack_id = pterodactyl_GetOption($params, 'pack_id');
        $location_id = pterodactyl_GetOption($params, 'location_id');
        $image = pterodactyl_GetOption($params, 'image', $egg['attributes']['docker_image']);
        $startup = pterodactyl_GetOption($params, 'startup', $egg['attributes']['startup']);
        $serverData = [
            'name' => pterodactyl_GenerateUsername(),
            'user' => (int) $userId,
            'nest' => (int) $nestId,
            'egg' => (int) $eggId,
            'docker_image' => $image,
            'startup' => $startup,
            'limits' => [
                'memory' => (int) $memory,
                'swap' => (int) $swap,
                'io' => (int) $io,
                'cpu' => (int) $cpu,
                'disk' => (int) $disk,
            ],
            'deploy' => [
                'locations' => [(int) $location_id],
                'dedicated_ip' => false,
                'port_range' => [],
            ],
            'environment' => [ // TODO: Figure out how to get environment variables from API
                'BUNGEE_VERSION' => '1',
                'SERVER_JARFILE' => 'latest.jar',
            ],
            'start_on_completion' => true,
            'external_id' => $params['serviceid'],
        ];
        if(isset($pack_id)) $serverData['pack'] = (int) $pack_id;

        $server = pterodactyl_API($params, 'servers', $serverData, 'POST');

        if($server['status_code'] !== 201) throw new Exception('Failed to create the server, received the error code: ' . $server['status_code'] . '. Enable module debug log for more info.');
    } catch(Exception $err) {
        return $err->getMessage();
    }

    return 'success';
}

function pterodactyl_SuspendAccount() {
    try {

    } catch(Exception $err) {

    }
}

function pterodactyl_UnsuspendAccount() {
    try {

    } catch(Exception $err) {

    }
}

function pterodactyl_TerminateAccount() {
    try {

    } catch(Exception $err) {

    }
}

function pterodactyl_ChangePassword() {
    try {

    } catch(Exception $err) {

    }
}

function pterodactyl_ChangePackage() {
    try {

    } catch(Exception $err) {

    }
}

function pterodactyl_ClientArea() {
    try {

    } catch(Exception $err) {

    }
}

function pterodactyl_AdminArea() {

}

function pterodactyl_LoginLink() {

}