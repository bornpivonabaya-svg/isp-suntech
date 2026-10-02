<?php

/**
 *  SunTech ISP - Mikrotik Hotspot captive portal generator
 *
 *  Generates the html-directory files for a Mikrotik hotspot (login, status,
 *  logout...) branded from the app settings. The login page talks to this
 *  billing server (through the walled garden) to list plans and activate
 *  vouchers, then logs the customer in on the router with CHAP.
 **/

class CaptivePortal
{
    public static $pages = ['login.html', 'alogin.html', 'status.html', 'logout.html', 'error.html', 'redirect.html', 'rlogin.html'];

    public static function settings()
    {
        global $config;
        $d = [
            'cp_title' => $config['CompanyName'],
            'cp_tagline' => 'Fast, affordable WiFi',
            'cp_color' => '#f58c14',
            'cp_color2' => '#102a54',
            'cp_phone' => $config['phone'],
            'cp_whatsapp' => '',
            'cp_notice' => '',
            'cp_show_plans' => 'yes',
            'cp_show_voucher' => 'yes',
            'cp_show_member' => 'yes',
            'cp_show_buy' => 'yes',
            'cp_server_url' => APP_URL,
            'cp_dir' => 'suntech',
            'cp_prefix' => '',
            'cp_walled_extra' => '',
        ];
        foreach ($d as $k => $v) {
            if (isset($config[$k]) && $config[$k] !== '') {
                $d[$k] = $config[$k];
            }
        }
        $d['cp_server_url'] = rtrim($d['cp_server_url'], '/');
        return $d;
    }

    /**
     * Folder used as html-directory on the router, e.g. "flash/suntech".
     */
    public static function htmlDirectory()
    {
        $s = self::settings();
        return $s['cp_prefix'] . $s['cp_dir'];
    }

    /**
     * Host part of the billing server URL, as seen by the router.
     */
    public static function serverHost()
    {
        $s = self::settings();
        return parse_url($s['cp_server_url'], PHP_URL_HOST);
    }

    /**
     * True when the router cannot possibly reach this server URL.
     */
    public static function serverUrlIsLocalOnly()
    {
        $h = strtolower(self::serverHost());
        return in_array($h, ['localhost', '127.0.0.1', '::1', '']);
    }

    public static function logoPath()
    {
        global $UPLOAD_PATH;
        foreach (['login-logo.png', 'login-logo.default.png'] as $f) {
            $p = $UPLOAD_PATH . DIRECTORY_SEPARATOR . $f;
            if (file_exists($p)) {
                return $p;
            }
        }
        return null;
    }

    private static function e($s)
    {
        return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
    }

    /**
     * All files of the hotspot directory for one router. Binary-safe strings.
     */
    public static function files($router)
    {
        $s = self::settings();
        $color = preg_match('~^#[0-9a-fA-F]{6}$~', $s['cp_color']) ? $s['cp_color'] : '#f58c14';
        $color2 = preg_match('~^#[0-9a-fA-F]{6}$~', $s['cp_color2']) ? $s['cp_color2'] : '#102a54';
        $wa = preg_replace('~\D~', '', $s['cp_whatsapp']);
        $contact = [];
        if (!empty($s['cp_phone'])) {
            $contact[] = '<a href="tel:' . self::e(preg_replace('~[^\d+]~', '', $s['cp_phone'])) . '">&#9742; ' . self::e($s['cp_phone']) . '</a>';
        }
        if ($wa) {
            $contact[] = '<a href="https://wa.me/' . $wa . '">WhatsApp</a>';
        }
        $vars = [
            '{{TITLE}}' => self::e($s['cp_title']),
            '{{TAGLINE}}' => self::e($s['cp_tagline']),
            '{{NOTICE}}' => $s['cp_notice'] ? '<div class="notice">' . nl2br(self::e($s['cp_notice'])) . '</div>' : '',
            '{{CONTACT}}' => implode(' &middot; ', $contact),
            '{{COLOR}}' => $color,
            '{{COLOR2}}' => $color2,
            '{{SERVER}}' => self::e($s['cp_server_url']),
            '{{ROUTER_ID}}' => (int) $router['id'],
            '{{ROUTER_NAME}}' => self::e($router['name']),
            '{{SHOW_PLANS}}' => $s['cp_show_plans'] == 'yes' ? 'true' : 'false',
            '{{SHOW_VOUCHER}}' => $s['cp_show_voucher'] == 'yes' ? '' : 'hidden',
            '{{SHOW_MEMBER}}' => $s['cp_show_member'] == 'yes' ? '' : 'hidden',
            '{{SHOW_BUY}}' => $s['cp_show_buy'] == 'yes' ? '' : 'hidden',
            '{{YEAR}}' => date('Y'),
        ];
        $css = strtr(self::css(), $vars);
        $vars['{{CSS}}'] = $css;
        $files = [];
        foreach (self::$pages as $page) {
            $files[$page] = strtr(self::template($page), $vars);
        }
        $files['md5.js'] = self::md5js();
        $logo = self::logoPath();
        if ($logo) {
            $files['logo.png'] = file_get_contents($logo);
        }
        return $files;
    }

