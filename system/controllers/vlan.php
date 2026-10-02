<?php

/**
 *  SunTech ISP - VLAN management
 *
 *  Each VLAN lives on one router and can carry a Hotspot (captive portal)
 *  or PPPoE service. Config can be exported as a RouterOS script or pushed
 *  through the API.
 **/

_admin();
$ui->assign('_title', Lang::T('VLANs'));
$ui->assign('_system_menu', 'network');

$action = $routes['1'] ?: 'list';
$ui->assign('_admin', $admin);

if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
    _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
}

$htmlDir = CaptivePortal::htmlDirectory();

function vlan_form_values()
{
    return [
        'router_id' => (int) _post('router_id'),
        'name' => trim(_post('name')),
        'vlan_id' => (int) _post('vlan_id'),
        'parent_interface' => trim(_post('parent_interface')),
        'service' => in_array(_post('service'), ['Hotspot', 'PPPoE', 'None']) ? _post('service') : 'Hotspot',
        'gateway' => trim(_post('gateway')),
        'pool_range' => trim(_post('pool_range')),
        'dns' => trim(_post('dns')),
        'dhcp' => _post('dhcp') ? 1 : 0,
        'dns_name' => trim(_post('dns_name')),
        'description' => trim(_post('description')),
        'enabled' => _post('enabled') ? 1 : 0,
    ];
}

function vlan_validate($v, $id = 0)
{
    $msg = '';
    if (!ORM::for_table('tbl_routers')->find_one($v['router_id'])) {
        $msg .= Lang::T('Router is required') . '<br>';
    }
    if (!preg_match('~^[A-Za-z0-9 _.-]{1,32}$~', $v['name'])) {
        $msg .= Lang::T('Name should be 1-32 characters: letters, numbers, space, dot, dash') . '<br>';
    }
    if ($v['vlan_id'] < 1 || $v['vlan_id'] > 4094) {
        $msg .= Lang::T('VLAN ID must be between 1 and 4094') . '<br>';
    }
    if (!preg_match('~^[A-Za-z0-9_./-]{1,64}$~', $v['parent_interface'])) {
        $msg .= Lang::T('Parent interface is required') . '<br>';
    }
    if (!RouterScript::parseCidr($v['gateway'])) {
        $msg .= Lang::T('Gateway must be an IP in CIDR format, example 10.10.10.1/24') . '<br>';
    }
    if ($v['pool_range'] != '' && !preg_match('~^\d{1,3}(\.\d{1,3}){3}-\d{1,3}(\.\d{1,3}){3}(,\d{1,3}(\.\d{1,3}){3}-\d{1,3}(\.\d{1,3}){3})*$~', $v['pool_range'])) {
        $msg .= Lang::T('Pool range format: 10.10.10.2-10.10.10.254') . '<br>';
    }
    if ($v['dns'] != '' && !preg_match('~^\d{1,3}(\.\d{1,3}){3}(,\d{1,3}(\.\d{1,3}){3})*$~', $v['dns'])) {
        $msg .= Lang::T('DNS format: 8.8.8.8,1.1.1.1') . '<br>';
    }
    if ($v['dns_name'] != '' && !preg_match('~^[A-Za-z0-9.-]{1,64}$~', $v['dns_name'])) {
        $msg .= Lang::T('Invalid hotspot DNS name') . '<br>';
    }
    $dup = ORM::for_table('tbl_vlans')->where('router_id', $v['router_id'])->where('vlan_id', $v['vlan_id'])->where_not_equal('id', $id)->find_one();
    if ($dup) {
        $msg .= Lang::T('This VLAN ID already exists on the router') . '<br>';
    }
    return $msg;
}

function vlan_sync($vlan, $remove = false)
{
    global $htmlDir, $_app_stage;
    $router = ORM::for_table('tbl_routers')->find_one($vlan['router_id']);
    if (!$router) {
        throw new Exception(Lang::T('Router not found'));
    }
    if ($_app_stage == 'Demo') {
        throw new Exception('Demo mode');
    }
    $ops = $remove ? RouterScript::vlanRemoveOperations($vlan) : RouterScript::vlanOperations($vlan, $htmlDir);
    $client = RouterScript::client($router);
    return RouterScript::apply($client, $ops);
}

