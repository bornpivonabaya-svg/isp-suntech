<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

_admin();
$ui->assign('_title', 'Plugin Manager');
$ui->assign('_system_menu', 'settings');

$plugin_repository = 'https://hotspotbilling.github.io/Plugin-Repository/repository.json';

$action = $routes['1'];
$ui->assign('_admin', $admin);


if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
    _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
}

$cache = $CACHE_PATH . File::pathFixer('/plugin_repository.json');
if (file_exists($cache) && time() - filemtime($cache) < (24 * 60 * 60)) {
    $txt = file_get_contents($cache);
    $json = json_decode($txt, true);
    if (empty($json['plugins']) && empty($json['payment_gateway'])) {
        unlink($cache);
        r2(getUrl('pluginmanager'));
    }
} else {
    $data = Http::getData($plugin_repository);
    file_put_contents($cache, $data);
    $json = json_decode($data, true);
}
function downloadAndExtractGitHubZip($githubUrl, $targetZipFile, $extractToDir, &$errorMsg = '')
{
    global $config;
    if (!empty($config['github_token']) && !empty($config['github_username'])) {
        $githubUrl = str_replace('https://github.com', 'https://' . urlencode($config['github_username']) . ':' . urlencode($config['github_token']) . '@github.com', $githubUrl);
    }

    $branches = ['master', 'main'];
    $downloadSuccess = false;
    $httpCode = 0;

    foreach ($branches as $branch) {
        $url = rtrim($githubUrl, '/') . '/archive/refs/heads/' . $branch . '.zip';
        $fp = fopen($targetZipFile, 'w+');
        if (!$fp) {
            $errorMsg = 'Cannot open temp zip file for writing';
            return false;
        }
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPGET, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'PHPNuxBill-PluginManager/1.0 (Linux; x86_64)');
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fclose($fp);

        if ($httpCode == 200 && file_exists($targetZipFile) && filesize($targetZipFile) > 200) {
            $downloadSuccess = true;
            break;
        }
    }

    if (!$downloadSuccess) {
        if (file_exists($targetZipFile)) {
            @unlink($targetZipFile);
        }
        if ($httpCode == 404) {
            $errorMsg = 'Plugin repository or branch not found on GitHub (HTTP 404). If this is a paid or private plugin, please download the ZIP manually and upload it.';
        } elseif ($httpCode == 403) {
            $errorMsg = 'GitHub download forbidden (HTTP 403). If this is a private plugin, enter your GitHub token in Settings or upload the ZIP manually.';
        } else {
            $errorMsg = 'Failed to download plugin archive (HTTP ' . $httpCode . '). Please check internet connection or upload ZIP manually.';
        }
        return false;
    }

    if (!class_exists('ZipArchive')) {
        $errorMsg = 'ZipArchive extension is not enabled in PHP.';
        return false;
    }

    $zip = new ZipArchive();
    $res = $zip->open($targetZipFile);
    if ($res !== true) {
        if (file_exists($targetZipFile)) {
            @unlink($targetZipFile);
        }
        $errorMsg = 'Downloaded file is not a valid ZIP archive (error code: ' . $res . ').';
        return false;
    }

    $extracted = $zip->extractTo($extractToDir);
    $zip->close();
    @unlink($targetZipFile);

    if (!$extracted) {
        $errorMsg = 'Failed to extract ZIP archive.';
        return false;
    }

    return true;
}

