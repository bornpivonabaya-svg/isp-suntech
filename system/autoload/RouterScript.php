<?php

/**
 *  SunTech ISP - RouterOS command builder
 *
 *  Builds a list of idempotent RouterOS operations once, then either
 *  renders them as a .rsc script (copy/paste or /import) or applies them
 *  through the RouterOS API. Used by the VLAN and Captive Portal modules.
 *
 *  Each operation:
 *    ['path' => '/interface/vlan', 'key' => ['name' => 'vlan10'], 'args' => [...]]
 *    ['path' => '/ip/dns', 'set' => true, 'args' => [...]]   // singleton menu
 **/

use PEAR2\Net\RouterOS;

class RouterScript
{
    const TAG = 'suntech';

    /**
     * Parse "10.10.10.1/24" into gateway, prefix, network and default pool range.
     */
    public static function parseCidr($cidr)
    {
        if (!preg_match('~^(\d{1,3}(?:\.\d{1,3}){3})/(\d{1,2})$~', trim($cidr), $m)) {
            return false;
        }
        $ip = ip2long($m[1]);
        $prefix = (int) $m[2];
        if ($ip === false || $prefix < 8 || $prefix > 30) {
            return false;
        }
        $mask = $prefix == 0 ? 0 : (~0 << (32 - $prefix)) & 0xFFFFFFFF;
        $network = $ip & $mask;
        $broadcast = $network | (~$mask & 0xFFFFFFFF);
        if ($ip == $network || $ip == $broadcast) {
            return false;
        }
        // pool = every usable host except the gateway (gateway is usually first host)
        $first = $network + 1;
        $last = $broadcast - 1;
        if ($ip == $first) {
            $first++;
        } elseif ($ip == $last) {
            $last--;
        }
        return [
            'gateway' => long2ip($ip),
            'prefix' => $prefix,
            'network' => long2ip($network) . '/' . $prefix,
            'pool' => long2ip($first) . '-' . long2ip($last),
            'hosts' => $last - $first + 1,
        ];
    }

    public static function slug($text)
    {
        $s = strtolower(preg_replace('~[^A-Za-z0-9]+~', '-', $text));
        return trim($s, '-');
    }

    /**
     * RouterOS object names derived from a VLAN row, shared by add and remove.
     */
    public static function vlanNames($vlan)
    {
        $v = (int) $vlan['vlan_id'];
        $slug = self::slug($vlan['name']);
        return [
            'interface' => 'vlan' . $v . ($slug ? '-' . $slug : ''),
            'pool' => 'pool-vlan' . $v,
            'dhcp' => 'dhcp-vlan' . $v,
            'hs_profile' => 'hsprof-vlan' . $v,
            'hotspot' => 'hs-vlan' . $v,
            'pppoe' => 'pppoe-vlan' . $v,
            'comment' => self::TAG . ':vlan' . $v,
        ];
    }

