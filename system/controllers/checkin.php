<?php

/**
 *  SunTech ISP - router check-in (public)
 *
 *  Called by the Mikrotik scheduler created by the router setup script:
 *  /tool fetch url=".../index.php?_route=checkin/<token>"
 *  The request source address becomes the router IP used for the API.
 *
 *  When a pending captive portal update is queued (admin clicked "Push" but
 *  the router was unreachable), this endpoint responds with RouterOS commands
 *  that make the router pull the updated files itself.
 **/

header('Cache-Control: no-store');

$token = preg_replace('~[^a-f0-9]~', '', strtolower($routes['1']));
$router = strlen($token) == 32 ? ORM::for_table('tbl_routers')->where('api_token', $token)->find_one() : null;
if (!$router) {
    header('Content-Type: text/plain; charset=utf-8');
    http_response_code(404);
    die('unknown router');
}

$ip = $_SERVER['REMOTE_ADDR'];
if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
    $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
} elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
    $ip = $_SERVER['HTTP_X_REAL_IP'];
} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
    $ip = trim($ips[0]);
}
// keep a custom API port if one was set
$parts = explode(':', $router['ip_address']);
$newAddress = $ip . (!empty($parts[1]) && $parts[1] != '8728' ? ':' . $parts[1] : '');

if ($router['ip_address'] != $newAddress) {
    $dup = ORM::for_table('tbl_routers')->where('ip_address', $newAddress)->where_not_equal('id', $router['id'])->find_one();
    if ($dup) {
        // two routers behind the same NAT address: keep the old value, the admin must use a VPN
        _log('Router ' . $router['name'] . ' check-in from ' . $ip . ' ignored, address used by ' . $dup['name'], 'Admin', 0);
        header('Content-Type: text/plain; charset=utf-8');
        http_response_code(409);
        die('address in use by another router');
    }
    _log('Router ' . $router['name'] . ' address set to ' . $newAddress . ' by check-in', 'Admin', 0);
    $router->ip_address = $newAddress;
}
$router->status = 'Online';
$router->last_seen = date('Y-m-d H:i:s');

// --- Pending captive portal update (pull-based) ---
$pendingUpdate = trim($router['pending_update']);
if ($pendingUpdate === 'captive') {
    // Clear the flag first so we don't re-send on every check-in
    $router->pending_update = '';
    $router->save();

    // Respond with RouterOS script that re-downloads captive portal files
    header('Content-Type: text/plain; charset=utf-8');
    $cp = CaptivePortal::settings();
    $dir = CaptivePortal::htmlDirectory();
    $serverUrl = rtrim($cp['cp_server_url'], '/');

    $lines = [];
    $lines[] = '# SunTech ISP: captive portal update queued by admin';
    $lines[] = ':log info "SunTech ISP: downloading captive portal update"';

    // Download each portal file
    $files = array_keys(CaptivePortal::files($router));
    foreach ($files as $f) {
        $fUrl = CaptivePortal::fileUrl($router, $f);
        $checkCert = (strpos($fUrl, 'https://') === 0) ? ' check-certificate=no' : '';
        $lines[] = ':do { /tool fetch url=' . RouterScript::quote($fUrl) . $checkCert . ' dst-path=' . RouterScript::quote($dir . '/' . $f) . ' } on-error={ :log warning "SunTech ISP: failed to download ' . $f . '" }';
    }

    // Apply walled garden operations
    $wgOps = CaptivePortal::walledGardenOperations();
    if ($wgOps) {
        $lines[] = RouterScript::toScript($wgOps, 'SunTech ISP: walled garden update');
    }

    // Set html-directory on all non-default hotspot profiles
    $lines[] = ':do { /ip hotspot profile set [find default=no] html-directory=' . RouterScript::quote($dir) . ' } on-error={ }';
    $lines[] = ':log info "SunTech ISP: captive portal updated successfully"';

    echo implode("\n", $lines) . "\n";
    die();
}

$router->save();
header('Content-Type: text/plain; charset=utf-8');
echo 'ok';