switch ($action) {
    case 'refresh':
        if (file_exists($cache))
            unlink($cache);
        r2(getUrl('pluginmanager'), 's', 'Refresh success');
        break;
    case 'dlinstall':
        if ($_app_stage == 'Demo') {
            r2(getUrl('pluginmanager'), 'e', 'Demo Mode cannot install as it Security risk');
        }
        if (!is_writeable($CACHE_PATH)) {
            r2(getUrl('pluginmanager'), 'e', 'Folder cache/ is not writable');
        }
        if (!is_writeable($PLUGIN_PATH)) {
            r2(getUrl('pluginmanager'), 'e', 'Folder plugin/ is not writable');
        }
        if (!is_writeable($DEVICE_PATH)) {
            r2(getUrl('pluginmanager'), 'e', 'Folder devices/ is not writable');
        }
        if (!is_writeable($UI_PATH . DIRECTORY_SEPARATOR . 'themes')) {
            r2(getUrl('pluginmanager'), 'e', 'Folder themes/ is not writable');
        }
        $cache = $CACHE_PATH . DIRECTORY_SEPARATOR . 'installer' . DIRECTORY_SEPARATOR;
        if (!file_exists($cache)) {
            mkdir($cache, 0775, true);
        }
        if (file_exists($_FILES['zip_plugin']['tmp_name']) && is_uploaded_file($_FILES['zip_plugin']['tmp_name'])) {
            $zip = new ZipArchive();
            $res = $zip->open($_FILES['zip_plugin']['tmp_name']);
            if ($res !== true) {
                r2(getUrl('pluginmanager'), 'e', 'Uploaded file is not a valid ZIP archive.');
            }
            $zip->extractTo($cache);
            $zip->close();
            $plugin = basename($_FILES['zip_plugin']['name']);
            unlink($_FILES['zip_plugin']['tmp_name']);
            $success = 0;
            //moving
            if (file_exists($cache . 'plugin')) {
                File::copyFolder($cache . 'plugin' . DIRECTORY_SEPARATOR, $PLUGIN_PATH . DIRECTORY_SEPARATOR);
                $success++;
            }
            if (file_exists($cache . 'paymentgateway')) {
                File::copyFolder($cache . 'paymentgateway' . DIRECTORY_SEPARATOR, $PAYMENTGATEWAY_PATH . DIRECTORY_SEPARATOR);
                $success++;
            }
            if (file_exists($cache . 'theme')) {
                File::copyFolder($cache . 'theme' . DIRECTORY_SEPARATOR, $UI_PATH . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR);
                $success++;
            }
            if (file_exists($cache . 'device')) {
                File::copyFolder($cache . 'device' . DIRECTORY_SEPARATOR, $DEVICE_PATH . DIRECTORY_SEPARATOR);
                $success++;
            }
            if ($success == 0) {
                // old plugin and theme using this
                $check = strtolower($plugin);
                if (strpos($check, 'plugin') !== false) {
                    File::copyFolder($cache, $PLUGIN_PATH . DIRECTORY_SEPARATOR);
                } else if (strpos($check, 'payment') !== false) {
                    File::copyFolder($cache, $PAYMENTGATEWAY_PATH . DIRECTORY_SEPARATOR);
                } else if (strpos($check, 'theme') !== false) {
                    rename($cache, $UI_PATH . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . $plugin);
                } else if (strpos($check, 'device') !== false) {
                    File::copyFolder($cache, $DEVICE_PATH . DIRECTORY_SEPARATOR);
                }
            }
            //Cleaning
            File::deleteFolder($cache);
            r2(getUrl('pluginmanager'), 's', 'Installation success');
        } else if (_post('gh_url', '') != '') {
            $ghUrl = _post('gh_url', '');
            $plugin = basename(rtrim($ghUrl, '/'));
            $file = $cache . $plugin . '.zip';
            $err = '';
            if (!downloadAndExtractGitHubZip($ghUrl, $file, $cache, $err)) {
                r2(getUrl('pluginmanager'), 'e', $err);
            }
            $folder = $cache . DIRECTORY_SEPARATOR . $plugin . '-main' . DIRECTORY_SEPARATOR;
            if (!file_exists($folder)) {
                $folder = $cache . DIRECTORY_SEPARATOR . $plugin . '-master' . DIRECTORY_SEPARATOR;
            }
            if (!file_exists($folder)) {
                $folder = $cache;
            }
            $success = 0;
            if (file_exists($folder . 'plugin')) {
                File::copyFolder($folder . 'plugin' . DIRECTORY_SEPARATOR, $PLUGIN_PATH . DIRECTORY_SEPARATOR);
                $success++;
            }
            if (file_exists($folder . 'paymentgateway')) {
                File::copyFolder($folder . 'paymentgateway' . DIRECTORY_SEPARATOR, $PAYMENTGATEWAY_PATH . DIRECTORY_SEPARATOR);
                $success++;
            }
            if (file_exists($folder . 'theme')) {
                File::copyFolder($folder . 'theme' . DIRECTORY_SEPARATOR, $UI_PATH . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR);
                $success++;
            }
            if (file_exists($folder . 'device')) {
                File::copyFolder($folder . 'device' . DIRECTORY_SEPARATOR, $DEVICE_PATH . DIRECTORY_SEPARATOR);
                $success++;
            }
            if ($success == 0) {
                $check = strtolower($ghUrl);
                if (strpos($check, 'plugin') !== false) {
                    File::copyFolder($folder, $PLUGIN_PATH . DIRECTORY_SEPARATOR);
                } else if (strpos($check, 'payment') !== false) {
                    File::copyFolder($folder, $PAYMENTGATEWAY_PATH . DIRECTORY_SEPARATOR);
                } else if (strpos($check, 'theme') !== false) {
                    rename($folder, $UI_PATH . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . $plugin);
                } else if (strpos($check, 'device') !== false) {
                    File::copyFolder($folder, $DEVICE_PATH . DIRECTORY_SEPARATOR);
                }
            }
            File::deleteFolder($cache);
            r2(getUrl('pluginmanager'), 's', 'Installation success');
        } else {
            r2(getUrl('pluginmanager'), 'e', 'Nothing Installed');
        }
        break;
    case 'delete':
        if ($_app_stage == 'Demo') {
            r2(getUrl('pluginmanager'), 'e', 'You cannot perform this action in Demo mode');
        }
        if (!is_writeable($CACHE_PATH)) {
            r2(getUrl('pluginmanager'), 'e', 'Folder cache/ is not writable');
        }
        if (!is_writeable($PLUGIN_PATH)) {
            r2(getUrl('pluginmanager'), 'e', 'Folder plugin/ is not writable');
        }
        set_time_limit(-1);
        $tipe = $routes['2'];
        $plugin = $routes['3'];
        $file = $CACHE_PATH . DIRECTORY_SEPARATOR . $plugin . '.zip';
        if (file_exists($file))
            unlink($file);
        if ($tipe == 'plugin') {
            foreach ($json['plugins'] as $plg) {
                if ($plg['id'] == $plugin) {
                    $err = '';
                    if (!downloadAndExtractGitHubZip($plg['github'], $file, $CACHE_PATH, $err)) {
                        $directFolder = $PLUGIN_PATH . DIRECTORY_SEPARATOR . $plugin;
                        if (file_exists($directFolder)) {
                            File::deleteFolder($directFolder);
                            r2(getUrl('pluginmanager'), 's', 'Plugin ' . $plugin . ' has been deleted');
                        }
                        r2(getUrl('pluginmanager'), 'e', $err);
                    }
                    $folder = $CACHE_PATH . File::pathFixer('/' . $plugin . '-main/');
                    if (!file_exists($folder)) {
                        $folder = $CACHE_PATH . File::pathFixer('/' . $plugin . '-master/');
                    }
                    if (file_exists($folder)) {
                        scanAndRemovePath($folder, $PLUGIN_PATH . DIRECTORY_SEPARATOR);
                        File::deleteFolder($folder);
                    }
                    if (file_exists($file)) {
                        unlink($file);
                    }
                    r2(getUrl('pluginmanager'), 's', 'Plugin ' . $plugin . ' has been deleted');
                    break;
                }
            }
            break;
        }
        break;
    case 'install':
        if ($_app_stage == 'Demo') {
            r2(getUrl('pluginmanager'), 'e', 'You cannot perform this action in Demo mode');
        }
        if (!is_writeable($CACHE_PATH)) {
            r2(getUrl('pluginmanager'), 'e', 'Folder cache/ is not writable');
        }
        if (!is_writeable($PLUGIN_PATH)) {
            r2(getUrl('pluginmanager'), 'e', 'Folder plugin/ is not writable');
        }
        set_time_limit(-1);
        $tipe = $routes['2'];
        $plugin = $routes['3'];
        $file = $CACHE_PATH . DIRECTORY_SEPARATOR . $plugin . '.zip';
        if (file_exists($file))
            unlink($file);
        if ($tipe == 'plugin') {
            foreach ($json['plugins'] as $plg) {
                if ($plg['id'] == $plugin) {
                    $err = '';
                    if (!downloadAndExtractGitHubZip($plg['github'], $file, $CACHE_PATH, $err)) {
                        r2(getUrl('pluginmanager'), 'e', $err);
                    }
                    $folder = $CACHE_PATH . File::pathFixer('/' . $plugin . '-main/');
                    if (!file_exists($folder)) {
                        $folder = $CACHE_PATH . File::pathFixer('/' . $plugin . '-master/');
                    }
                    if (!file_exists($folder)) {
                        r2(getUrl('pluginmanager'), 'e', 'Extracted Folder is unknown');
                    }
                    File::copyFolder($folder, $PLUGIN_PATH . DIRECTORY_SEPARATOR, ['README.md', 'LICENSE']);
                    File::deleteFolder($folder);
                    if (file_exists($file)) {
                        unlink($file);
                    }
                    r2(getUrl('pluginmanager'), 's', 'Plugin ' . $plugin . ' has been installed');
                    break;
                }
            }
            break;
        } else if ($tipe == 'payment') {
            foreach ($json['payment_gateway'] as $plg) {
                if ($plg['id'] == $plugin) {
                    $err = '';
                    if (!downloadAndExtractGitHubZip($plg['github'], $file, $CACHE_PATH, $err)) {
                        r2(getUrl('pluginmanager'), 'e', $err);
                    }
                    $folder = $CACHE_PATH . File::pathFixer('/' . $plugin . '-main/');
                    if (!file_exists($folder)) {
                        $folder = $CACHE_PATH . File::pathFixer('/' . $plugin . '-master/');
                    }
                    if (!file_exists($folder)) {
                        r2(getUrl('pluginmanager'), 'e', 'Extracted Folder is unknown');
                    }
                    File::copyFolder($folder, $PAYMENTGATEWAY_PATH . DIRECTORY_SEPARATOR, ['README.md', 'LICENSE']);
                    File::deleteFolder($folder);
                    if (file_exists($file)) {
                        unlink($file);
                    }
                    r2(getUrl('paymentgateway'), 's', 'Payment Gateway ' . $plugin . ' has been installed');
                    break;
                }
            }
            break;
        } else if ($tipe == 'device') {
            foreach ($json['devices'] as $d) {
                if ($d['id'] == $plugin) {
                    $err = '';
                    if (!downloadAndExtractGitHubZip($d['github'], $file, $CACHE_PATH, $err)) {
                        r2(getUrl('pluginmanager'), 'e', $err);
                    }
                    $folder = $CACHE_PATH . File::pathFixer('/' . $plugin . '-main/');
                    if (!file_exists($folder)) {
                        $folder = $CACHE_PATH . File::pathFixer('/' . $plugin . '-master/');
                    }
                    if (!file_exists($folder)) {
                        r2(getUrl('pluginmanager'), 'e', 'Extracted Folder is unknown');
                    }
                    File::copyFolder($folder, $DEVICE_PATH . DIRECTORY_SEPARATOR, ['README.md', 'LICENSE']);
                    File::deleteFolder($folder);
                    if (file_exists($file)) {
                        unlink($file);
                    }
                    r2(getUrl('settings/devices'), 's', 'Device ' . $plugin . ' has been installed');
                    break;
                }
            }
            break;
        }
    default:
        if (class_exists('ZipArchive')) {
            $zipExt = true;
        } else {
            $zipExt = false;
        }
        $ui->assign('zipExt', $zipExt);
        $ui->assign('plugins', $json['plugins']);
        $ui->assign('pgs', $json['payment_gateway']);
        $ui->assign('dvcs', $json['devices']);
        $ui->display('admin/settings/plugin-manager.tpl');
}


function scanAndRemovePath($source, $target)
{
    $files = scandir($source);
    foreach ($files as $file) {
        if (is_file($source . $file)) {
            if (file_exists($target . $file)) {
                unlink($target . $file);
            }
        } else if (is_dir($source . $file) && !in_array($file, ['.', '..'])) {
            scanAndRemovePath($source . $file . DIRECTORY_SEPARATOR, $target . $file . DIRECTORY_SEPARATOR);
            if (file_exists($target . $file)) {
                rmdir($target . $file);
            }
        }
    }
    if (file_exists($target)) {
        rmdir($target);
    }
}