switch ($action) {
    case 'list':
        $routers = [];
        foreach (ORM::for_table('tbl_routers')->find_array() as $r) {
            $routers[$r['id']] = $r;
        }
        $query = ORM::for_table('tbl_vlans')->order_by_asc('router_id')->order_by_asc('vlan_id');
        $router_id = (int) _req('router');
        if ($router_id) {
            $query->where('router_id', $router_id);
        }
        $d = Paginator::findMany($query, ['router' => $router_id]);
        $ui->assign('d', $d);
        $ui->assign('routers', $routers);
        $ui->assign('router_id', $router_id);
        run_hook('view_vlan'); #HOOK
        $ui->display('admin/vlan/list.tpl');
        break;

    case 'add':
    case 'edit':
        $d = null;
        if ($action == 'edit') {
            $d = ORM::for_table('tbl_vlans')->find_one($routes['2']);
            if (!$d) {
                r2(getUrl('vlan/list'), 'e', Lang::T('Data Not Found'));
            }
        }
        $ui->assign('d', $d);
        $ui->assign('routers', ORM::for_table('tbl_routers')->order_by_asc('name')->find_array());
        $ui->assign('csrf_token', Csrf::generateAndStoreToken());
        $ui->display('admin/vlan/form.tpl');
        break;

    case 'save':
        if (!Csrf::check(_post('csrf_token'))) {
            r2(getUrl('vlan/list'), 'e', Lang::T('Invalid or Expired CSRF Token'));
        }
        $id = (int) _post('id');
        $v = vlan_form_values();
        $msg = vlan_validate($v, $id);
        if ($msg != '') {
            r2(getUrl($id ? 'vlan/edit/' . $id : 'vlan/add'), 'e', $msg);
        }
        if ($id) {
            $d = ORM::for_table('tbl_vlans')->find_one($id);
            if (!$d) {
                r2(getUrl('vlan/list'), 'e', Lang::T('Data Not Found'));
            }
            $old = $d->as_array();
            // vlan id or router changed: names on the router change too, clean the old ones first
            if ($old['sync_status'] == 'synced' && ($old['vlan_id'] != $v['vlan_id'] || $old['router_id'] != $v['router_id'] || $old['name'] != $v['name'])) {
                try {
                    vlan_sync($old, true);
                } catch (Throwable $e) {
                    _log('VLAN ' . $old['vlan_id'] . ' cleanup failed: ' . $e->getMessage(), $admin['user_type'], $admin['id']);
                }
            }
        } else {
            $d = ORM::for_table('tbl_vlans')->create();
        }
        foreach ($v as $k => $val) {
            $d->$k = $val;
        }
        $d->sync_status = 'pending';
        $d->save();
        _log('[' . $admin['username'] . ']: ' . ($id ? 'Edit' : 'Add') . ' VLAN ' . $v['vlan_id'] . ' ' . $v['name'], $admin['user_type'], $admin['id']);

        if (_post('sync_now')) {
            r2(getUrl('vlan/sync/' . $d->id()), 's', Lang::T('Data Saved Successfully'));
        }
        r2(getUrl('vlan/list'), 's', Lang::T('Data Saved Successfully'));
        break;

    case 'sync':
    case 'unsync':
        $d = ORM::for_table('tbl_vlans')->find_one($routes['2']);
        if (!$d) {
            r2(getUrl('vlan/list'), 'e', Lang::T('Data Not Found'));
        }
        try {
            $log = vlan_sync($d->as_array(), $action == 'unsync');
            $d->sync_status = $action == 'unsync' ? 'pending' : 'synced';
            $d->sync_message = implode("\n", $log);
            $d->last_sync = date('Y-m-d H:i:s');
            $d->save();
            r2(getUrl('vlan/list'), 's', ($action == 'unsync' ? Lang::T('Removed from router') : Lang::T('Synced to router')) . '<br><small>' . implode('<br>', array_map('htmlspecialchars', $log)) . '</small>');
        } catch (Throwable $e) {
            $d->sync_status = 'error';
            $d->sync_message = $e->getMessage();
            $d->last_sync = date('Y-m-d H:i:s');
            $d->save();
            r2(getUrl('vlan/list'), 'e', Lang::T('Router sync failed') . ': ' . htmlspecialchars($e->getMessage()) . '<br>' . Lang::T('Use the Script button to configure the router manually.'));
        }
        break;

    case 'sync-all':
        $router_id = (int) $routes['2'];
        $rows = ORM::for_table('tbl_vlans')->where('router_id', $router_id)->find_many();
        $ok = 0;
        $fail = [];
        foreach ($rows as $d) {
            try {
                $d->sync_message = implode("\n", vlan_sync($d->as_array()));
                $d->sync_status = 'synced';
                $ok++;
            } catch (Throwable $e) {
                $d->sync_message = $e->getMessage();
                $d->sync_status = 'error';
                $fail[] = 'VLAN ' . $d['vlan_id'] . ': ' . htmlspecialchars($e->getMessage());
            }
            $d->last_sync = date('Y-m-d H:i:s');
            $d->save();
        }
        r2(getUrl('vlan/list&router=' . $router_id), $fail ? 'e' : 's', "Synced $ok VLAN(s)" . ($fail ? '<br>' . implode('<br>', $fail) : ''));
        break;

    case 'delete':
        $d = ORM::for_table('tbl_vlans')->find_one($routes['2']);
        if (!$d) {
            r2(getUrl('vlan/list'), 'e', Lang::T('Data Not Found'));
        }
        $note = '';
        if ($d['sync_status'] == 'synced') {
            try {
                vlan_sync($d->as_array(), true);
            } catch (Throwable $e) {
                $note = '<br>' . Lang::T('Could not remove it from the router') . ': ' . htmlspecialchars($e->getMessage());
            }
        }
        _log('[' . $admin['username'] . ']: Delete VLAN ' . $d['vlan_id'] . ' ' . $d['name'], $admin['user_type'], $admin['id']);
        $d->delete();
        r2(getUrl('vlan/list'), $note ? 'e' : 's', Lang::T('Data Deleted Successfully') . $note);
        break;

    case 'script':
        // script/<vlan id>  or  script/router/<router id>  ; add &download=1 to download
        if ($routes['2'] == 'router') {
            $router = ORM::for_table('tbl_routers')->find_one($routes['3']);
            $rows = ORM::for_table('tbl_vlans')->where('router_id', $routes['3'])->order_by_asc('vlan_id')->find_array();
            $title = 'SunTech ISP VLANs for router ' . ($router ? $router['name'] : '');
            $file = 'suntech-vlans-' . RouterScript::slug($router ? $router['name'] : 'router') . '.rsc';
        } else {
            $rows = ORM::for_table('tbl_vlans')->where('id', $routes['2'])->find_array();
            $title = $rows ? 'SunTech ISP VLAN ' . $rows[0]['vlan_id'] . ' ' . $rows[0]['name'] : '';
            $file = $rows ? 'suntech-vlan' . $rows[0]['vlan_id'] . '.rsc' : 'vlan.rsc';
        }
        if (!$rows) {
            r2(getUrl('vlan/list'), 'e', Lang::T('Data Not Found'));
        }
        $ops = [];
        foreach ($rows as $row) {
            $ops = array_merge($ops, RouterScript::vlanOperations($row, $htmlDir));
        }
        $script = RouterScript::toScript($ops, $title);
        if (_get('download')) {
            header('Content-Type: text/plain; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $file . '"');
            echo $script;
            die();
        }
        $ui->assign('script', $script);
        $ui->assign('file', $file);
        $ui->assign('rows', $rows);
        $ui->assign('download_url', getUrl('vlan/script/' . implode('/', array_slice($routes, 2)) . '&download=1'));
        $ui->display('admin/vlan/script.tpl');
        break;

    case 'interfaces':
        // AJAX: list interfaces of a router for the parent interface picker
        header('Content-Type: application/json');
        $router = ORM::for_table('tbl_routers')->find_one($routes['2']);
        if (!$router) {
            echo json_encode(['ok' => false, 'error' => 'Router not found']);
            die();
        }
        try {
            echo json_encode(['ok' => true, 'interfaces' => RouterScript::interfaces(RouterScript::client($router))]);
        } catch (Throwable $e) {
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
        die();

    default:
        r2(getUrl('vlan/list'));
}