    /**
     * Operations that create/update a VLAN and the service bound to it.
     */
    public static function vlanOperations($vlan, $htmlDirectory = 'suntech')
    {
        $n = self::vlanNames($vlan);
        $net = self::parseCidr($vlan['gateway']);
        if (!$net) {
            throw new Exception('Invalid gateway CIDR: ' . $vlan['gateway']);
        }
        $pool = trim($vlan['pool_range']) ?: $net['pool'];
        $dns = trim($vlan['dns']) ?: $net['gateway'];
        $ops = [];

        $ops[] = ['path' => '/interface/vlan', 'key' => ['name' => $n['interface']], 'args' => [
            'vlan-id' => (int) $vlan['vlan_id'],
            'interface' => $vlan['parent_interface'],
            'comment' => $n['comment'] . ' ' . $vlan['name'],
            'disabled' => $vlan['enabled'] ? 'no' : 'yes',
        ]];
        $ops[] = ['path' => '/ip/address', 'key' => ['comment' => $n['comment']], 'args' => [
            'address' => $net['gateway'] . '/' . $net['prefix'],
            'interface' => $n['interface'],
        ]];
        $ops[] = ['path' => '/ip/pool', 'key' => ['name' => $n['pool']], 'args' => [
            'ranges' => $pool,
            'comment' => $n['comment'],
        ]];

        if ($vlan['dhcp']) {
            if ($dns == $net['gateway']) {
                // clients use the router as resolver
                $ops[] = ['path' => '/ip/dns', 'set' => true, 'args' => ['allow-remote-requests' => 'yes']];
            }
            $ops[] = ['path' => '/ip/dhcp-server/network', 'key' => ['comment' => $n['comment']], 'args' => [
                'address' => $net['network'],
                'gateway' => $net['gateway'],
                'dns-server' => $dns,
            ]];
            $ops[] = ['path' => '/ip/dhcp-server', 'key' => ['name' => $n['dhcp']], 'args' => [
                'interface' => $n['interface'],
                'address-pool' => $n['pool'],
                'lease-time' => '1h',
                'disabled' => 'no',
            ]];
        }

        if ($vlan['service'] == 'Hotspot') {
            $profile = [
                'hotspot-address' => $net['gateway'],
                'html-directory' => $htmlDirectory,
                'login-by' => 'http-chap,http-pap,cookie,mac-cookie',
            ];
            if (!empty($vlan['dns_name'])) {
                $profile['dns-name'] = $vlan['dns_name'];
            }
            $ops[] = ['path' => '/ip/hotspot/profile', 'key' => ['name' => $n['hs_profile']], 'args' => $profile];
            $ops[] = ['path' => '/ip/hotspot', 'key' => ['name' => $n['hotspot']], 'args' => [
                'interface' => $n['interface'],
                'address-pool' => $n['pool'],
                'profile' => $n['hs_profile'],
                'disabled' => $vlan['enabled'] ? 'no' : 'yes',
            ]];
        } elseif ($vlan['service'] == 'PPPoE') {
            $ops[] = ['path' => '/interface/pppoe-server/server', 'key' => ['service-name' => $n['pppoe']], 'args' => [
                'interface' => $n['interface'],
                'authentication' => 'pap,chap,mschap1,mschap2',
                'one-session-per-host' => 'yes',
                'disabled' => $vlan['enabled'] ? 'no' : 'yes',
            ]];
        }
        return $ops;
    }

    /**
     * Operations that remove everything vlanOperations() created, in reverse dependency order.
     */
    public static function vlanRemoveOperations($vlan)
    {
        $n = self::vlanNames($vlan);
        return [
            ['path' => '/interface/pppoe-server/server', 'key' => ['service-name' => $n['pppoe']], 'remove' => true],
            ['path' => '/ip/hotspot', 'key' => ['name' => $n['hotspot']], 'remove' => true],
            ['path' => '/ip/hotspot/profile', 'key' => ['name' => $n['hs_profile']], 'remove' => true],
            ['path' => '/ip/dhcp-server', 'key' => ['name' => $n['dhcp']], 'remove' => true],
            ['path' => '/ip/dhcp-server/network', 'key' => ['comment' => $n['comment']], 'remove' => true],
            ['path' => '/ip/pool', 'key' => ['name' => $n['pool']], 'remove' => true],
            ['path' => '/ip/address', 'key' => ['comment' => $n['comment']], 'remove' => true],
            ['path' => '/interface/vlan', 'key' => ['name' => $n['interface']], 'remove' => true],
        ];
    }

    public static function quote($value)
    {
        if (is_int($value) || preg_match('~^[A-Za-z0-9._/,-]+$~', (string) $value)) {
            return (string) $value;
        }
        return '"' . str_replace(['\\', '"', '$'], ['\\\\', '\\"', '\\$'], $value) . '"';
    }

    private static function argString($args)
    {
        $out = [];
        foreach ($args as $k => $v) {
            $out[] = $k . '=' . self::quote($v);
        }
        return implode(' ', $out);
    }