    /**
     * Render a Mikrotik template ($(var), $(if ..) $(else) $(endif)) for the browser preview.
     */
    public static function render($html, $vars)
    {
        $tokens = preg_split('~(\$\((?:if|elif|else|endif)\b[^)]*\))~', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
        $out = '';
        $stack = []; // each: [parentActive, branchTaken, active]
        $active = true;
        foreach ($tokens as $t) {
            if (preg_match('~^\$\((if|elif|else|endif)\b\s*(.*)\)$~s', $t, $m)) {
                switch ($m[1]) {
                    case 'if':
                        $cond = self::cond($m[2], $vars);
                        $stack[] = [$active, $cond];
                        $active = $active && $cond;
                        break;
                    case 'elif':
                        $top = &$stack[count($stack) - 1];
                        $cond = !$top[1] && self::cond($m[2], $vars);
                        $top[1] = $top[1] || $cond;
                        $active = $top[0] && $cond;
                        unset($top);
                        break;
                    case 'else':
                        $top = $stack[count($stack) - 1];
                        $active = $top[0] && !$top[1];
                        break;
                    case 'endif':
                        $top = array_pop($stack);
                        $active = $top ? $top[0] : true;
                        break;
                }
                continue;
            }
            if ($active) {
                $out .= preg_replace_callback('~\$\(([a-z0-9-]+)\)~', function ($m) use ($vars) {
                    return isset($vars[$m[1]]) ? $vars[$m[1]] : '';
                }, $t);
            }
        }
        return $out;
    }

    private static function cond($expr, $vars)
    {
        $expr = trim($expr);
        if (preg_match('~^([a-z0-9-]+)\s*(==|!=)\s*["\']?(.*?)["\']?$~', $expr, $m)) {
            $v = isset($vars[$m[1]]) ? (string) $vars[$m[1]] : '';
            return $m[2] == '==' ? $v === $m[3] : $v !== $m[3];
        }
        return !empty($vars[$expr]);
    }

    /**
     * Sample Mikrotik variables for the preview.
     */
    public static function sampleVars($state = [])
    {
        $v = [
            'link-login-only' => '#login', 'link-login' => '#login', 'link-logout' => '#logout',
            'link-status' => '#status', 'link-orig' => 'http://example.com/', 'link-orig-esc' => 'http%3A%2F%2Fexample.com%2F',
            'link-redirect' => '#', 'link-advert' => '#', 'mac' => 'AA:BB:CC:11:22:33', 'mac-esc' => 'AA%3ABB%3ACC%3A11%3A22%3A33',
            'ip' => '10.10.10.23', 'username' => 'TEST1234', 'identity' => 'SunTech-Router', 'server-name' => 'hs-vlan10',
            'chap-id' => '', 'chap-challenge' => '', 'error' => '', 'trial' => 'no', 'uptime' => '1h12m40s',
            'session-time-left' => '22h47m20s', 'bytes-in-nice' => '128.4 MiB', 'bytes-out-nice' => '12.9 MiB',
            'remain-bytes-total' => '', 'refresh-timeout' => '', 'refresh-timeout-secs' => '', 'login-by' => 'http-chap',
            'http-status' => '', 'http-header' => '', 'hostname' => 'login.suntech.net', 'popup' => 'false',
        ];
        return array_merge($v, $state);
    }

    /**
     * RouterOS operations: walled garden for this server (+ extra hosts).
     */
    public static function walledGardenOperations()
    {
        $s = self::settings();
        $host = self::serverHost();
        $ops = [];
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $ops[] = ['path' => '/ip/hotspot/walled-garden/ip', 'key' => ['comment' => RouterScript::TAG . ':billing'], 'args' => [
                'action' => 'accept', 'dst-address' => $host,
            ]];
        } elseif ($host) {
            $ops[] = ['path' => '/ip/hotspot/walled-garden', 'key' => ['comment' => RouterScript::TAG . ':billing'], 'args' => [
                'action' => 'allow', 'dst-host' => $host,
            ]];
        }
        $i = 0;
        foreach (preg_split('~[\s,]+~', $s['cp_walled_extra'], -1, PREG_SPLIT_NO_EMPTY) as $extra) {
            if (!preg_match('~^[A-Za-z0-9*.:-]+$~', $extra)) {
                continue;
            }
            $i++;
            $ops[] = ['path' => '/ip/hotspot/walled-garden', 'key' => ['comment' => RouterScript::TAG . ':extra' . $i], 'args' => [
                'action' => 'allow', 'dst-host' => $extra,
            ]];
        }
        return $ops;
    }

