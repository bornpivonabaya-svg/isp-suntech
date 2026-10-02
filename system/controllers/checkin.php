<?php

/**
 *  SunTech ISP - router check-in (public)
 *
 *  Called by the Mikrotik scheduler created by the router setup script:
 *  /tool fetch url=".../index.php?_route=checkin/<token>"
 *  The request source address becomes the router IP used for the API.
 **/

header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');

$token = preg_replace('~[^a-f0-9]~', '', strtolower($routes['1']));
$router = strlen($token) == 32 ? ORM::for_table('tbl_routers')->where('api_token', $token)->find_one() : null;
if (!$router) {
    http_response_code(404);
    die('unknown router');
}

$ip = $_SERVER['REMOTE_ADDR'];
// keep a custom API port if one was set
$parts = explode(':', $router['ip_address']);
$newAddress = $ip . (!empty($parts[1]) && $parts[1] != '8728' ? ':' . $parts[1] : '');

if ($router['ip_address'] != $newAddress) {
    $dup = ORM::for_table('tbl_routers')->where('ip_address', $newAddress)->where_not_equal('id', $router['id'])->find_one();
    if ($dup) {
        // two routers behind the same NAT address: keep the old value, the admin must use a VPN
        _log('Router ' . $router['name'] . ' check-in from ' . $ip . ' ignored, address used by ' . $dup['name'], 'Admin', 0);
        http_response_code(409);
        die('address in use by another router');
    }
    _log('Router ' . $router['name'] . ' address set to ' . $newAddress . ' by check-in', 'Admin', 0);
    $router->ip_address = $newAddress;
}
$router->status = 'Online';
$router->last_seen = date('Y-m-d H:i:s');
$router->save();
echo 'ok';
