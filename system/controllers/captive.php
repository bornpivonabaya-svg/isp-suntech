<?php

/**
 *  SunTech ISP - Captive Portal
 *
 *  Admin:  captive (settings + preview), captive/preview, captive/download/<router>,
 *          captive/script/<router>, captive/push/<router>
 *  Public: captive/plans/<router>, captive/voucher/<router>, captive/file/<router>/<file>
 *          (called by the hotspot login page and by /tool fetch on the router)
 **/

$action = $routes['1'] ?: 'settings';

function captive_json($data, $code = 200)
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Cache-Control: no-store');
    echo json_encode($data);
    die();
}

function captive_router($id)
{
    $r = ORM::for_table('tbl_routers')->where('id', (int) $id)->where('enabled', 1)->find_one();
    return $r ? $r : null;
}

function captive_human_validity($p)
{
    $unit = ['Mins' => 'minute', 'Hrs' => 'hour', 'Days' => 'day', 'Months' => 'month', 'Period' => 'month'];
    $u = isset($unit[$p['validity_unit']]) ? $unit[$p['validity_unit']] : $p['validity_unit'];
    return $p['validity'] . ' ' . $u . ($p['validity'] == 1 ? '' : 's');
}

// brute-force guard for the public voucher endpoint
function captive_throttle($ip, $failed = false)
{
    global $CACHE_PATH;
    $file = $CACHE_PATH . DIRECTORY_SEPARATOR . 'captive_' . md5($ip) . '.json';
    $d = file_exists($file) ? json_decode(file_get_contents($file), true) : null;
    if (!$d || $d['since'] < time() - 600) {
        $d = ['since' => time(), 'fails' => 0];
    }
    if ($failed) {
        $d['fails']++;
        file_put_contents($file, json_encode($d));
    }
    return $d['fails'] >= 10;
}

