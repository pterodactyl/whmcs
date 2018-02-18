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
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer " . $params['serverpassword'],
        "Accept: Application/vnd.pterodactyl.v1+json",
    ]);

    if(isset($data)) curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));

    $response = curl_exec($curl);
    if(!$response) logModuleCall("Pterodactyl-WHMCS", "CURL ERROR", curl_error($curl), "");

    $responseData = json_decode($response, true);
    $responseData['status_code'] = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    curl_close($curl);

    logModuleCall("Pterodactyl-WHMCS", $method . " - " . $url,
        isset($data) ? print_r($data, true) : "",
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
            "FriendlyName" => "CPU Limit",
            "Type" => "text",
            "Size" => 10,
        ],
        "disk" => [
            "FriendlyName" => "Disk Space",
            "Type" => "text",
            "Size" => 10,
        ],
        "memory" => [
            "FriendlyName" => "Memory",
            "Type" => "text",
            "Size" => 10,
        ],
        "swap" => [
            "FriendlyName" => "Swap",
            "Type" => "text",
            "Size" => 10,
        ],
        "io" => [
            "FriendlyName" => "Block IO Weight",
            "Type" => "text",
            "Size" => 10,
            "Default" => "500",
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

function pterodactyl_CreateAccount() {

}

function pterodactyl_SuspendAccount() {

}

function pterodactyl_UnsuspendAccount() {

}

function pterodactyl_TerminateAccount() {

}

function pterodactyl_ChangePassword() {

}

function pterodactyl_ChangePackage() {

}

function pterodactyl_ClientArea() {

}

function pterodactyl_AdminArea() {

}

function pterodactyl_LoginLink() {

}