    /**
     * Render operations as an idempotent RouterOS script.
     */
    public static function toScript($ops, $title = '')
    {
        $lines = [];
        $lines[] = '# ' . ($title ?: 'SunTech ISP configuration');
        $lines[] = '# generated ' . date('Y-m-d H:i:s') . ' - safe to run more than once';
        foreach ($ops as $op) {
            $menu = str_replace('/', ' ', ltrim($op['path'], '/'));
            $menu = '/' . $menu;
            if (!empty($op['set'])) {
                $lines[] = $menu . ' set ' . self::argString($op['args']);
                continue;
            }
            $find = [];
            foreach ($op['key'] as $k => $v) {
                $find[] = $k . '=' . self::quote($v);
            }
            $find = implode(' ', $find);
            if (!empty($op['remove'])) {
                $lines[] = $menu . ' remove [find ' . $find . ']';
                continue;
            }
            $args = self::argString($op['args']);
            $lines[] = ':if ([:len [' . $menu . ' find ' . $find . ']] = 0) do={ ' .
                $menu . ' add ' . $find . ' ' . $args . ' } else={ ' .
                $menu . ' set [find ' . $find . '] ' . $args . ' }';
        }
        return implode("\n", $lines) . "\n";
    }

    /**
     * Apply operations through the RouterOS API. Returns a log array; throws on the first error.
     */
    public static function apply($client, $ops)
    {
        $log = [];
        foreach ($ops as $op) {
            $path = $op['path'];
            if (!empty($op['set'])) {
                self::send($client, $path . '/set', $op['args']);
                $log[] = 'SET ' . $path;
                continue;
            }
            $ids = self::find($client, $path, $op['key']);
            if (!empty($op['remove'])) {
                foreach ($ids as $id) {
                    self::send($client, $path . '/remove', ['numbers' => $id]);
                }
                if ($ids) {
                    $log[] = 'REMOVE ' . $path . ' ' . json_encode($op['key']);
                }
                continue;
            }
            if ($ids) {
                self::send($client, $path . '/set', array_merge(['numbers' => $ids[0]], $op['args']));
                $log[] = 'UPDATE ' . $path . ' ' . json_encode($op['key']);
            } else {
                self::send($client, $path . '/add', array_merge($op['key'], $op['args']));
                $log[] = 'ADD ' . $path . ' ' . json_encode($op['key']);
            }
        }
        return $log;
    }

    public static function find($client, $path, $key)
    {
        $req = new RouterOS\Request($path . '/print');
        $req->setArgument('.proplist', '.id');
        $query = null;
        foreach ($key as $k => $v) {
            $query = $query ? $query->andWhere($k, $v) : RouterOS\Query::where($k, $v);
        }
        if ($query) {
            $req->setQuery($query);
        }
        $ids = [];
        foreach ($client->sendSync($req) as $res) {
            if ($res->getType() === RouterOS\Response::TYPE_ERROR) {
                throw new Exception($path . ': ' . $res->getProperty('message'));
            }
            if ($res->getType() === RouterOS\Response::TYPE_DATA) {
                $ids[] = $res->getProperty('.id');
            }
        }
        return $ids;
    }

    public static function send($client, $command, $args)
    {
        $req = new RouterOS\Request($command);
        foreach ($args as $k => $v) {
            $req->setArgument($k, $v);
        }
        foreach ($client->sendSync($req) as $res) {
            if ($res->getType() === RouterOS\Response::TYPE_ERROR) {
                throw new Exception($command . ': ' . $res->getProperty('message'));
            }
        }
    }

    /**
     * Make sure a router row has a check-in token and, if none set yet, generated API credentials.
     * Returns true when the row changed (caller saves).
     */
    public static function prepareAutoSetup($router)
    {
        $changed = false;
        if (empty($router['api_token'])) {
            $router->api_token = bin2hex(random_bytes(16));
            $changed = true;
        }
        if (empty($router['username'])) {
            $router->username = 'suntech-api';
            $router->password = substr(str_replace(['+', '/', '='], '', base64_encode(random_bytes(18))), 0, 20);
            $changed = true;
        }
        return $changed;
    }

    public static function checkinUrl($router, $serverUrl)
    {
        return rtrim($serverUrl, '/') . '/index.php?_route=checkin/' . $router['api_token'];
    }