switch ($action) {
    /* ---------------- public endpoints ---------------- */

    case 'plans':
        $router = captive_router($routes['2']);
        if (!$router) {
            captive_json(['ok' => false, 'plans' => []], 404);
        }
        $plans = ORM::for_table('tbl_plans')
            ->where('type', 'Hotspot')->where('enabled', 1)->where('prepaid', 'yes')
            ->where('routers', $router['name'])->where_not_equal('plan_type', 'Business')
            ->order_by_expr('CAST(price AS DECIMAL(15,2)) ASC')->find_array();
        $out = [];
        foreach ($plans as $p) {
            $limit = '';
            if ($p['typebp'] == 'Limited') {
                if (in_array($p['limit_type'], ['Data_Limit', 'Both_Limit'])) {
                    $limit = $p['data_limit'] . ' ' . $p['data_unit'];
                }
                if (in_array($p['limit_type'], ['Time_Limit', 'Both_Limit'])) {
                    $limit .= ($limit ? ' / ' : '') . $p['time_limit'] . ' ' . $p['time_unit'];
                }
            } else {
                $limit = 'Unlimited';
            }
            $out[] = [
                'id' => (int) $p['id'],
                'name' => $p['name_plan'],
                'price' => Lang::moneyFormat($p['price']),
                'validity' => captive_human_validity($p),
                'limit' => $limit,
            ];
        }
        captive_json(['ok' => true, 'plans' => $out]);
        break;

    case 'voucher':
        $ip = $_SERVER['REMOTE_ADDR'];
        if (captive_throttle($ip)) {
            captive_json(['ok' => false, 'message' => 'Too many wrong vouchers, wait 10 minutes and try again.'], 429);
        }
        $router = captive_router($routes['2']);
        $code = Text::alphanumeric(_post('code'), '-_.,');
        if (!$router || $code == '') {
            captive_json(['ok' => false, 'message' => 'Enter a voucher code.'], 400);
        }
        try {
            // already activated voucher: make sure the router has it, then let the customer in
            $tur = ORM::for_table('tbl_user_recharges')->where('username', $code)->where('customer_id', '0')->find_one();
            if ($tur) {
                if ($tur['status'] != 'on') {
                    captive_json(['ok' => false, 'message' => Lang::T('Internet Voucher Expired')]);
                }
                $p = ORM::for_table('tbl_plans')->find_one($tur['plan_id']);
                if ($p && $tur['routers'] == $router['name']) {
                    $dvc = Package::getDevice($p);
                    try {
                        if (file_exists($dvc)) {
                            require_once $dvc;
                            (new $p['device'])->add_customer(['fullname' => 'Voucher', 'email' => '', 'username' => $code, 'password' => $code], $p);
                        }
                    } catch (Throwable $e) {
                        // router may still have the user from the first activation, let it decide
                        _log('Captive portal re-push of voucher ' . $code . ' failed: ' . $e->getMessage(), 'Customer', 0);
                    }
                }
                captive_json(['ok' => true, 'username' => $code, 'password' => $code, 'message' => 'Welcome back, connecting...']);
            }

            $v = ORM::for_table('tbl_voucher')->where_raw('BINARY code = ?', [$code])->find_one();
            if (!$v || $v['status'] != 0) {
                captive_throttle($ip, true);
                captive_json(['ok' => false, 'message' => $v ? 'This voucher has already been used.' : Lang::T('Voucher invalid')]);
            }
            if ($v['routers'] != $router['name'] && $v['routers'] != 'radius') {
                captive_throttle($ip, true);
                captive_json(['ok' => false, 'message' => 'This voucher is not valid on this hotspot.']);
            }
            if (!Package::rechargeUser(0, $v['routers'], $v['id_plan'], 'Voucher', $code)) {
                captive_json(['ok' => false, 'message' => 'Voucher could not be activated, please contact support.']);
            }
            $v->status = '1';
            $v->used_date = date('Y-m-d H:i:s');
            $v->save();
            _log('Voucher ' . $code . ' activated from captive portal ' . $router['name'] . ' mac ' . _post('mac'), 'Customer', 0);
            captive_json(['ok' => true, 'username' => $code, 'password' => $code, 'message' => 'Voucher activated, connecting...']);
        } catch (Throwable $e) {
            captive_json(['ok' => false, 'message' => 'Activation failed: ' . $e->getMessage()], 500);
        }
        break;

    case 'stk':
        $router = captive_router($routes['2']);
        $plan_id = (int) _post('plan_id');
        $phone_raw = _post('phone');
        if (!$router || !$plan_id) {
            captive_json(['ok' => false, 'message' => 'Invalid router or package selected.'], 400);
        }
        $plan = ORM::for_table('tbl_plans')->where('id', $plan_id)->where('enabled', 1)->find_one();
        if (!$plan) {
            captive_json(['ok' => false, 'message' => 'Selected package was not found.'], 404);
        }
        if (!class_exists('Mpesa') || !Mpesa::isConfigured()) {
            captive_json(['ok' => false, 'message' => 'M-Pesa STK push is currently not configured on this server.'], 503);
        }
        $phone = Mpesa::normalizePhone($phone_raw);
        if (!$phone) {
            captive_json(['ok' => false, 'message' => 'Enter a valid Safaricom phone number (e.g. 0712345678).'], 400);
        }
        try {
            // Generate a unique voucher code
            $code = 'ST' . strtoupper(substr(md5(uniqid((string) mt_rand(), true)), 0, 6));

            $trx = ORM::for_table('tbl_payment_gateway')->create();
            $trx->username = $code;
            $trx->user_id = 0;
            $trx->gateway = 'mpesa';
            $trx->plan_id = $plan['id'];
            $trx->plan_name = $plan['name_plan'];
            $trx->routers_id = $router['id'];
            $trx->routers = $router['name'];
            $trx->price = $plan['price'];
            $trx->payment_method = 'M-Pesa';
            $trx->payment_channel = $phone;
            $trx->created_date = date('Y-m-d H:i:s');
            $trx->expired_date = date('Y-m-d H:i:s', strtotime('+1 hour'));
            $trx->status = 1; // Unpaid

            $push = Mpesa::stkPush($phone, $plan['price'], Mpesa::config()['account_ref'], $plan['name_plan']);
            $trx->gateway_trx_id = $push['CheckoutRequestID'] ?? '';
            $trx->pg_request = json_encode($push);
            $trx->save();

            captive_json([
                'ok' => true,
                'trx_id' => (int) $trx->id(),
                'code' => $code,
                'message' => 'M-Pesa prompt sent to ' . $phone . '. Please enter your PIN on your phone.'
            ]);
        } catch (Throwable $e) {
            captive_json(['ok' => false, 'message' => 'M-Pesa error: ' . $e->getMessage()], 500);
        }
        break;

    case 'check_stk':
        $trx_id = (int) _get('trx_id');
        $trx = ORM::for_table('tbl_payment_gateway')->find_one($trx_id);
        if (!$trx) {
            captive_json(['ok' => false, 'message' => 'Transaction not found.'], 404);
        }
        if ($trx['status'] == 2) {
            captive_json([
                'ok' => true,
                'paid' => true,
                'username' => $trx['username'],
                'password' => $trx['username'],
                'message' => 'Payment received! Connecting you to internet...'
            ]);
        }
        if (!empty($trx['gateway_trx_id']) && class_exists('Mpesa')) {
            try {
                $q = Mpesa::stkQuery($trx['gateway_trx_id']);
                if ($q['state'] == 'paid') {
                    $trx->status = 2;
                    $trx->paid_date = date('Y-m-d H:i:s');
                    $trx->save();

                    Package::rechargeUser(0, $trx['routers'], $trx['plan_id'], 'M-Pesa', $trx['username']);

                    captive_json([
                        'ok' => true,
                        'paid' => true,
                        'username' => $trx['username'],
                        'password' => $trx['username'],
                        'message' => 'Payment received! Connecting you to internet...'
                    ]);
                } elseif ($q['state'] == 'failed') {
                    $trx->status = 3;
                    $trx->save();
                    captive_json([
                        'ok' => false,
                        'failed' => true,
                        'message' => Mpesa::resultMessage($q['code'], $q['message'])
                    ]);
                }
            } catch (Throwable $e) {
                // status pending
            }
        }
        captive_json(['ok' => true, 'paid' => false, 'message' => 'Waiting for M-Pesa PIN...']);
        break;

    case 'file':
        // served to the router's /tool fetch
        $router = captive_router($routes['2']);
        $theme = _get('theme');
        $files = $router ? CaptivePortal::files($router, $theme) : [];
        $name = $routes['3'];
        if (!isset($files[$name])) {
            http_response_code(404);
            die('not found');
        }
        $types = ['html' => 'text/html; charset=utf-8', 'js' => 'application/javascript', 'png' => 'image/png'];
        header('Content-Type: ' . $types[pathinfo($name, PATHINFO_EXTENSION)]);
        echo $files[$name];
        die();

    /* ---------------- admin ---------------- */

    case 'settings':
        _admin();
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', 'dashboard');
        }
        $ui->assign('_title', Lang::T('Captive Portal'));
        $ui->assign('_system_menu', 'network');
        $ui->assign('_admin', $admin);
        $routers = ORM::for_table('tbl_routers')->where('enabled', 1)->order_by_asc('name')->find_array();
        $ui->assign('routers', $routers);
        $ui->assign('themes', CaptivePortal::$themes);
        $ui->assign('cp', CaptivePortal::settings());
        $ui->assign('local_only', CaptivePortal::serverUrlIsLocalOnly());
        // suggest LAN addresses of this PC so a real router can reach it
        $lan = [];
        foreach ((array) @gethostbynamel(gethostname()) as $ip) {
            if ($ip && strpos($ip, '127.') !== 0) {
                $lan[] = preg_replace('~//[^/]+~', '//' . $ip, APP_URL, 1);
            }
        }
        $ui->assign('lan_urls', $lan);
        $ui->assign('csrf_token', Csrf::generateAndStoreToken());
        $ui->display('admin/captive/settings.tpl');
        break;

    case 'save':
        _admin();
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', 'dashboard');
        }
        if (!Csrf::check(_post('csrf_token'))) {
            r2(getUrl('captive'), 'e', Lang::T('Invalid or Expired CSRF Token'));
        }
        $keys = ['cp_theme', 'cp_title', 'cp_tagline', 'cp_color', 'cp_color2', 'cp_phone', 'cp_whatsapp', 'cp_notice',
            'cp_server_url', 'cp_dir', 'cp_prefix', 'cp_walled_extra'];
        $checks = ['cp_show_plans', 'cp_show_voucher', 'cp_show_member', 'cp_show_buy', 'cp_block_vpn', 'cp_block_dns_tunnel', 'cp_block_protocols', 'cp_mpesa_pay'];
        $vals = [];
        foreach ($keys as $k) {
            $vals[$k] = _post($k);
        }
        foreach ($checks as $k) {
            $vals[$k] = _post($k) ? 'yes' : 'no';
        }
        $vals['cp_prefix'] = trim(preg_replace('~[^A-Za-z0-9_/-]~', '', $vals['cp_prefix']), '/');
        $vals['cp_prefix'] = $vals['cp_prefix'] ? $vals['cp_prefix'] . '/' : '';
        $vals['cp_dir'] = preg_replace('~[^A-Za-z0-9_-]~', '', $vals['cp_dir']) ?: 'suntech';
        if ($vals['cp_server_url'] != '' && !preg_match('~^https?://[^\s/]+~', $vals['cp_server_url'])) {
            r2(getUrl('captive'), 'e', Lang::T('Server URL must start with http:// or https://'));
        }
        foreach ($vals as $k => $v) {
            $d = ORM::for_table('tbl_appconfig')->where('setting', $k)->find_one();
            if (!$d) {
                $d = ORM::for_table('tbl_appconfig')->create();
                $d->setting = $k;
            }
            $d->value = $v;
            $d->save();
        }
        _log('[' . $admin['username'] . ']: Captive portal settings updated', $admin['user_type'], $admin['id']);
        r2(getUrl('captive'), 's', Lang::T('Settings Saved Successfully'));
        break;

    case 'preview':
        // captive/preview/<router>/<page>&error=..&trial=yes
        _admin();
        $router = captive_router($routes['2']) ?: ['id' => 0, 'name' => 'preview'];
        $page = in_array($routes['3'], CaptivePortal::$pages) ? $routes['3'] : 'login.html';
        $theme = _get('theme');
        $files = CaptivePortal::files($router, $theme);
        $state = [];
        if (_get('error')) {
            $state['error'] = 'invalid username or password';
        }
        if (_get('trial')) {
            $state['trial'] = 'yes';
        }
        $html = CaptivePortal::render($files[$page], CaptivePortal::sampleVars($state));
        // assets come from this server in preview; the form must not leave the preview
        $fileQs = $theme ? '?theme=' . urlencode($theme) : '';
        $html = str_replace(['src="logo.png"', 'src="md5.js"'], [
            'src="' . getUrl('captive/file/' . $router['id'] . '/logo.png' . $fileQs) . '"',
            'src="' . getUrl('captive/file/' . $router['id'] . '/md5.js' . $fileQs) . '"',
        ], $html);
        $html = str_replace('</body>', '<script>document.forms.sendin && (document.forms.sendin.onsubmit = null, document.forms.sendin.submit = function () { alert("Preview: the router would now log in user " + this.username.value); });</script></body>', $html);
        header('Content-Type: text/html; charset=utf-8');
        echo $html;
        die();

    case 'download':
        _admin();
        $router = captive_router($routes['2']);
        if (!$router) {
            r2(getUrl('captive'), 'e', Lang::T('Router not found'));
        }
        $cp = CaptivePortal::settings();
        $zip = CaptivePortal::zip(CaptivePortal::files($router), $cp['cp_dir']);
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $cp['cp_dir'] . '-' . RouterScript::slug($router['name']) . '.zip"');
        header('Content-Length: ' . strlen($zip));
        echo $zip;
        die();

    case 'script':
        _admin();
        $router = captive_router($routes['2']);
        if (!$router) {
            r2(getUrl('captive'), 'e', Lang::T('Router not found'));
        }
        header('Content-Type: text/plain; charset=utf-8');
        if (_get('download')) {
            header('Content-Disposition: attachment; filename="suntech-portal-' . RouterScript::slug($router['name']) . '.rsc"');
        }
        echo CaptivePortal::setupScript($router, (bool) _get('all'));
        die();

    case 'push':
        _admin();
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', 'dashboard');
        }
        $router = captive_router($routes['2']);
        if (!$router) {
            r2(getUrl('captive'), 'e', Lang::T('Router not found'));
        }
        if (CaptivePortal::serverUrlIsLocalOnly()) {
            r2(getUrl('captive'), 'e', Lang::T('Set Server URL to an address the router can reach (not localhost) before pushing.'));
        }
        $dir = CaptivePortal::htmlDirectory();
        try {
            $client = RouterScript::client($router);
            $log = RouterScript::apply($client, CaptivePortal::walledGardenOperations());
            foreach (array_keys(CaptivePortal::files($router)) as $f) {
                RouterScript::send($client, '/tool/fetch', ['url' => CaptivePortal::fileUrl($router, $f), 'dst-path' => $dir . '/' . $f]);
                $log[] = 'FETCH ' . $dir . '/' . $f;
            }
            if (_post('all_profiles')) {
                foreach (RouterScript::find($client, '/ip/hotspot/profile', ['default' => 'false']) as $id) {
                    RouterScript::send($client, '/ip/hotspot/profile/set', ['numbers' => $id, 'html-directory' => $dir]);
                }
                $log[] = 'SET html-directory=' . $dir . ' on hotspot profiles';
            }
            _log('[' . $admin['username'] . ']: Captive portal pushed to ' . $router['name'], $admin['user_type'], $admin['id']);
            r2(getUrl('captive'), 's', Lang::T('Portal installed on') . ' ' . htmlspecialchars($router['name']) . '<br><small>' . implode('<br>', array_map('htmlspecialchars', $log)) . '</small>');
        } catch (Throwable $e) {
            r2(getUrl('captive'), 'e', Lang::T('Push failed') . ': ' . htmlspecialchars($e->getMessage()) . '<br>' . Lang::T('Download the ZIP and upload it with Winbox instead.'));
        }
        break;

    default:
        r2(getUrl('captive'));
}