    /**
     * Full setup script: walled garden + fetch portal files from this server.
     */
    public static function setupScript($router, $applyAllProfiles = false)
    {
        $dir = self::htmlDirectory();
        $ops = self::walledGardenOperations();
        $script = RouterScript::toScript($ops, 'SunTech ISP captive portal for router ' . $router['name']);
        if (self::serverUrlIsLocalOnly()) {
            $script .= "# WARNING: Server URL is localhost, the router cannot download from it. Set the LAN IP in Captive Portal settings.
";
        }
        $script .= "# download portal pages from the billing server into " . $dir . "\n";
        foreach (array_keys(self::files($router)) as $f) {
            $script .= '/tool fetch url=' . RouterScript::quote(self::fileUrl($router, $f)) . ' dst-path=' . RouterScript::quote($dir . '/' . $f) . "\n";
        }
        $line = '/ip hotspot profile set [find default=no] html-directory=' . RouterScript::quote($dir);
        $script .= ($applyAllProfiles ? '' : "# use this portal on every hotspot profile (VLAN hotspots already do):\n# ") . $line . "\n";
        return $script;
    }

    public static function fileUrl($router, $file)
    {
        $s = self::settings();
        return $s['cp_server_url'] . '/index.php?_route=captive/file/' . (int) $router['id'] . '/' . $file;
    }

    /**
     * Minimal ZIP writer (stored, no compression) - ZipArchive is often missing on XAMPP.
     */
    public static function zip($files, $folder = '')
    {
        $data = '';
        $central = '';
        $time = getdate();
        $dosTime = ($time['hours'] << 11) | ($time['minutes'] << 5) | (int) ($time['seconds'] / 2);
        $dosDate = (($time['year'] - 1980) << 9) | ($time['mon'] << 5) | $time['mday'];
        $count = 0;
        foreach ($files as $name => $content) {
            $name = ($folder ? $folder . '/' : '') . $name;
            $crc = crc32($content);
            $len = strlen($content);
            $offset = strlen($data);
            $data .= pack('VvvvvvVVVvv', 0x04034b50, 20, 0, 0, $dosTime, $dosDate, $crc, $len, $len, strlen($name), 0) . $name . $content;
            $central .= pack('VvvvvvvVVVvvvvvVV', 0x02014b50, 20, 20, 0, 0, $dosTime, $dosDate, $crc, $len, $len, strlen($name), 0, 0, 0, 0, 32, $offset) . $name;
            $count++;
        }
        return $data . $central . pack('VvvvvVVv', 0x06054b50, 0, 0, $count, $count, strlen($central), strlen($data), 0);
    }