    /**
     * Script pasted once on the Mikrotik: creates the API user, enables the API
     * and schedules a check-in so the billing system learns the router IP.
     */
    public static function routerSetupScript($router, $serverUrl)
    {
        $url = self::checkinUrl($router, $serverUrl);
        $port = 8728;
        $parts = explode(':', $router['ip_address']);
        if (!empty($parts[1])) {
            $port = (int) $parts[1];
        }
        $l = [];
        $l[] = '# SunTech ISP - connect router "' . $router['name'] . '" to the billing system';
        $l[] = '# paste in Winbox > New Terminal (or SSH). Safe to run again.';
        // only manage users this system generated, never touch an existing admin account
        if (strpos($router['username'], 'suntech') === 0) {
            $l[] = ':if ([:len [/user group find name=suntech-api]] = 0) do={ /user group add name=suntech-api policy=read,write,api,policy,test,sensitive,ftp }';
            $l[] = ':if ([:len [/user find name=' . self::quote($router['username']) . ']] = 0) do={ /user add name=' . self::quote($router['username']) .
                ' group=suntech-api password=' . self::quote($router['password']) . ' comment="SunTech ISP billing" } else={ /user set [find name=' .
                self::quote($router['username']) . '] group=suntech-api password=' . self::quote($router['password']) . ' }';
        } else {
            $l[] = '# using existing router user "' . $router['username'] . '" saved in the billing system';
        }
        $l[] = '/ip service set api disabled=no port=' . $port;
        $l[] = ':if ([:len [/ip dhcp-client find interface=ether1]] = 0) do={ /ip dhcp-client add interface=ether1 disabled=no } else={ /ip dhcp-client set [find interface=ether1] disabled=no }';
        $l[] = '/ip dns set servers=8.8.8.8,1.1.1.1 allow-remote-requests=yes';
        $checkCert = (strpos($url, 'https://') === 0) ? ' check-certificate=no' : '';
        $fetch = '/tool fetch url=' . self::quote($url) . $checkCert . ' keep-result=no';
        $l[] = '/system scheduler remove [find name=suntech-checkin]';
        $l[] = '/system scheduler add name=suntech-checkin start-time=startup interval=5m comment="SunTech ISP check-in" on-event=' .
            self::quote(':do { ' . $fetch . ' } on-error={ :log warning "SunTech ISP check-in failed" }');
        $l[] = ':do { ' . $fetch . '; :put "SunTech ISP: router registered" } on-error={ :put "SunTech ISP: cannot reach ' . rtrim($serverUrl, '/') . ' - check the Server URL and network" }';
        return implode("\n", $l) . "\n";
    }

    /**
     * Connect to a tbl_routers row.
     */
    public static function client($router)
    {
        if (trim($router['ip_address']) == '') {
            throw new Exception('Router ' . $router['name'] . ' has not checked in yet. Run its setup script on the Mikrotik (Network > Routers > Setup Script).');
        }
        $iport = explode(':', $router['ip_address']);
        $port =!empty($iport[1]) ? (int) $iport[1] : 8728;
        // fail fast instead of waiting for the API client's long timeout
        $sock = @fsockopen($iport[0], $port, $errno, $errstr, 5);
        if (!$sock) {
            throw new Exception('Router ' . $iport[0] . ':' . $port . ' not reachable (' . trim($errstr) . '). Check the IP and that the API service is enabled.');
        }
        fclose($sock);
        return new RouterOS\Client($iport[0], $router['username'], $router['password'], $port);
    }

    /**
     * List interface names on the router (for the parent interface picker).
     */
    public static function interfaces($client)
    {
        $req = new RouterOS\Request('/interface/print');
        $req->setArgument('.proplist', 'name,type');
        $list = [];
        foreach ($client->sendSync($req) as $res) {
            if ($res->getType() === RouterOS\Response::TYPE_DATA) {
                $list[] = ['name' => $res->getProperty('name'), 'type' => $res->getProperty('type')];
            }
        }
        return $list;
    }
}
