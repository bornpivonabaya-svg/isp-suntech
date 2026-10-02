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

    public static $themes = [
        'suntech-blue' => [
            'name' => 'Suntech Blue (Red Boy Denim)',
            'category' => 'Commercial ISP (Default)',
            'desc' => 'Vibrant royal denim blue layout with 3D mascot boy, sky badges, and instant M-Pesa checkout.',
            'appearance' => 'light',
            'color' => '#0284c7',
            'color2' => '#0369a1',
            'bg' => '#f0f4f8',
            'card_bg' => '#ffffff',
            'text' => '#0f172a',
            'muted' => '#64748b',
            'border' => '#e2e8f0',
            'badge' => '#0284c7',
        ],
        'mashuphost-clean' => [
            'name' => 'Suntech Clean (Emerald Light)',
            'category' => 'Recommended',
            'desc' => 'Fast, light, and clear. Loads quickly on any phone, crisp emerald accents, and packages on one screen.',
            'appearance' => 'light',
            'color' => '#059669',
            'color2' => '#047857',
            'bg' => '#f8fafc',
            'card_bg' => '#ffffff',
            'text' => '#0f172a',
            'muted' => '#64748b',
            'border' => '#e2e8f0',
            'badge' => '#059669',
        ],
        'gold-energy' => [
            'name' => 'Gold Energy (SPICEZCOM)',
            'category' => 'Commercial ISP',
            'desc' => 'High-converting Kenyan ISP layout with gold energy waves, 3D signboard cards, and dark theme support bar.',
            'appearance' => 'dark',
            'color' => '#f59e0b',
            'color2' => '#b45309',
            'bg' => '#090d16',
            'card_bg' => '#131926',
            'text' => '#f8fafc',
            'muted' => '#94a3b8',
            'border' => '#243044',
            'badge' => '#f59e0b',
        ],
        'modern-glass' => [
            'name' => 'Modern Glass (Luxury Dark)',
            'category' => 'Modern / Tech',
            'desc' => 'Ultra-sleek dark theme with frosted glassmorphism, luminous glow lights, and clean typography.',
            'appearance' => 'dark',
            'glass' => true,
            'color' => '#8b5cf6',
            'color2' => '#4f46e5',
            'bg' => '#090a14',
            'card_bg' => 'rgba(255, 255, 255, 0.05)',
            'text' => '#f1f5f9',
            'muted' => '#94a3b8',
            'border' => 'rgba(255, 255, 255, 0.12)',
            'badge' => '#8b5cf6',
        ],
        'cyberpunk-neon' => [
            'name' => 'Cyberpunk Neon (Electric Cyber)',
            'category' => 'Gaming & Youth',
            'desc' => 'Midnight black background with glowing electric cyan and magenta neon accents for cybercafes and venues.',
            'appearance' => 'dark',
            'neon' => true,
            'color' => '#00f0ff',
            'color2' => '#ff007f',
            'bg' => '#050508',
            'card_bg' => '#0b0d14',
            'text' => '#ffffff',
            'muted' => '#94a3b8',
            'border' => '#00f0ff',
            'badge' => '#00f0ff',
        ],
        'hospitality-clean' => [
            'name' => 'Hospitality Clean (Hotel & Lounge)',
            'category' => 'Hospitality',
            'desc' => 'Sophisticated navy/slate minimalist aesthetic designed for luxury hotels, cafes, and executive guest lounges.',
            'appearance' => 'dark',
            'color' => '#38bdf8',
            'color2' => '#0f172a',
            'bg' => '#0b1120',
            'card_bg' => '#1e293b',
            'text' => '#f8fafc',
            'muted' => '#94a3b8',
            'border' => '#334155',
            'badge' => '#475569',
        ],
        'vibrant-retail' => [
            'name' => 'Vibrant Retail (Crisp Light)',
            'category' => 'Public Retail',
            'desc' => 'High-contrast daylight-optimized white design with bold colorful badges and quick-tap cards.',
            'appearance' => 'light',
            'color' => '#2563eb',
            'color2' => '#1d4ed8',
            'bg' => '#f1f5f9',
            'card_bg' => '#ffffff',
            'text' => '#0f172a',
            'muted' => '#64748b',
            'border' => '#cbd5e1',
            'badge' => '#2563eb',
        ],
        'green-line' => [
            'name' => 'Green Line (M-Pesa Safaricom)',
            'category' => 'Everyday Kenya',
            'desc' => 'Green header band, package list, familiar to anyone in Kenya who has used M-Pesa.',
            'appearance' => 'light',
            'color' => '#16803c',
            'color2' => '#14532d',
            'bg' => '#f4f7f4',
            'card_bg' => '#ffffff',
            'text' => '#0f1f14',
            'muted' => '#5b6b60',
            'border' => '#d8e3da',
            'badge' => '#16803c',
        ],
        'sunset' => [
            'name' => 'Sunset Orange',
            'category' => 'Everyday',
            'desc' => 'Warm orange hero with packages in a two-column grid. Bright, quick to scan on a phone in daylight.',
            'appearance' => 'light',
            'color' => '#ea580c',
            'color2' => '#c2410c',
            'bg' => '#fff8f1',
            'card_bg' => '#ffffff',
            'text' => '#2a1707',
            'muted' => '#7a5a43',
            'border' => '#f3dfcf',
            'badge' => '#ea580c',
        ],
        'midnight' => [
            'name' => 'Midnight Dark (OLED)',
            'category' => 'Dark',
            'desc' => 'Near-black with a single blue accent and a plain header. Easy on the eyes at night; no glow effects.',
            'appearance' => 'dark',
            'color' => '#3b82f6',
            'color2' => '#1d4ed8',
            'bg' => '#0b0f17',
            'card_bg' => '#131926',
            'text' => '#f1f5f9',
            'muted' => '#94a3b8',
            'border' => '#243044',
            'badge' => '#3b82f6',
        ],
        'ocean' => [
            'name' => 'Ocean Teal',
            'category' => 'Everyday',
            'desc' => 'Teal hero and compact price tiles — three packages per row, prices first. Good for long price lists.',
            'appearance' => 'light',
            'color' => '#0f766e',
            'color2' => '#115e59',
            'bg' => '#f2f8f9',
            'card_bg' => '#ffffff',
            'text' => '#0b2a31',
            'muted' => '#4c6b72',
            'border' => '#cfe2e5',
            'badge' => '#0f766e',
        ],
        'campus' => [
            'name' => 'Campus Indigo',
            'category' => 'Venues',
            'desc' => 'Indigo band and a grid of packages, sized for students buying short bundles between classes.',
            'appearance' => 'light',
            'color' => '#4f46e5',
            'color2' => '#3730a3',
            'bg' => '#f5f5fb',
            'card_bg' => '#ffffff',
            'text' => '#1a1a3d',
            'muted' => '#5f5f8a',
            'border' => '#dcdcf0',
            'badge' => '#4f46e5',
        ],
        'warm-sand' => [
            'name' => 'Warm Sand',
            'category' => 'Venues',
            'desc' => 'Sand and brown tones with generous spacing, for cafes, guest houses and lodges.',
            'appearance' => 'light',
            'color' => '#9a5b2e',
            'color2' => '#78350f',
            'bg' => '#f8f3ec',
            'card_bg' => '#fffdf9',
            'text' => '#2b2118',
            'muted' => '#7a6a5a',
            'border' => '#e8dccb',
            'badge' => '#9a5b2e',
        ],
        'royal' => [
            'name' => 'Royal Purple',
            'category' => 'Dark',
            'desc' => 'Deep purple with price tiles on a dark surface. Distinctive without any animation.',
            'appearance' => 'dark',
            'color' => '#a855f7',
            'color2' => '#7e22ce',
            'bg' => '#140f24',
            'card_bg' => '#1e1636',
            'text' => '#f5f3ff',
            'muted' => '#b4a8d6',
            'border' => '#332a52',
            'badge' => '#a855f7',
        ],
    ];

    public static function settings()
    {
        global $config;
        $d = [
            'cp_theme' => 'suntech-blue',
            'cp_title' => $config['CompanyName'],
            'cp_tagline' => 'Fast, affordable WiFi',
            'cp_color' => '#0284c7',
            'cp_color2' => '#0369a1',
            'cp_phone' => $config['phone'],
            'cp_whatsapp' => '',
            'cp_notice' => '',
            'cp_show_plans' => 'yes',
            'cp_show_voucher' => 'yes',
            'cp_show_member' => 'yes',
            'cp_show_buy' => 'yes',
            'cp_block_vpn' => 'yes',
            'cp_block_dns_tunnel' => 'yes',
            'cp_block_protocols' => 'yes',
            'cp_mpesa_pay' => 'yes',
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
    public static function files($router, $overrideTheme = null)
    {
        $s = self::settings();
        $themeId = $overrideTheme ?: ($s['cp_theme'] ?? 'suntech-blue');
        if (!isset(self::$themes[$themeId])) {
            $themeId = 'suntech-blue';
        }
        $thm = self::$themes[$themeId];

        // If admin customized color, respect it, otherwise use theme defaults
        $color = (isset($s['cp_color']) && preg_match('~^#[0-9a-fA-F]{6}$~', $s['cp_color']) && $s['cp_color'] != '#f58c14')
            ? $s['cp_color'] : $thm['color'];
        $color2 = (isset($s['cp_color2']) && preg_match('~^#[0-9a-fA-F]{6}$~', $s['cp_color2']) && $s['cp_color2'] != '#102a54')
            ? $s['cp_color2'] : $thm['color2'];

        $wa = preg_replace('~\D~', '', $s['cp_whatsapp']);
        $contact = [];
        if (!empty($s['cp_phone'])) {
            $contact[] = '<a href="tel:' . self::e(preg_replace('~[^\d+]~', '', $s['cp_phone'])) . '">&#9742; ' . self::e($s['cp_phone']) . '</a>';
        }
        if ($wa) {
            $contact[] = '<a href="https://wa.me/' . $wa . '">WhatsApp</a>';
        }
        $vars = [
            '{{THEME}}' => $themeId,
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
            '{{MPESA_PAY}}' => $s['cp_mpesa_pay'] == 'yes' ? 'true' : 'false',
            '{{PHONE}}' => self::e($s['cp_phone'] ?: ''),
            '{{YEAR}}' => date('Y'),
        ];
        $css = self::css($themeId, $color, $color2);
        $css = strtr($css, $vars);
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
            $fUrl = self::fileUrl($router, $f);
            $checkCert = (strpos($fUrl, 'https://') === 0) ? ' check-certificate=no' : '';
            $script .= '/tool fetch url=' . RouterScript::quote($fUrl) . $checkCert . ' dst-path=' . RouterScript::quote($dir . '/' . $f) . "\n";
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

    private static function css($themeId = 'suntech-blue', $color = '#0284c7', $color2 = '#0369a1')
    {
        $t = self::$themes[$themeId] ?? self::$themes['suntech-blue'];
        $isDark = ($t['appearance'] ?? 'light') === 'dark';
        $isGlass = !empty($t['glass']);
        $neon = !empty($t['neon']);

        $bg = $t['bg'];
        $cardBg = $t['card_bg'];
        $textColor = $t['text'];
        $mutedColor = $t['muted'];
        $borderColor = $t['border'];

        $cardStyle = "background: {$cardBg}; border: 1px solid {$borderColor};";
        if ($isGlass) {
            $cardStyle = "background: {$cardBg}; backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); border: 1px solid {$borderColor}; box-shadow: 0 8px 32px rgba(0,0,0,0.5);";
        } elseif ($neon) {
            $cardStyle = "background: {$cardBg}; border: 1.5px solid {$borderColor}; box-shadow: 0 0 16px rgba(0, 240, 255, 0.25);";
        } elseif ($isDark) {
            $cardStyle .= " box-shadow: 0 8px 30px rgba(0,0,0,0.4);";
        } else {
            $cardStyle .= " box-shadow: 0 8px 30px rgba(15,31,61,0.08);";
        }

        $inputBg = $isDark ? ($neon ? '#0a0d14' : 'rgba(255,255,255,0.06)') : '#f8fafc';
        $inputBorder = $isDark ? $borderColor : '#cbd5e1';
        $inputColor = $isDark ? '#f8fafc' : '#0f172a';

        $tabsBg = $isDark ? 'rgba(255,255,255,0.08)' : '#f1f4f9';
        $tabOnBg = $isDark ? ($isGlass ? 'rgba(255,255,255,0.15)' : $cardBg) : '#ffffff';
        $tabOnColor = $isDark ? '#ffffff' : $color2;
        $tabOnBorder = $isDark ? "border: 1px solid {$borderColor};" : "";

        $sheetBg = $isDark ? '#0f172a' : '#ffffff';
        $sheetColor = $isDark ? '#f8fafc' : '#0f172a';
        $sheetBorder = $isDark ? "border-top: 1px solid {$borderColor};" : "";
        $sheetCloseBg = $isDark ? '#1e293b' : '#f1f5f9';
        $sheetCloseColor = $isDark ? '#cbd5e1' : '#475569';
        $pkgPillBg = $isDark ? '#1e293b' : '#f8fafc';
        $pkgPillBorder = $isDark ? $borderColor : '#e2e8f0';

        $btnMainColor = ($neon || $themeId === 'gold-energy') ? '#090d16' : '#ffffff';

        return <<<CSS
*{box-sizing:border-box}html,body{margin:0;padding:0}
body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;background:{$bg};color:{$textColor};font-size:15px;line-height:1.45}
.top{background:linear-gradient(135deg,{$color2} 0%,{$color} 100%);color:#fff;padding:26px 16px 58px;text-align:center;position:relative}
.top-brand{display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,.14);padding:6px 14px;border-radius:24px;backdrop-filter:blur(6px);margin-bottom:8px}
.top img{max-height:42px;max-width:140px;object-fit:contain}
.top h1{margin:6px 0 2px;font-size:22px;font-weight:800;letter-spacing:-.3px}.top p{margin:0;opacity:.92;font-size:13px}
.status-pill{display:inline-flex;align-items:center;gap:6px;background:rgba(16,185,129,.2);border:1px solid rgba(16,185,129,.4);color:#6ee7b7;font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;margin-top:6px}
.status-dot{width:7px;height:7px;border-radius:50%;background:#10b981;box-shadow:0 0 8px #10b981}
.wrap{max-width:440px;margin:-38px auto 0;padding:0 14px 28px;position:relative;z-index:10}
.mascot-banner{background:linear-gradient(135deg,rgba(255,255,255,.18),rgba(255,255,255,.05));border:1px solid {$borderColor};border-radius:18px;padding:12px 14px;margin-bottom:12px;display:flex;align-items:center;justify-content:space-between;backdrop-filter:blur(8px)}
.mascot-info{flex:1;text-align:left}
.mascot-badge{display:inline-block;background:{$color};color:{$btnMainColor};font-size:10px;font-weight:800;padding:2px 8px;border-radius:12px;margin-bottom:3px;letter-spacing:.5px}
.mascot-title{font-size:15px;font-weight:800;color:{$textColor};line-height:1.2}
.mascot-sub{font-size:11.5px;color:{$mutedColor};margin-top:2px}
.mascot-art{margin-left:8px;flex-shrink:0}
.boy-svg{width:52px;height:65px;display:block}
.card{{$cardStyle}border-radius:20px;padding:18px;margin-bottom:14px}
.tabs{display:flex;background:{$tabsBg};border-radius:12px;padding:4px;margin-bottom:16px;gap:4px}
.tabs button{flex:1;border:0;background:transparent;padding:10px 6px;border-radius:9px;font-weight:700;color:{$mutedColor};cursor:pointer;font-size:13px;display:flex;align-items:center;justify-content:center;gap:5px;transition:all .2s}
.tabs button.on{background:{$tabOnBg};color:{$tabOnColor};box-shadow:0 2px 6px rgba(0,0,0,.08);{$tabOnBorder}}
label{display:block;font-size:12px;font-weight:700;color:{$mutedColor};margin:0 0 5px;text-transform:uppercase;letter-spacing:.3px}
input[type=text],input[type=password],input[type=tel]{width:100%;padding:12px 14px;border:1.5px solid {$inputBorder};border-radius:11px;font-size:15px;margin-bottom:14px;outline:none;background:{$inputBg};color:{$inputColor};transition:border-color .2s}
input:focus{border-color:{$color};box-shadow:0 0 0 3px rgba(56,189,248,.25)}
.btn{display:block;width:100%;border:0;border-radius:12px;padding:13px 16px;font-size:15px;font-weight:800;cursor:pointer;text-align:center;text-decoration:none;transition:all .2s;box-sizing:border-box}
.btn-main{background:{$color};color:{$btnMainColor};box-shadow:0 4px 14px rgba(0,0,0,.25)}.btn-main:active{transform:scale(.98)}.btn-main:disabled{opacity:.6}
.btn-mpesa{background:#16a34a;color:#fff;box-shadow:0 4px 14px rgba(22,163,74,.3)}.btn-mpesa:hover{background:#15803d}
.btn-alt{background:{$tabsBg};color:{$textColor};border:1px solid {$borderColor};margin-top:10px}
.btn-reconnect{background:rgba(59,130,246,.12);color:{$color};border:1.5px dashed {$color};padding:11px;border-radius:11px;font-size:13px;font-weight:700;margin-bottom:14px}
.btn-reconnect:hover{background:rgba(59,130,246,.2)}
.alert{background:#fef2f2;color:#991b1b;border:1px solid #fecaca;border-radius:11px;padding:10px 14px;margin-bottom:14px;font-size:13.5px;font-weight:600}
.ok{background:#f0fdf4;color:#166534;border-color:#bbf7d0}
.notice{background:rgba(245,158,11,.15);border-left:4px solid {$color};padding:10px 14px;border-radius:8px;margin-bottom:14px;font-size:13px;color:{$textColor}}
.plans{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.plan{border:1.5px solid {$borderColor};border-radius:14px;padding:12px 10px;text-align:center;text-decoration:none;color:inherit;display:flex;flex-direction:column;justify-content:space-between;background:{$cardBg};transition:all .2s;cursor:pointer;position:relative}
.plan:hover{border-color:{$color};transform:translateY(-2px);box-shadow:0 6px 16px rgba(0,0,0,.15)}
.plan b{display:block;font-size:13.5px;font-weight:800;color:{$textColor};margin-bottom:2px}
.plan .price{color:{$color};font-size:17px;font-weight:900;margin:4px 0}
.plan .limit{display:inline-block;font-size:11px;font-weight:700;color:#16a34a;background:#dcfce7;padding:2px 8px;border-radius:10px;margin-top:2px}
.plan .time{font-size:11px;color:{$mutedColor};margin-top:2px}
.plan .btn-buy{background:#16a34a;color:#fff;border-radius:8px;padding:5px 8px;font-size:12px;font-weight:700;margin-top:8px;border:0}
.muted{color:{$mutedColor};font-size:12.5px;text-align:center}
.foot{text-align:center;color:{$mutedColor};font-size:12.5px;margin-top:12px}.foot a{color:{$color};text-decoration:none;font-weight:700}
.support-bar{display:flex;align-items:center;justify-content:space-between;background:{$color2};color:#fff;border-radius:12px;padding:10px 14px;margin-top:10px;font-size:12.5px;font-weight:600}
.support-bar a{color:#fde047;text-decoration:none;font-weight:800}
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.65);backdrop-filter:blur(6px);z-index:99;display:flex;align-items:flex-end;justify-content:center;opacity:0;pointer-events:none;transition:opacity .25s}
.modal-overlay.open{opacity:1;pointer-events:auto}
.sheet{background:{$sheetBg};color:{$sheetColor};border-radius:24px 24px 0 0;width:100%;max-width:440px;padding:22px 20px 32px;box-shadow:0 -10px 40px rgba(0,0,0,.35);transform:translateY(100%);transition:transform .3s cubic-bezier(.16,1,.3,1);{$sheetBorder}}
.modal-overlay.open .sheet{transform:translateY(0)}
.sheet-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:14px}
.sheet-header h3{margin:0;font-size:18px;font-weight:800;color:{$sheetColor}}
.sheet-close{border:0;background:{$sheetCloseBg};border-radius:50%;width:30px;height:30px;cursor:pointer;font-weight:700;color:{$sheetCloseColor}}
.pkg-pill{background:{$pkgPillBg};border:1.5px solid {$pkgPillBorder};border-radius:12px;padding:10px 14px;display:flex;justify-content:space-between;align-items:center;margin-bottom:16px}
.pkg-pill b{color:{$sheetColor}}
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
<div class="top">
  <div class="top-brand"><img src="logo.png" alt="" onerror="this.style.display=\'none\'"><span>{{TITLE}}</span></div>
  <p>{{TAGLINE}}</p>
  <div class="status-pill"><span class="status-dot"></span> Wi-Fi Hotspot Online</div>
</div>
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

<div class="mascot-banner">
  <div class="mascot-info">
    <div class="mascot-badge">⚡ SUPER FAST WIFI</div>
    <div class="mascot-title">Instant Hotspot Access</div>
    <div class="mascot-sub">Pay via M-Pesa or enter your voucher code below</div>
  </div>
  <div class="mascot-art">
    <svg viewBox="0 0 80 100" class="boy-svg">
      <ellipse cx="45" cy="95" rx="22" ry="4" fill="#000" fill-opacity="0.35"/>
      <path d="M35 58 L32 88 L38 88 L42 62 Z" fill="#1e293b"/>
      <path d="M44 58 L48 88 L54 88 L48 62 Z" fill="#0f172a"/>
      <path d="M28 88 C28 85 38 85 40 88 L41 93 L28 93 Z" fill="#dc2626"/>
      <rect x="28" y="92" width="13" height="3" rx="1.5" fill="#ffffff"/>
      <path d="M46 88 C46 85 56 85 58 88 L59 93 L46 93 Z" fill="#dc2626"/>
      <rect x="46" y="92" width="13" height="3" rx="1.5" fill="#ffffff"/>
      <path d="M28 35 C28 28 58 28 58 35 L56 60 C56 62 30 62 30 60 Z" fill="#dc2626"/>
      <path d="M37 32 L49 32 L47 58 L39 58 Z" fill="#0f172a"/>
      <path d="M40 32 Q43 36 46 32" stroke="#ffffff" stroke-width="1.5" fill="none"/>
      <path d="M36 32 L37 60" stroke="#ffffff" stroke-width="1.5"/>
      <path d="M49 32 L48 60" stroke="#ffffff" stroke-width="1.5"/>
      <path d="M56 34 Q62 44 58 54 Q55 56 52 52 Q54 44 50 36 Z" fill="#dc2626"/>
      <circle cx="58" cy="54" r="3.5" fill="#fbcfe8"/>
      <path d="M30 35 Q20 38 14 42 Q12 45 16 46 Q22 43 30 40 Z" fill="#dc2626"/>
      <path d="M14 42 Q6 40 2 38 Q1 41 5 43 Q10 44 15 46 Z" fill="#fbcfe8"/>
      <path d="M3 38 L-4 38 C-6 38 -6 41 -4 41 L2 41 Z" fill="#fbcfe8"/>
      <circle cx="43" cy="22" r="14" fill="#fed7aa"/>
      <path d="M30 20 C29 8 57 8 56 20 C54 12 48 10 43 11 C38 10 32 12 30 20 Z" fill="#451a03"/>
      <ellipse cx="38" cy="21" rx="2.5" ry="3.5" fill="#ffffff"/>
      <circle cx="38.5" cy="21" r="1.8" fill="#1e1b4b"/>
      <circle cx="39" cy="20.5" r="0.6" fill="#ffffff"/>
      <ellipse cx="48" cy="21" rx="2.5" ry="3.5" fill="#ffffff"/>
      <circle cx="47.5" cy="21" r="1.8" fill="#1e1b4b"/>
      <circle cx="47" cy="20.5" r="0.6" fill="#ffffff"/>
      <path d="M39 27 Q43 32 47 27 Z" fill="#dc2626"/>
      <path d="M40 27 Q43 29 46 27" fill="#ffffff"/>
      <circle cx="29" cy="22" r="3" fill="#fbcfe8"/>
      <circle cx="57" cy="22" r="3" fill="#fbcfe8"/>
    </svg>
  </div>
</div>

<div class="card">
  <div class="tabs" id="tabs">
    <button type="button" class="on {{SHOW_PLANS}}" data-tab="plans">⚡ Packages</button>
    <button type="button" class="{{SHOW_VOUCHER}}" data-tab="voucher">🎫 Voucher</button>
    <button type="button" class="{{SHOW_MEMBER}}" data-tab="member">👤 Account</button>
  </div>

  <!-- PANE 1: PACKAGES -->
  <div id="pane-plans" class="{{SHOW_PLANS}}">
    <div class="plans" id="plans">
      <div style="grid-column:1/-1;text-align:center;padding:16px 0;color:#94a3b8"><span class="spin"></span> Loading Wi-Fi packages...</div>
    </div>
    <p class="muted" style="margin:12px 0 0">Tap a package to pay via M-Pesa &amp; connect instantly.</p>
  </div>

  <!-- PANE 2: VOUCHER -->
  <div id="pane-voucher" class="hidden">
    <div id="rec-box" class="hidden">
      <button type="button" class="btn btn-reconnect" id="btn-reconnect" onclick="reconnectVoucher()">⚡ Reconnect Active Voucher (<span id="rec-code"></span>)</button>
    </div>
    <form id="f-voucher" onsubmit="return voucherLogin()">
      <label for="code">Voucher Code</label>
      <input type="text" id="code" autocomplete="off" autocapitalize="characters" placeholder="e.g. ST8932" style="font-size:18px;letter-spacing:2px;font-weight:700;text-transform:uppercase" required>
      <button class="btn btn-main" id="b-voucher" type="submit">Connect to Internet</button>
    </form>
    <div style="text-align:center;margin-top:12px">
      <span class="muted">Don't have a voucher? <a href="#" onclick="showTab('plans');return false" style="color:{{COLOR}};font-weight:700">Buy Package &rarr;</a></span>
    </div>
  </div>

  <!-- PANE 3: ACCOUNT -->
  <div id="pane-member" class="hidden">
    <form id="f-member" onsubmit="return memberLogin()">
      <label for="user">Username or Phone</label>
      <input type="text" id="user" autocomplete="username" autocapitalize="off" value="$(username)" placeholder="07xx xxx xxx" required>
      <label for="pass">Password</label>
      <input type="password" id="pass" autocomplete="current-password" placeholder="••••••••" required>
      <button class="btn btn-main" type="submit">Login to Account</button>
    </form>
  </div>

  $(if trial == 'yes')<a class="btn btn-alt" href="$(link-login-only)?dst=$(link-orig-esc)&amp;username=T-$(mac-esc)">Free Trial Access</a>$(endif)
</div>

<!-- SUPPORT BAR -->
<div class="support-bar" id="support-bar" style="{{PHONE}}">
  <span>📞 Customer Support</span>
  <a href="tel:{{PHONE}}">{{PHONE}}</a>
</div>

<!-- M-PESA STK MODAL -->
<div class="modal-overlay" id="stk-modal">
  <div class="sheet">
    <div class="sheet-header">
      <h3>Pay via M-Pesa</h3>
      <button type="button" class="sheet-close" onclick="closeModal()">&times;</button>
    </div>
    <div class="pkg-pill">
      <div>
        <b id="m-name" style="font-size:15px;color:#0f172a">Package</b>
        <div id="m-validity" style="font-size:12px;color:#64748b">1 Day</div>
      </div>
      <div id="m-price" style="font-size:18px;font-weight:900;color:#16a34a">KES 0</div>
    </div>
    <form onsubmit="return submitStk();">
      <label for="stk-phone">M-Pesa Phone Number</label>
      <input type="tel" id="stk-phone" placeholder="0712 345 678" style="font-size:16px;font-weight:700" required>
      <div id="stk-msg" class="alert hidden" style="margin-top:4px"></div>
      <button class="btn btn-mpesa" id="b-stk" type="submit">Pay with M-Pesa</button>
      <button type="button" class="btn btn-alt" style="margin-top:8px" onclick="closeModal()">Cancel</button>
    </form>
  </div>
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
var activePlanId = null;
var pollTimer = null;

function api(route) { return CP.server + '/index.php?_route=' + route; }
function say(text, good) {
  var m = document.getElementById('msg');
  m.textContent = text; m.className = 'alert' + (good ? ' ok' : '');
}
function sayStk(text, good) {
  var m = document.getElementById('stk-msg');
  m.textContent = text; m.className = 'alert' + (good ? ' ok' : '');
}

function routerLogin(user, pass) {
  try { localStorage.setItem('cp_saved_voucher', user); } catch(e){}
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
  b.disabled = true; b.innerHTML = '<span class="spin"></span>Connecting...';
  var body = 'code=' + encodeURIComponent(code) + '&mac=' + encodeURIComponent(CP.mac) + '&ip=' + encodeURIComponent(CP.ip);
  var x = new XMLHttpRequest();
  x.open('POST', api('captive/voucher/' + CP.router));
  x.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
  x.timeout = 15000;
  x.onload = function () {
    var r = {};
    try { r = JSON.parse(x.responseText); } catch (e) {}
    if (r.ok) { say(r.message || 'Connected successfully!', true); routerLogin(r.username, r.password); }
    else { say(r.message || 'Voucher not accepted'); b.disabled = false; b.textContent = 'Connect to Internet'; }
  };
  x.onerror = x.ontimeout = function () { routerLogin(code, code); };
  x.send(body);
  return false;
}

function reconnectVoucher() {
  var c = localStorage.getItem('cp_saved_voucher');
  if (c) { document.getElementById('code').value = c; voucherLogin(); }
}

function showTab(name) {
  var tabs = document.querySelectorAll('#tabs button');
  for (var i = 0; i < tabs.length; i++) {
    tabs[i].className = tabs[i].getAttribute('data-tab') === name ? 'on' : '';
  }
  document.getElementById('pane-plans').className = name === 'plans' ? '' : 'hidden';
  document.getElementById('pane-voucher').className = name === 'voucher' ? '' : 'hidden';
  document.getElementById('pane-member').className = name === 'member' ? '' : 'hidden';
}

var tabs = document.querySelectorAll('#tabs button');
for (var i = 0; i < tabs.length; i++) {
  tabs[i].onclick = function () { showTab(this.getAttribute('data-tab')); };
}

function openBuy(id, name, validity, price) {
  activePlanId = id;
  document.getElementById('m-name').textContent = name;
  document.getElementById('m-validity').textContent = validity;
  document.getElementById('m-price').textContent = price;
  var b = document.getElementById('b-stk');
  b.disabled = false; b.textContent = 'Pay ' + price + ' with M-Pesa';
  document.getElementById('stk-msg').className = 'alert hidden';
  try {
    var savedPhone = localStorage.getItem('cp_phone');
    if (savedPhone) document.getElementById('stk-phone').value = savedPhone;
  } catch(e){}
  document.getElementById('stk-modal').className = 'modal-overlay open';
}

function closeModal() {
  if (pollTimer) clearInterval(pollTimer);
  document.getElementById('stk-modal').className = 'modal-overlay';
}

function submitStk() {
  var phone = document.getElementById('stk-phone').value.trim();
  var b = document.getElementById('b-stk');
  if (!phone) return false;
  try { localStorage.setItem('cp_phone', phone); } catch(e){}
  b.disabled = true; b.innerHTML = '<span class="spin"></span>Sending M-Pesa prompt...';
  sayStk('Sending prompt to ' + phone + '...', true);

  var x = new XMLHttpRequest();
  x.open('POST', api('captive/stk/' + CP.router));
  x.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
  x.onload = function() {
    var r = {};
    try { r = JSON.parse(x.responseText); } catch(e){}
    if (r.ok) {
      sayStk(r.message || 'Check your phone and enter your M-Pesa PIN', true);
      b.innerHTML = '<span class="spin"></span>Waiting for PIN...';
      startPolling(r.trx_id);
    } else {
      sayStk(r.message || 'M-Pesa prompt failed, please try again.');
      b.disabled = false; b.textContent = 'Try Again';
    }
  };
  x.onerror = function() {
    sayStk('Could not reach payment server. Check Wi-Fi connection.');
    b.disabled = false; b.textContent = 'Retry';
  };
  x.send('plan_id=' + encodeURIComponent(activePlanId) + '&phone=' + encodeURIComponent(phone));
  return false;
}

function startPolling(trxId) {
  if (pollTimer) clearInterval(pollTimer);
  var attempts = 0;
  pollTimer = setInterval(function() {
    attempts++;
    if (attempts > 30) {
      clearInterval(pollTimer);
      sayStk('Payment timeout. If you received an M-Pesa SMS, enter your code in the Voucher tab.');
      document.getElementById('b-stk').disabled = false;
      document.getElementById('b-stk').textContent = 'Retry';
      return;
    }
    var q = new XMLHttpRequest();
    q.open('GET', api('captive/check_stk/' + CP.router + '&trx_id=' + trxId));
    q.onload = function() {
      var res = {};
      try { res = JSON.parse(q.responseText); } catch(e){}
      if (res.paid) {
        clearInterval(pollTimer);
        sayStk(res.message || 'Payment received! Connecting...', true);
        setTimeout(function() {
          closeModal();
          routerLogin(res.username, res.password);
        }, 1200);
      } else if (res.failed) {
        clearInterval(pollTimer);
        sayStk(res.message || 'Payment was cancelled or failed.');
        document.getElementById('b-stk').disabled = false;
        document.getElementById('b-stk').textContent = 'Retry';
      }
    };
    q.send();
  }, 2500);
}

// Auto-populate plans
if (CP.showPlans) {
  var p = new XMLHttpRequest();
  p.open('GET', api('captive/plans/' + CP.router));
  p.timeout = 10000;
  p.onload = function () {
    var r = {};
    try { r = JSON.parse(p.responseText); } catch (e) { return; }
    if (!r.plans || !r.plans.length) {
      document.getElementById('plans').innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:12px;color:#94a3b8">No packages currently available.</div>';
      return;
    }
    var h = '';
    for (var i = 0; i < r.plans.length; i++) {
      var pl = r.plans[i];
      h += '<div class="plan" onclick="openBuy(' + pl.id + ',\'' + esc(pl.name) + '\',\'' + esc(pl.validity) + '\',\'' + esc(pl.price) + '\')">' +
        '<div><b>' + esc(pl.name) + '</b><span class="time">' + esc(pl.validity) + '</span></div>' +
        '<div class="price">' + esc(pl.price) + '</div>' +
        (pl.limit ? '<span class="limit">' + esc(pl.limit) + '</span>' : '') +
        '<button type="button" class="btn-buy">⚡ Buy Package</button>' +
        '</div>';
    }
    document.getElementById('plans').innerHTML = h;
  };
  p.send();
}

function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }

// Check for saved voucher
try {
  var saved = localStorage.getItem('cp_saved_voucher');
  if (saved) {
    document.getElementById('rec-code').textContent = saved;
    document.getElementById('rec-box').className = '';
  }
} catch(e){}
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
