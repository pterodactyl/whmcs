<?php

/**
MIT License

Copyright (c) 2018-2019 Stepan Fedotov <stepan@crident.com>

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

use Illuminate\Database\Capsule\Manager as Capsule;

function pterodactyl_GetHostname(array $params) {
    $hostname = $params['serverhostname'];
    if ($hostname === '') throw new Exception('Could not find the panel\'s hostname - did you configure server group for the product?');

    // For whatever reason, WHMCS converts some characters of the hostname to their literal meanings (- => dash, etc) in some cases
    foreach([
        'DOT' => '.',
        'DASH' => '-',
    ] as $from => $to) {
        $hostname = str_replace($from, $to, $hostname);
    }

    if(ip2long($hostname) !== false) $hostname = 'http://' . $hostname;
    else $hostname = ($params['serversecure'] ? 'https://' : 'http://') . $hostname;

    // Append port and return
    return rtrim($hostname, '/') . ':' . $params['serverport'];
}

function pterodactyl_API(array $params, $endpoint, array $data = [], $method = "GET", $dontLog = false) {
    $url = pterodactyl_GetHostname($params) . '/api/application/' . $endpoint;

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($curl, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
    curl_setopt($curl, CURLOPT_USERAGENT, "Pterodactyl-WHMCS");
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($curl, CURLOPT_POSTREDIR, CURL_REDIR_POST_301);
    curl_setopt($curl, CURLOPT_TIMEOUT, 5);

    $headers = [
        "Authorization: Bearer " . $params['serverpassword'],
        "Accept: Application/vnd.pterodactyl.v1+json",
    ];

    if($method === 'POST' || $method === 'PATCH') {
        $jsonData = json_encode($data);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $jsonData);
        array_push($headers, "Content-Type: application/json");
        array_push($headers, "Content-Length: " . strlen($jsonData));
    }

    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($curl);
    $responseData = json_decode($response, true);
    $responseData['status_code'] = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    
    if($responseData['status_code'] === 0 && !$dontLog) logModuleCall("Pterodactyl-WHMCS", "CURL ERROR", curl_error($curl), "");

    curl_close($curl);

    if(!$dontLog) logModuleCall("Pterodactyl-WHMCS", $method . " - " . $url,
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
        'DefaultNonSSLPort' => '80',
        'DefaultSSLPort' => '443',
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
        "dedicated_ip" => [
            "FriendlyName" => "Dedicated IP",
            "Description" => "Assign dedicated ip to the server (optional)",
            "Type" => "yesno",
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
            "Description" => "Block IO Adjustment number (10-1000)",
            "Type" => "text",
            "Size" => 10,
            "Default" => "500",
        ],
        "pack_id" => [
            "FriendlyName" => "Pack ID",
            "Description" => "ID of the Pack to install the server with (optional) [UNUSED, LEFT FOR COMPATIBILITY REASONS]",
            "Type" => "text",
            "Size" => 10,
        ],
        "port_range" => [
            "FriendlyName" => "Port Range",
            "Description" => "Port ranges seperated by comma to assign to the server (Example: 25565-25570,25580-25590) (optional)",
            "Type" => "text",
            "Size" => 25,
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
        "databases" => [
            "FriendlyName" => "Databases",
            "Description" => "Client will be able to create this amount of databases for their server (optional)",
            "Type" => "text",
            "Size" => 10,
        ],
    	"server_name" => [
            "FriendlyName" => "Server Name",
            "Description" => "The name of the server as shown on the panel (optional)",
            "Type" => "text",
            "Size" => 25,
        ],
        "oom_disabled" => [
            "FriendlyName" => "Disable OOM Killer",
            "Description" => "Should the Out Of Memory Killer be disabled (optional)",
            "Type" => "yesno",
        ],
        "backups" => [
            "FriendlyName" => "Backups",
            "Description" => "Client will be able to create this amount of backups for their server (optional)",
            "Type" => "text",
            "Size" => 10,
        ],
        "allocations" => [
            "FriendlyName" => "Allocations",
            "Description" => "Client will be able to create this amount of allocations for their server (optional)",
            "Type" => "text",
            "Size" => 10,
        ],
    ];
}

function pterodactyl_TestConnection(array $params) {
    $solutions = [
        0 => "Check module debug log for more detailed error.",
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

function random($length) {
    if (class_exists("\Illuminate\Support\Str")) {
        return \Illuminate\Support\Str::random($length);
    } else if (function_exists("str_random")) {
        return str_random($length);
    } else {
        throw new \Exception("Unable to find a valid function for generating random strings");
    }
}

function pterodactyl_GenerateUsername($length = 8) {
    $returnable = false;
    while (!$returnable) {
        $generated = random($length);
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
    } else if(isset($params['configoptions'][$id]) && $params['configoptions'][$id] !== '') {
        return $params['configoptions'][$id];
    } else if(isset($params['customfields'][$friendlyName]) && $params['customfields'][$friendlyName] !== '') {
        return $params['customfields'][$friendlyName];
    } else if(isset($params['customfields'][$id]) && $params['customfields'][$id] !== '') {
        return $params['customfields'][$id];
    }

    $found = false;
    $i = 0;
    foreach(pterodactyl_ConfigOptions() as $key => $value) {
        $i++;
        if($key === $id) {
            $found = true;
            break;
        }
    }

    if($found && isset($params['configoption' . $i]) && $params['configoption' . $i] !== '') {
        return $params['configoption' . $i];
    }

    return $default;
}

function pterodactyl_CreateAccount(array $params) {
    try {
        $serverId = pterodactyl_GetServerID($params);
        if(isset($serverId)) throw new Exception('Failed to create server because it is already created.');

        $userResult = pterodactyl_API($params, 'users/external/' . $params['clientsdetails']['id']);
        if($userResult['status_code'] === 404) {
            $userResult = pterodactyl_API($params, 'users?filter[email]=' . urlencode($params['clientsdetails']['email']));
            if($userResult['meta']['pagination']['total'] === 0) {
                $userResult = pterodactyl_API($params, 'users', [
                    'username' => pterodactyl_GetOption($params, 'username', pterodactyl_GenerateUsername()),
                    'email' => $params['clientsdetails']['email'],
                    'first_name' => $params['clientsdetails']['firstname'],
                    'last_name' => $params['clientsdetails']['lastname'],
                    'external_id' => (string) $params['clientsdetails']['id'],
                ], 'POST');
            } else {
                foreach($userResult['data'] as $key => $value) {
                    if($value['attributes']['email'] === $params['clientsdetails']['email']) {
                        $userResult = array_merge($userResult, $value);
                        break;
                    }
                }
                $userResult = array_merge($userResult, $userResult['data'][0]);
            }
        }

        if($userResult['status_code'] === 200 || $userResult['status_code'] === 201) {
            $userId = $userResult['attributes']['id'];
        } else {
            throw new Exception('Failed to create user, received error code: ' . $userResult['status_code'] . '. Enable module debug log for more info.');
        }

        $nestId = pterodactyl_GetOption($params, 'nest_id');
        $eggId = pterodactyl_GetOption($params, 'egg_id');

        $eggData = pterodactyl_API($params, 'nests/' . $nestId . '/eggs/' . $eggId . '?include=variables');
        if($eggData['status_code'] !== 200) throw new Exception('Failed to get egg data, received error code: ' . $eggData['status_code'] . '. Enable module debug log for more info.');

        $environment = [];
        foreach($eggData['attributes']['relationships']['variables']['data'] as $key => $val) {
            $attr = $val['attributes'];
            $var = $attr['env_variable'];
            $default = $attr['default_value'];
            $friendlyName = pterodactyl_GetOption($params, $attr['name']);
            $envName = pterodactyl_GetOption($params, $attr['env_variable']);

            if(isset($friendlyName)) $environment[$var] = $friendlyName;
            elseif(isset($envName)) $environment[$var] = $envName;
            else $environment[$var] = $default;
        }

        $name = pterodactyl_GetOption($params, 'server_name', pterodactyl_GenerateUsername() . '_' . $params['serviceid']);
        $memory = pterodactyl_GetOption($params, 'memory');
        $swap = pterodactyl_GetOption($params, 'swap');
        $io = pterodactyl_GetOption($params, 'io');
        $cpu = pterodactyl_GetOption($params, 'cpu');
        $disk = pterodactyl_GetOption($params, 'disk');
        $location_id = pterodactyl_GetOption($params, 'location_id');
        $dedicated_ip = pterodactyl_GetOption($params, 'dedicated_ip') ? true : false;
        $port_range = pterodactyl_GetOption($params, 'port_range');
        $port_range = isset($port_range) ? explode(',', $port_range) : [];
        $image = pterodactyl_GetOption($params, 'image', $eggData['attributes']['docker_image']);
        $startup = pterodactyl_GetOption($params, 'startup', $eggData['attributes']['startup']);
        $databases = pterodactyl_GetOption($params, 'databases');
        $allocations = pterodactyl_GetOption($params, 'allocations');
        $backups = pterodactyl_GetOption($params, 'backups');
        $oom_disabled = pterodactyl_GetOption($params, 'oom_disabled') ? true : false;
        $serverData = [
            'name' => $name,
            'user' => (int) $userId,
            'nest' => (int) $nestId,
            'egg' => (int) $eggId,
            'docker_image' => $image,
            'startup' => $startup,
            'oom_disabled' => $oom_disabled,
            'limits' => [
                'memory' => (int) $memory,
                'swap' => (int) $swap,
                'io' => (int) $io,
                'cpu' => (int) $cpu,
                'disk' => (int) $disk,
            ],
            'feature_limits' => [
                'databases' => $databases ? (int) $databases : null,
                'allocations' => (int) $allocations,
                'backups' => (int) $backups,
            ],
            'deploy' => [
                'locations' => [(int) $location_id],
                'dedicated_ip' => $dedicated_ip,
                'port_range' => $port_range,
            ],
            'environment' => $environment,
            'start_on_completion' => true,
            'external_id' => (string) $params['serviceid'],
        ];

        $server = pterodactyl_API($params, 'servers?include=allocations', $serverData, 'POST');

        if($server['status_code'] === 400) throw new Exception('Couldn\'t find any nodes satisfying the request.');
        if($server['status_code'] !== 201) throw new Exception('Failed to create the server, received the error code: ' . $server['status_code'] . '. Enable module debug log for more info.');

        unset($params['password']);

        // Get IP & Port and set on WHMCS "Dedicated IP" field
        $_IP = $server['attributes']['relationships']['allocations']['data'][0]['attributes']['ip'];
        $_Port = $server['attributes']['relationships']['allocations']['data'][0]['attributes']['port'];
        
        // Check if IP & Port field have value. Prevents ":" being added if API error
        if (isset($_IP) && isset($_Port)) {
        try {
			$query = Capsule::table('tblhosting')->where('id', $params['serviceid'])->where('userid', $params['userid'])->update(array('dedicatedip' => $_IP . ":" . $_Port));
		} catch (Exception $e) { return $e->getMessage() . "<br />" . $e->getTraceAsString(); }
    }

        Capsule::table('tblhosting')->where('id', $params['serviceid'])->update([
            'username' => '',
            'password' => '',
        ]);
    } catch(Exception $err) {
        return $err->getMessage();
    }

    return 'success';
}

// Function to allow backwards compatibility with death-droid's module
function pterodactyl_GetServerID(array $params, $raw = false) {
    $serverResult = pterodactyl_API($params, 'servers/external/' . $params['serviceid'], [], 'GET', true);
    if($serverResult['status_code'] === 200) {
        if($raw) return $serverResult;
        else return $serverResult['attributes']['id'];
    } else if($serverResult['status_code'] === 500) {
        throw new Exception('Failed to get server, panel errored. Check panel logs for more info.');
    }

    if(Capsule::schema()->hasTable('tbl_pterodactylproduct')) {
        $oldData = Capsule::table('tbl_pterodactylproduct')
            ->select('user_id', 'server_id')
            ->where('service_id', '=', $params['serviceid'])
            ->first();

        if(isset($oldData) && isset($oldData->server_id)) {
            if($raw) {
                $serverResult = pterodactyl_API($params, 'servers/' . $oldData->server_id);
                if($serverResult['status_code'] === 200) return $serverResult;
                else throw new Exception('Failed to get server, received the error code: ' . $serverResult['status_code'] . '. Enable module debug log for more info.');
            } else {
                return $oldData->server_id;
            }
        }
    }
}

function pterodactyl_SuspendAccount(array $params) {
    try {
        $serverId = pterodactyl_GetServerID($params);
        if(!isset($serverId)) throw new Exception('Failed to suspend server because it doesn\'t exist.');

        $suspendResult = pterodactyl_API($params, 'servers/' . $serverId . '/suspend', [], 'POST');
        if($suspendResult['status_code'] !== 204) throw new Exception('Failed to suspend the server, received error code: ' . $suspendResult['status_code'] . '. Enable module debug log for more info.');
    } catch(Exception $err) {
        return $err->getMessage();
    }

    return 'success';
}

function pterodactyl_UnsuspendAccount(array $params) {
    try {
        $serverId = pterodactyl_GetServerID($params);
        if(!isset($serverId)) throw new Exception('Failed to unsuspend server because it doesn\'t exist.');

        $suspendResult = pterodactyl_API($params, 'servers/' . $serverId . '/unsuspend', [], 'POST');
        if($suspendResult['status_code'] !== 204) throw new Exception('Failed to unsuspend the server, received error code: ' . $suspendResult['status_code'] . '. Enable module debug log for more info.');
    } catch(Exception $err) {
        return $err->getMessage();
    }

    return 'success';
}

function pterodactyl_TerminateAccount(array $params) {
    try {
        $serverId = pterodactyl_GetServerID($params);
        if(!isset($serverId)) throw new Exception('Failed to terminate server because it doesn\'t exist.');

        $deleteResult = pterodactyl_API($params, 'servers/' . $serverId, [], 'DELETE');
        if($deleteResult['status_code'] !== 204) throw new Exception('Failed to terminate the server, received error code: ' . $deleteResult['status_code'] . '. Enable module debug log for more info.');
    } catch(Exception $err) {
        return $err->getMessage();
    }

    // Remove the "Dedicated IP" Field on Termination
    try {
        $query = Capsule::table('tblhosting')->where('id', $params['serviceid'])->where('userid', $params['userid'])->update(array('dedicatedip' => ""));
    } catch (Exception $e) { return $e->getMessage() . "<br />" . $e->getTraceAsString(); }

    return 'success';
}

function pterodactyl_ChangePassword(array $params) {
    try {
        if($params['password'] === '') throw new Exception('The password cannot be empty.');

        $serverData = pterodactyl_GetServerID($params, true);
        if(!isset($serverData)) throw new Exception('Failed to change password because linked server doesn\'t exist.');

        $userId = $serverData['attributes']['user'];
        $userResult = pterodactyl_API($params, 'users/' . $userId);
        if($userResult['status_code'] !== 200) throw new Exception('Failed to retrieve user, received error code: ' . $userResult['status_code'] . '.');

        $updateResult = pterodactyl_API($params, 'users/' . $serverData['attributes']['user'], [
            'username' => $userResult['attributes']['username'],
            'email' => $userResult['attributes']['email'],
            'first_name' => $userResult['attributes']['first_name'],
            'last_name' => $userResult['attributes']['last_name'],

            'password' => $params['password'],
        ], 'PATCH');
        if($updateResult['status_code'] !== 200) throw new Exception('Failed to change password, received error code: ' . $updateResult['status_code'] . '.');

        unset($params['password']);
        Capsule::table('tblhosting')->where('id', $params['serviceid'])->update([
            'username' => '',
            'password' => '',
        ]);
    } catch(Exception $err) {
        return $err->getMessage();
    }

    return 'success';
}

function pterodactyl_ChangePackage(array $params) {
    try {
        $serverData = pterodactyl_GetServerID($params, true);
        if($serverData['status_code'] === 404 || !isset($serverData['attributes']['id'])) throw new Exception('Failed to change package of server because it doesn\'t exist.');
        $serverId = $serverData['attributes']['id'];

        $memory = pterodactyl_GetOption($params, 'memory');
        $swap = pterodactyl_GetOption($params, 'swap');
        $io = pterodactyl_GetOption($params, 'io');
        $cpu = pterodactyl_GetOption($params, 'cpu');
        $disk = pterodactyl_GetOption($params, 'disk');
        $databases = pterodactyl_GetOption($params, 'databases');
        $allocations = pterodactyl_GetOption($params, 'allocations');
        $backups = pterodactyl_GetOption($params, 'backups');
        $oom_disabled = pterodactyl_GetOption($params, 'oom_disabled') ? true : false;
        $updateData = [
            'allocation' => $serverData['attributes']['allocation'],
            'memory' => (int) $memory,
            'swap' => (int) $swap,
            'io' => (int) $io,
            'cpu' => (int) $cpu,
            'disk' => (int) $disk,
            'oom_disabled' => $oom_disabled,
            'feature_limits' => [
                'databases' => (int) $databases,
                'allocations' => (int) $allocations,
                'backups' => (int) $backups,
            ],
        ];

        $updateResult = pterodactyl_API($params, 'servers/' . $serverId . '/build', $updateData, 'PATCH');
        if($updateResult['status_code'] !== 200) throw new Exception('Failed to update build of the server, received error code: ' . $updateResult['status_code'] . '. Enable module debug log for more info.');

        $nestId = pterodactyl_GetOption($params, 'nest_id');
        $eggId = pterodactyl_GetOption($params, 'egg_id');
        $eggData = pterodactyl_API($params, 'nests/' . $nestId . '/eggs/' . $eggId . '?include=variables');
        if($eggData['status_code'] !== 200) throw new Exception('Failed to get egg data, received error code: ' . $eggData['status_code'] . '. Enable module debug log for more info.');

        $environment = [];
        foreach($eggData['attributes']['relationships']['variables']['data'] as $key => $val) {
            $attr = $val['attributes'];
            $var = $attr['env_variable'];
            $friendlyName = pterodactyl_GetOption($params, $attr['name']);
            $envName = pterodactyl_GetOption($params, $attr['env_variable']);

            if(isset($friendlyName)) $environment[$var] = $friendlyName;
            elseif(isset($envName)) $environment[$var] = $envName;
            elseif(isset($serverData['attributes']['container']['environment'][$var])) $environment[$var] = $serverData['attributes']['container']['environment'][$var];
            elseif(isset($attr['default_value'])) $environment[$var] = $attr['default_value'];
        }

        $image = pterodactyl_GetOption($params, 'image', $serverData['attributes']['container']['image']);
        $startup = pterodactyl_GetOption($params, 'startup', $serverData['attributes']['container']['startup_command']);
        $updateData = [
            'environment' => $environment,
            'startup' => $startup,
            'egg' => (int) $eggId,
            'image' => $image,
            'skip_scripts' => false,
        ];

        $updateResult = pterodactyl_API($params, 'servers/' . $serverId . '/startup', $updateData, 'PATCH');
        if($updateResult['status_code'] !== 200) throw new Exception('Failed to update startup of the server, received error code: ' . $updateResult['status_code'] . '. Enable module debug log for more info.');
    } catch(Exception $err) {
        return $err->getMessage();
    }

    return 'success';
}

function pterodactyl_LoginLink(array $params) {
    if($params['moduletype'] !== 'pterodactyl') return;

    try {
        $serverId = pterodactyl_GetServerID($params);
        if(!isset($serverId)) return;

        $hostname = pterodactyl_GetHostname($params);
        echo '<a style="padding-right:3px" href="'.$hostname.'/admin/servers/view/' . $serverId . '" target="_blank">[Go to Service]</a>';
        echo '<p style="float:right; padding-right:1.3%">[<a href="https://github.com/pterodactyl/whmcs/issues" target="_blank">Report A Bug</a>]</p>';
        # echo '<p style="float: right">[<a href="https://github.com/pterodactyl/whmcs/issues" target="_blank">Report A Bug</a>]</p>';
    } catch(Exception $err) {
        // Ignore
    }
}

function pterodactyl_ClientArea(array $params) {
    if($params['moduletype'] !== 'pterodactyl') return;

    try {
        $hostname = pterodactyl_GetHostname($params);
        $serverData = pterodactyl_GetServerID($params, true);
        if($serverData['status_code'] === 404 || !isset($serverData['attributes']['id'])) return [
            'templatefile' => 'clientarea',
            'vars' => [
                'serviceurl' => $hostname,
            ],
        ];

        return [
            'templatefile' => 'clientarea',
            'vars' => [
                'serviceurl' => $hostname . '/server/' . $serverData['attributes']['identifier'],
            ],
        ];
    } catch (Exception $err) {
        // Ignore
    }
}