    private static function css()
    {
        return <<<'CSS'
*{box-sizing:border-box}html,body{margin:0;padding:0}
body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;background:#f2f4f8;color:#1d2433;font-size:15px;line-height:1.45}
.top{background:linear-gradient(135deg,{{COLOR2}} 0%,{{COLOR2}} 55%,{{COLOR}} 140%);color:#fff;padding:22px 16px 64px;text-align:center}
.top img{max-height:56px;max-width:180px;background:#fff;border-radius:12px;padding:6px 10px}
.top h1{margin:10px 0 2px;font-size:22px;letter-spacing:.2px}.top p{margin:0;opacity:.85;font-size:14px}
.wrap{max-width:440px;margin:-46px auto 0;padding:0 14px 24px}
.card{background:#fff;border-radius:16px;box-shadow:0 6px 24px rgba(16,42,84,.12);padding:18px;margin-bottom:14px}
.card h2{font-size:16px;margin:0 0 12px}
.tabs{display:flex;background:#eef1f6;border-radius:10px;padding:4px;margin-bottom:14px}
.tabs button{flex:1;border:0;background:transparent;padding:9px;border-radius:8px;font-weight:600;color:#5b6577;cursor:pointer;font-size:14px}
.tabs button.on{background:#fff;color:{{COLOR2}};box-shadow:0 1px 3px rgba(0,0,0,.08)}
label{display:block;font-size:13px;color:#5b6577;margin:0 0 4px}
input[type=text],input[type=password]{width:100%;padding:12px 14px;border:1.5px solid #d7dce5;border-radius:10px;font-size:16px;margin-bottom:12px;outline:none}
input:focus{border-color:{{COLOR}}}
.btn{display:block;width:100%;border:0;border-radius:10px;padding:13px;font-size:16px;font-weight:700;cursor:pointer;text-align:center;text-decoration:none}
.btn-main{background:{{COLOR}};color:#fff}.btn-main:disabled{opacity:.6}
.btn-alt{background:#fff;color:{{COLOR2}};border:1.5px solid {{COLOR2}};margin-top:10px}
.alert{background:#fdecea;color:#a12622;border-radius:10px;padding:10px 12px;margin-bottom:12px;font-size:14px}
.ok{background:#e7f6ec;color:#1d6b3a}
.notice{background:#fff7e6;border-left:4px solid {{COLOR}};padding:10px 12px;border-radius:8px;margin-bottom:14px;font-size:14px}
.plans{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.plan{border:1.5px solid #e3e7ee;border-radius:12px;padding:12px;text-align:center;text-decoration:none;color:inherit;display:block}
.plan:hover{border-color:{{COLOR}}}
.plan b{display:block;font-size:14px;margin-bottom:4px}.plan .price{color:{{COLOR}};font-size:18px;font-weight:800}
.plan small{color:#7a8396;display:block;margin-top:2px}
.muted{color:#7a8396;font-size:13px;text-align:center}
.foot{text-align:center;color:#7a8396;font-size:13px;margin-top:6px}.foot a{color:{{COLOR2}};text-decoration:none;font-weight:600}
table.st{width:100%;border-collapse:collapse}table.st td{padding:9px 4px;border-bottom:1px solid #eef1f6}table.st td:last-child{text-align:right;font-weight:600}
.hidden{display:none!important}
.spin{display:inline-block;width:14px;height:14px;border:2px solid rgba(255,255,255,.5);border-top-color:#fff;border-radius:50%;animation:s .7s linear infinite;vertical-align:-2px;margin-right:6px}
@keyframes s{to{transform:rotate(360deg)}}
CSS;
    }

    private static function head($title, $extra = '')
    {
        return '<!DOCTYPE html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta http-equiv="pragma" content="no-cache"><meta http-equiv="expires" content="-1">
<title>' . $title . ' - {{TITLE}}</title>' . $extra . '
<style>{{CSS}}</style>
</head><body>
<div class="top"><img src="logo.png" alt="" onerror="this.style.display=\'none\'"><h1>{{TITLE}}</h1><p>{{TAGLINE}}</p></div>
<div class="wrap">
';
    }

    private static function foot()
    {
        return '<div class="foot">{{CONTACT}}<br>&copy; {{YEAR}} {{TITLE}}</div>
</div></body></html>
';
    }

    public static function template($page)
    {
        switch ($page) {
            case 'login.html':
                return self::head('Login', '
$(if chap-id)<script src="md5.js"></script>$(endif)') . <<<'HTML'
{{NOTICE}}
$(if error)<div class="alert">$(error)</div>$(endif)
<div class="alert hidden" id="msg"></div>
<div class="card">
  <div class="tabs" id="tabs">
    <button type="button" class="on {{SHOW_VOUCHER}}" data-tab="voucher">Voucher</button>
    <button type="button" class="{{SHOW_MEMBER}}" data-tab="member">Account</button>
  </div>
  <form id="f-voucher" class="{{SHOW_VOUCHER}}" onsubmit="return voucherLogin()">
    <label for="code">Voucher code</label>
    <input type="text" id="code" autocomplete="off" autocapitalize="characters" placeholder="Enter your voucher" required>
    <button class="btn btn-main" id="b-voucher" type="submit">Connect</button>
  </form>
  <form id="f-member" class="hidden" onsubmit="return memberLogin()">
    <label for="user">Username</label>
    <input type="text" id="user" autocomplete="username" autocapitalize="off" value="$(username)" required>
    <label for="pass">Password</label>
    <input type="password" id="pass" autocomplete="current-password" required>
    <button class="btn btn-main" type="submit">Login</button>
  </form>
  $(if trial == 'yes')<a class="btn btn-alt" href="$(link-login-only)?dst=$(link-orig-esc)&amp;username=T-$(mac-esc)">Free trial</a>$(endif)
  <a class="btn btn-alt {{SHOW_BUY}}" id="buy" href="#">Buy a package</a>
</div>
<div class="card hidden" id="plans-card">
  <h2>Packages</h2>
  <div class="plans" id="plans"></div>
  <p class="muted" style="margin:10px 0 0">Tap a package to pay and get connected.</p>
</div>
<form name="sendin" action="$(link-login-only)" method="post" class="hidden">
  <input type="hidden" name="username"><input type="hidden" name="password">
  <input type="hidden" name="dst" value="$(link-orig)"><input type="hidden" name="popup" value="true">
</form>
<script>
var CP = {
  server: '{{SERVER}}', router: '{{ROUTER_ID}}', showPlans: {{SHOW_PLANS}},
  mac: '$(mac)', ip: '$(ip)', chapId: '$(chap-id)', chapChallenge: '$(chap-challenge)'
};
function api(route) { return CP.server + '/index.php?_route=' + route; }
function portalUrl(route) {
  return api(route) + '&nux-mac=' + encodeURIComponent(CP.mac) + '&nux-ip=' + encodeURIComponent(CP.ip) + '&nux-router=' + CP.router;
}
function say(text, good) {
  var m = document.getElementById('msg');
  m.textContent = text; m.className = 'alert' + (good ? ' ok' : '');
}
function routerLogin(user, pass) {
  var f = document.sendin;
  f.username.value = user;
  f.password.value = (CP.chapId && window.hexMD5) ? hexMD5(CP.chapId + pass + CP.chapChallenge) : pass;
  f.submit();
  return false;
}
function memberLogin() {
  return routerLogin(document.getElementById('user').value.trim(), document.getElementById('pass').value);
}
function voucherLogin() {
  var code = document.getElementById('code').value.trim(), b = document.getElementById('b-voucher');
  if (!code) return false;
  b.disabled = true; b.innerHTML = '<span class="spin"></span>Checking...';
  var body = 'code=' + encodeURIComponent(code) + '&mac=' + encodeURIComponent(CP.mac) + '&ip=' + encodeURIComponent(CP.ip);
  var x = new XMLHttpRequest();
  x.open('POST', api('captive/voucher/' + CP.router));
  x.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
  x.timeout = 15000;
  x.onload = function () {
    var r = {};
    try { r = JSON.parse(x.responseText); } catch (e) {}
    if (r.ok) { say(r.message || 'Voucher accepted, connecting...', true); routerLogin(r.username, r.password); }
    else { say(r.message || 'Voucher not accepted'); b.disabled = false; b.textContent = 'Connect'; }
  };
  // billing server unreachable: the voucher may already be active on the router
  x.onerror = x.ontimeout = function () { routerLogin(code, code); };
  x.send(body);
  return false;
}
var tabs = document.querySelectorAll('#tabs button');
for (var i = 0; i < tabs.length; i++) tabs[i].onclick = function () {
  for (var j = 0; j < tabs.length; j++) tabs[j].className = tabs[j].className.replace(/\bon\b/, '').trim();
  this.className += ' on';
  document.getElementById('f-voucher').className = this.getAttribute('data-tab') == 'voucher' ? '' : 'hidden';
  document.getElementById('f-member').className = this.getAttribute('data-tab') == 'member' ? '' : 'hidden';
};
if (tabs[0].className.indexOf('hidden') >= 0 && tabs[1]) tabs[1].onclick();
document.getElementById('buy').href = portalUrl('login');
function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }
if (CP.showPlans) {
  var p = new XMLHttpRequest();
  p.open('GET', api('captive/plans/' + CP.router));
  p.timeout = 10000;
  p.onload = function () {
    var r = {};
    try { r = JSON.parse(p.responseText); } catch (e) { return; }
    if (!r.plans || !r.plans.length) return;
    var h = '';
    for (var i = 0; i < r.plans.length; i++) {
      var pl = r.plans[i];
      h += '<a class="plan" href="' + esc(portalUrl('login')) + '"><b>' + esc(pl.name) + '</b><span class="price">' + esc(pl.price) +
        '</span><small>' + esc(pl.validity) + '</small>' + (pl.limit ? '<small>' + esc(pl.limit) + '</small>' : '') + '</a>';
    }
    document.getElementById('plans').innerHTML = h;
    document.getElementById('plans-card').className = 'card';
  };
  p.send();
}
</script>
HTML
                    . self::foot();

            case 'alogin.html':
                return self::head('Connected', '
<meta http-equiv="refresh" content="3; url=$(link-redirect)">') . <<<'HTML'
<div class="card" style="text-align:center">
  <h2 style="font-size:20px">&#10004; You are connected</h2>
  <p class="muted">Welcome $(username). Taking you to your page...</p>
  <a class="btn btn-main" href="$(link-redirect)">Continue</a>
  <a class="btn btn-alt" href="$(link-status)">Connection status</a>
</div>
HTML
                    . self::foot();

            case 'status.html':
                return self::head('Status', '
$(if refresh-timeout)<meta http-equiv="refresh" content="$(refresh-timeout-secs)">$(endif)') . <<<'HTML'
<div class="card">
  <h2>Connection status</h2>
  <table class="st">
    <tr><td>Account</td><td>$(username)</td></tr>
    <tr><td>IP address</td><td>$(ip)</td></tr>
    <tr><td>Connected for</td><td>$(uptime)</td></tr>
    $(if session-time-left)<tr><td>Time left</td><td>$(session-time-left)</td></tr>$(endif)
    $(if remain-bytes-total)<tr><td>Data left</td><td>$(remain-bytes-total)</td></tr>$(endif)
    <tr><td>Downloaded / Uploaded</td><td>$(bytes-out-nice) / $(bytes-in-nice)</td></tr>
  </table>
  <br>
  <a class="btn btn-alt" style="margin-top:0" href="{{SERVER}}/index.php?_route=home&amp;nux-mac=$(mac-esc)&amp;nux-ip=$(ip)&amp;nux-router={{ROUTER_ID}}">My account &amp; top up</a>
  <form action="$(link-logout)" method="post" style="margin-top:10px">
    <input type="hidden" name="erase-cookie" value="true">
    <button class="btn btn-main" type="submit">Log out</button>
  </form>
</div>
HTML
                    . self::foot();

            case 'logout.html':
                return self::head('Logged out') . <<<'HTML'
<div class="card">
  <h2>You have logged out</h2>
  <table class="st">
    <tr><td>Account</td><td>$(username)</td></tr>
    <tr><td>Session time</td><td>$(uptime)</td></tr>
    <tr><td>Downloaded / Uploaded</td><td>$(bytes-out-nice) / $(bytes-in-nice)</td></tr>
  </table>
  <br>
  <a class="btn btn-main" href="$(link-login)">Log in again</a>
</div>
HTML
                    . self::foot();

            case 'error.html':
                return self::head('Error') . <<<'HTML'
<div class="card">
  <div class="alert">$(error)</div>
  <a class="btn btn-main" href="$(link-login-only)">Back to login</a>
</div>
HTML
                    . self::foot();

            case 'redirect.html':
            case 'rlogin.html':
                return <<<'HTML'
$(if http-status == 302)Hotspot login required$(endif)
$(if http-header == "Location")$(link-redirect)$(endif)
<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>{{TITLE}}</title>
<meta http-equiv="refresh" content="0; url=$(link-redirect)">
<meta http-equiv="pragma" content="no-cache"><meta http-equiv="expires" content="-1">
</head><body><a href="$(link-redirect)">Continue</a></body></html>
HTML;
        }
        return '';
    }

    /**
     * MD5 over 8-bit chars (Mikrotik chap-id/challenge are octal-escaped bytes).
     */
    public static function md5js()
    {
        return <<<'JS'
/* MD5 for Mikrotik HTTP-CHAP login: hexMD5(chapId + password + chapChallenge) */
(function (w) {
  function add(x, y) { var l = (x & 0xFFFF) + (y & 0xFFFF); return (((x >> 16) + (y >> 16) + (l >> 16)) << 16) | (l & 0xFFFF); }
  function rol(n, c) { return (n << c) | (n >>> (32 - c)); }
  function cmn(q, a, b, x, s, t) { return add(rol(add(add(a, q), add(x, t)), s), b); }
  function ff(a, b, c, d, x, s, t) { return cmn((b & c) | (~b & d), a, b, x, s, t); }
  function gg(a, b, c, d, x, s, t) { return cmn((b & d) | (c & ~d), a, b, x, s, t); }
  function hh(a, b, c, d, x, s, t) { return cmn(b ^ c ^ d, a, b, x, s, t); }
  function ii(a, b, c, d, x, s, t) { return cmn(c ^ (b | ~d), a, b, x, s, t); }
  function core(x, len) {
    x[len >> 5] |= 0x80 << (len % 32);
    x[(((len + 64) >>> 9) << 4) + 14] = len;
    var a = 1732584193, b = -271733879, c = -1732584194, d = 271733878;
    for (var i = 0; i < x.length; i += 16) {
      var oa = a, ob = b, oc = c, od = d;
      a = ff(a, b, c, d, x[i], 7, -680876936); d = ff(d, a, b, c, x[i + 1], 12, -389564586);
      c = ff(c, d, a, b, x[i + 2], 17, 606105819); b = ff(b, c, d, a, x[i + 3], 22, -1044525330);
      a = ff(a, b, c, d, x[i + 4], 7, -176418897); d = ff(d, a, b, c, x[i + 5], 12, 1200080426);
      c = ff(c, d, a, b, x[i + 6], 17, -1473231341); b = ff(b, c, d, a, x[i + 7], 22, -45705983);
      a = ff(a, b, c, d, x[i + 8], 7, 1770035416); d = ff(d, a, b, c, x[i + 9], 12, -1958414417);
      c = ff(c, d, a, b, x[i + 10], 17, -42063); b = ff(b, c, d, a, x[i + 11], 22, -1990404162);
      a = ff(a, b, c, d, x[i + 12], 7, 1804603682); d = ff(d, a, b, c, x[i + 13], 12, -40341101);
      c = ff(c, d, a, b, x[i + 14], 17, -1502002290); b = ff(b, c, d, a, x[i + 15], 22, 1236535329);
      a = gg(a, b, c, d, x[i + 1], 5, -165796510); d = gg(d, a, b, c, x[i + 6], 9, -1069501632);
      c = gg(c, d, a, b, x[i + 11], 14, 643717713); b = gg(b, c, d, a, x[i], 20, -373897302);
      a = gg(a, b, c, d, x[i + 5], 5, -701558691); d = gg(d, a, b, c, x[i + 10], 9, 38016083);
      c = gg(c, d, a, b, x[i + 15], 14, -660478335); b = gg(b, c, d, a, x[i + 4], 20, -405537848);
      a = gg(a, b, c, d, x[i + 9], 5, 568446438); d = gg(d, a, b, c, x[i + 14], 9, -1019803690);
      c = gg(c, d, a, b, x[i + 3], 14, -187363961); b = gg(b, c, d, a, x[i + 8], 20, 1163531501);
      a = gg(a, b, c, d, x[i + 13], 5, -1444681467); d = gg(d, a, b, c, x[i + 2], 9, -51403784);
      c = gg(c, d, a, b, x[i + 7], 14, 1735328473); b = gg(b, c, d, a, x[i + 12], 20, -1926607734);
      a = hh(a, b, c, d, x[i + 5], 4, -378558); d = hh(d, a, b, c, x[i + 8], 11, -2022574463);
      c = hh(c, d, a, b, x[i + 11], 16, 1839030562); b = hh(b, c, d, a, x[i + 14], 23, -35309556);
      a = hh(a, b, c, d, x[i + 1], 4, -1530992060); d = hh(d, a, b, c, x[i + 4], 11, 1272893353);
      c = hh(c, d, a, b, x[i + 7], 16, -155497632); b = hh(b, c, d, a, x[i + 10], 23, -1094730640);
      a = hh(a, b, c, d, x[i + 13], 4, 681279174); d = hh(d, a, b, c, x[i], 11, -358537222);
      c = hh(c, d, a, b, x[i + 3], 16, -722521979); b = hh(b, c, d, a, x[i + 6], 23, 76029189);
      a = hh(a, b, c, d, x[i + 9], 4, -640364487); d = hh(d, a, b, c, x[i + 12], 11, -421815835);
      c = hh(c, d, a, b, x[i + 15], 16, 530742520); b = hh(b, c, d, a, x[i + 2], 23, -995338651);
      a = ii(a, b, c, d, x[i], 6, -198630844); d = ii(d, a, b, c, x[i + 7], 10, 1126891415);
      c = ii(c, d, a, b, x[i + 14], 15, -1416354905); b = ii(b, c, d, a, x[i + 5], 21, -57434055);
      a = ii(a, b, c, d, x[i + 12], 6, 1700485571); d = ii(d, a, b, c, x[i + 3], 10, -1894986606);
      c = ii(c, d, a, b, x[i + 10], 15, -1051523); b = ii(b, c, d, a, x[i + 1], 21, -2054922799);
      a = ii(a, b, c, d, x[i + 8], 6, 1873313359); d = ii(d, a, b, c, x[i + 15], 10, -30611744);
      c = ii(c, d, a, b, x[i + 6], 15, -1560198380); b = ii(b, c, d, a, x[i + 13], 21, 1309151649);
      a = ii(a, b, c, d, x[i + 4], 6, -145523070); d = ii(d, a, b, c, x[i + 11], 10, -1120210379);
      c = ii(c, d, a, b, x[i + 2], 15, 718787259); b = ii(b, c, d, a, x[i + 9], 21, -343485551);
      a = add(a, oa); b = add(b, ob); c = add(c, oc); d = add(d, od);
    }
    return [a, b, c, d];
  }
  w.hexMD5 = function (s) {
    var x = [], i;
    for (i = 0; i < s.length * 8; i += 8) x[i >> 5] |= (s.charCodeAt(i / 8) & 255) << (i % 32);
    for (i = 0; i < (((s.length * 8 + 64) >>> 9) << 4) + 16; i++) x[i] = x[i] | 0;
    var r = core(x, s.length * 8), hex = '0123456789abcdef', o = '';
    for (i = 0; i < 16; i++) { var b = (r[i >> 2] >> ((i % 4) * 8)) & 255; o += hex.charAt(b >> 4) + hex.charAt(b & 15); }
    return o;
  };
})(window);
JS;
    }
}
