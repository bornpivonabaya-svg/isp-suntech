<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

_admin();
$ui->assign('_title', Lang::T('Network'));
$ui->assign('_system_menu', 'network');

$action = $routes['1'];
$ui->assign('_admin', $admin);

require_once $DEVICE_PATH . DIRECTORY_SEPARATOR . "MikrotikHotspot.php";

if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
    _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
}

$leafletpickerHeader = <<<EOT
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css">
EOT;

switch ($action) {
    case 'add':
        run_hook('view_add_routers'); #HOOK
        $ui->display('admin/routers/add.tpl');
        break;

    case 'edit':
        $id  = $routes['2'];
        $d = ORM::for_table('tbl_routers')->find_one($id);
        if (!$d) {
            $d = ORM::for_table('tbl_routers')->where_equal('name', _get('name'))->find_one();
        }
        $ui->assign('xheader', $leafletpickerHeader);
        if ($d) {
            $ui->assign('d', $d);
            run_hook('view_router_edit'); #HOOK
            $ui->display('admin/routers/edit.tpl');
        } else {
            r2(getUrl('routers/list'), 'e', Lang::T('Account Not Found'));
        }
        break;

    case 'setup':
        $d = ORM::for_table('tbl_routers')->find_one($routes['2']);
        if (!$d) {
            r2(getUrl('routers/list'), 'e', Lang::T('Data Not Found'));
        }
        if (RouterScript::prepareAutoSetup($d)) {
            $d->save();
        }
        $serverUrl = CaptivePortal::settings()['cp_server_url'];
        $lan = [];
        foreach ((array) @gethostbynamel(gethostname()) as $ip) {
            if ($ip && strpos($ip, '127.') !== 0) {
                $lan[] = preg_replace('~//[^/]+~', '//' . $ip, APP_URL, 1);
            }
        }
        $ui->assign('d', $d);
        $ui->assign('server_url', $serverUrl);
        $ui->assign('lan_urls', $lan);
        $ui->assign('local_only', CaptivePortal::serverUrlIsLocalOnly());
        $ui->assign('script', RouterScript::routerSetupScript($d, $serverUrl));
        $ui->display('admin/routers/setup.tpl');
        break;

    case 'setup-status':
        header('Content-Type: application/json');
        $d = ORM::for_table('tbl_routers')->find_one($routes['2']);
        echo json_encode($d ? ['ip' => $d['ip_address'], 'status' => $d['status'], 'last_seen' => $d['last_seen']] : []);
        die();

    case 'server-url':
        // address routers use to reach this system (shared with the captive portal)
        $url = rtrim(_post('server_url'), '/');
        if (!preg_match('~^https?://[^\s/]+~', $url)) {
            r2(getUrl('routers/setup/') . _post('id'), 'e', Lang::T('Server URL must start with http:// or https://'));
        }
        $c = ORM::for_table('tbl_appconfig')->where('setting', 'cp_server_url')->find_one();
        if (!$c) {
            $c = ORM::for_table('tbl_appconfig')->create();
            $c->setting = 'cp_server_url';
        }
        $c->value = $url;
        $c->save();
        r2(getUrl('routers/setup/') . _post('id'), 's', Lang::T('Server URL saved'));
        break;

    case 'delete':
        $id  = $routes['2'];
        run_hook('router_delete'); #HOOK
        $d = ORM::for_table('tbl_routers')->find_one($id);
        if ($d) {
            $d->delete();
            r2(getUrl('routers/list'), 's', Lang::T('Data Deleted Successfully'));
        }
        break;

    case 'add-post':
        $name = _post('name');
        $ip_address = _post('ip_address');
        $username = _post('username');
        $password = _post('password');
        $description = _post('description');
        $enabled = _post('enabled');

        $msg = '';
        if (Validator::Length($name, 30, 1) == false) {
            $msg .= 'Name should be between 1 to 30 characters' . '<br>';
        }
        // no IP = automatic setup: the router registers itself with the setup script
        if ($ip_address != '') {
            if ($username == '') {
                $msg .= Lang::T('Username is required when IP Address is set') . '<br>';
            }

            $d = ORM::for_table('tbl_routers')->where('ip_address', $ip_address)->find_one();
            if ($d) {
                $msg .= Lang::T('IP Router Already Exist') . '<br>';
            }
        }
        if (ORM::for_table('tbl_routers')->where('name', $name)->find_one()) {
            $msg .= 'Name Already Exists<br>';
        }
        if (strtolower($name) == 'radius') {
            $msg .= '<b>Radius</b> name is reserved<br>';
        }

        if ($msg == '') {
            run_hook('add_router'); #HOOK
            if (_post("testIt") && $ip_address != '') {
                (new MikrotikHotspot())->getClient($ip_address, $username, $password);
            }
            $d = ORM::for_table('tbl_routers')->create();
            $d->name = $name;
            $d->ip_address = $ip_address;
            $d->username = $username;
            $d->password = $password;
            $d->description = $description;
            $d->enabled = $enabled;
            if ($ip_address == '') {
                RouterScript::prepareAutoSetup($d);
                $d->status = 'Offline';
            }
            $d->save();

            // Automatically clone default hotspot plans for this router if none exist
            $existingPlans = ORM::for_table('tbl_plans')->where('routers', $name)->count();
            if ($existingPlans == 0) {
                $srcPlans = ORM::for_table('tbl_plans')->where_not_equal('routers', $name)->find_many();
                $copiedNames = [];
                foreach ($srcPlans as $sp) {
                    if (in_array($sp['name_plan'], $copiedNames)) continue;
                    $copiedNames[] = $sp['name_plan'];
                    $np = ORM::for_table('tbl_plans')->create();
                    foreach ($sp->as_array() as $k => $v) {
                        if ($k == 'id') continue;
                        if ($k == 'routers') {
                            $np->$k = $name;
                        } else {
                            $np->$k = $v;
                        }
                    }
                    $np->save();
                }
            }

            if ($ip_address == '') {
                r2(getUrl('routers/setup/') . $d->id(), 's', Lang::T('Router added, now run the script on the Mikrotik'));
            }
            r2(getUrl('routers/edit/') . $d->id(), 's', Lang::T('Data Created Successfully'));
        } else {
            r2(getUrl('routers/add'), 'e', $msg);
        }
        break;


    case 'edit-post':
        $name = _post('name');
        $ip_address = _post('ip_address');
        $username = _post('username');
        $password = _post('password');
        $description = _post('description');
        $coordinates = _post('coordinates');
        $coverage = _post('coverage');
        $enabled = $_POST['enabled'];
        $msg = '';
        if (Validator::Length($name, 30, 4) == false) {
            $msg .= 'Name should be between 5 to 30 characters' . '<br>';
        }
        if ($ip_address != '' && $username == '') {
            $msg .= Lang::T('Username is required when IP Address is set') . '<br>';
        }

        $id = _post('id');
        $d = ORM::for_table('tbl_routers')->find_one($id);
        if ($d) {
        } else {
            $msg .= Lang::T('Data Not Found') . '<br>';
        }

        if ($d['name'] != $name) {
            $c = ORM::for_table('tbl_routers')->where('name', $name)->where_not_equal('id', $id)->find_one();
            if ($c) {
                $msg .= 'Name Already Exists<br>';
            }
        }
        $oldname = $d['name'];

        if ($ip_address != '') {
            if ($d['ip_address'] != $ip_address) {
                $c = ORM::for_table('tbl_routers')->where('ip_address', $ip_address)->where_not_equal('id', $id)->find_one();
                if ($c) {
                    $msg .= 'IP Already Exists<br>';
                }
            }
        }

        if (strtolower($name) == 'radius') {
            $msg .= '<b>Radius</b> name is reserved<br>';
        }

        if ($msg == '') {
            run_hook('router_edit'); #HOOK
            if (_post("testIt") && $ip_address != '') {
                (new MikrotikHotspot())->getClient($ip_address, $username, $password);
            }
            $d->name = $name;
            $d->ip_address = $ip_address;
            $d->username = $username;
            $d->password = $password;
            $d->description = $description;
            $d->coordinates = $coordinates;
            $d->coverage = $coverage;
            $d->enabled = $enabled;
            $d->save();
            if ($name != $oldname) {
                $p = ORM::for_table('tbl_plans')->where('routers', $oldname)->find_result_set();
                $p->set('routers', $name);
                $p->save();
                $p = ORM::for_table('tbl_payment_gateway')->where('routers', $oldname)->find_result_set();
                $p->set('routers', $name);
                $p->save();
                $p = ORM::for_table('tbl_pool')->where('routers', $oldname)->find_result_set();
                $p->set('routers', $name);
                $p->save();
                $p = ORM::for_table('tbl_transactions')->where('routers', $oldname)->find_result_set();
                $p->set('routers', $name);
                $p->save();
                $p = ORM::for_table('tbl_user_recharges')->where('routers', $oldname)->find_result_set();
                $p->set('routers', $name);
                $p->save();
                $p = ORM::for_table('tbl_voucher')->where('routers', $oldname)->find_result_set();
                $p->set('routers', $name);
                $p->save();
            }
            r2(getUrl('routers/list'), 's', Lang::T('Data Updated Successfully'));
        } else {
            r2(getUrl('routers/edit/') . $id, 'e', $msg);
        }
        break;

    default:

        $name = _post('name');
        $name = _post('name');
        $query = ORM::for_table('tbl_routers')->order_by_desc('id');
        if ($name != '') {
            $query->where_like('name', '%' . $name . '%');
        }
        $d = Paginator::findMany($query, ['name' => $name]);
        $ui->assign('d', $d);
        run_hook('view_list_routers'); #HOOK
        $ui->display('admin/routers/list.tpl');
        break;
}
