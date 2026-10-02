<?php

/**
 *  SunTech ISP - Safaricom M-Pesa (Daraja API) client
 *
 *  STK Push (Lipa na M-Pesa Online) for PayBill or Till (Buy Goods).
 *  Settings live in tbl_appconfig with the mpesa_ prefix.
 *  Docs: https://developer.safaricom.co.ke
 **/

class Mpesa
{
    const SANDBOX_SHORTCODE = '174379';
    const SANDBOX_PASSKEY = 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919';

    public static function config()
    {
        global $config;
        $c = [
            'env' => 'sandbox',
            'consumer_key' => '',
            'consumer_secret' => '',
            'shortcode' => '',
            'passkey' => '',
            'type' => 'CustomerPayBillOnline', // or CustomerBuyGoodsOnline
            'till' => '',                       // PartyB for Buy Goods
            'account_ref' => '',
            'callback_url' => '',
            'callback_key' => '',
        ];
        foreach ($c as $k => $v) {
            if (isset($config['mpesa_' . $k]) && $config['mpesa_' . $k] !== '') {
                $c[$k] = $config['mpesa_' . $k];
            }
        }
        if ($c['env'] == 'sandbox') {
            if ($c['shortcode'] == '') {
                $c['shortcode'] = self::SANDBOX_SHORTCODE;
            }
            if ($c['passkey'] == '') {
                $c['passkey'] = self::SANDBOX_PASSKEY;
            }
        }
        if ($c['account_ref'] == '') {
            $c['account_ref'] = substr(preg_replace('~[^A-Za-z0-9]~', '', $config['CompanyName']), 0, 12) ?: 'SunTechISP';
        }
        return $c;
    }

    public static function isConfigured()
    {
        $c = self::config();
        return $c['consumer_key'] != '' && $c['consumer_secret'] != '' && $c['shortcode'] != '' && $c['passkey'] != '';
    }

    public static function baseUrl()
    {
        return self::config()['env'] == 'production' ? 'https://api.safaricom.co.ke' : 'https://sandbox.safaricom.co.ke';
    }

    /**
     * Callback URL Safaricom posts the result to. Must be public HTTPS in production.
     */
    public static function callbackUrl()
    {
        $c = self::config();
        $url = $c['callback_url'] ?: APP_URL . '/index.php?_route=callback/mpesa';
        return $url . (strpos($url, '?') === false ? '?' : '&') . 'k=' . self::callbackKey();
    }

    /**
     * Secret in the callback URL so random posts are rejected.
     */
    public static function callbackKey()
    {
        global $db_pass, $db_name;
        $c = self::config();
        return $c['callback_key'] ?: substr(sha1('mpesa-callback|' . $db_name . '|' . $db_pass . '|' . $c['consumer_key']), 0, 20);
    }

    /**
     * 07xx / 01xx / +2547xx / 2547xx  ->  2547xxxxxxxx ; false when not a Kenyan mobile number.
     */
    public static function normalizePhone($phone)
    {
        $p = preg_replace('~\D~', '', (string) $phone);
        if (preg_match('~^0([17]\d{8})$~', $p, $m)) {
            $p = '254' . $m[1];
        } elseif (preg_match('~^([17]\d{8})$~', $p, $m)) {
            $p = '254' . $m[1];
        }
        return preg_match('~^254[17]\d{8}$~', $p) ? $p : false;
    }

    private static function request($method, $path, $body = null, $token = null, $basic = null)
    {
        $ch = curl_init(self::baseUrl() . $path);
        $headers = ['Accept: application/json'];
        if ($token) {
            $headers[] = 'Authorization: Bearer ' . $token;
        }
        if ($basic) {
            $headers[] = 'Authorization: Basic ' . $basic;
        }
        if ($body !== null) {
            $headers[] = 'Content-Type: application/json';
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);
        $raw = curl_exec($ch);
        $err = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($raw === false) {
            throw new Exception('M-Pesa connection failed: ' . $err);
        }
        $json = json_decode($raw, true);
        if (!is_array($json)) {
            throw new Exception('M-Pesa returned HTTP ' . $code . ': ' . substr(strip_tags($raw), 0, 200));
        }
        $json['_http'] = $code;
        return $json;
    }

    /**
     * OAuth token, cached until shortly before it expires.
     */
    public static function token()
    {
        global $CACHE_PATH;
        $c = self::config();
        $cache = $CACHE_PATH . DIRECTORY_SEPARATOR . 'mpesa_token_' . md5($c['env'] . $c['consumer_key']) . '.json';
        if (file_exists($cache)) {
            $t = json_decode(file_get_contents($cache), true);
            if ($t && $t['expires'] > time() + 60) {
                return $t['token'];
            }
        }
        try {
            $r = self::request('GET', '/oauth/v1/generate?grant_type=client_credentials', null, null, base64_encode($c['consumer_key'] . ':' . $c['consumer_secret']));
        } catch (Exception $e) {
            // Daraja answers wrong credentials with an empty 400
            throw new Exception('M-Pesa login failed, check Consumer Key, Secret and Environment (' . $e->getMessage() . ')');
        }
        if (empty($r['access_token'])) {
            throw new Exception('M-Pesa login failed, check Consumer Key and Secret (' . ($r['errorMessage'] ?? ('HTTP ' . $r['_http'])) . ')');
        }
        file_put_contents($cache, json_encode(['token' => $r['access_token'], 'expires' => time() + (int) $r['expires_in']]));
        return $r['access_token'];
    }

    private static function password($timestamp)
    {
        $c = self::config();
        return base64_encode($c['shortcode'] . $c['passkey'] . $timestamp);
    }

    /**
     * Send the PIN prompt to the customer's phone. Returns Daraja response (CheckoutRequestID...).
     */
    public static function stkPush($phone, $amount, $reference, $description)
    {
        $c = self::config();
        $ts = date('YmdHis');
        $amount = max(1, (int) ceil($amount));
        $body = [
            'BusinessShortCode' => $c['shortcode'],
            'Password' => self::password($ts),
            'Timestamp' => $ts,
            'TransactionType' => $c['type'],
            'Amount' => $amount,
            'PartyA' => $phone,
            'PartyB' => ($c['type'] == 'CustomerBuyGoodsOnline' && $c['till'] != '') ? $c['till'] : $c['shortcode'],
            'PhoneNumber' => $phone,
            'CallBackURL' => self::callbackUrl(),
            'AccountReference' => substr($reference, 0, 12),
            'TransactionDesc' => substr($description, 0, 13),
        ];
        $r = self::request('POST', '/mpesa/stkpush/v1/processrequest', $body, self::token());
        if (!isset($r['ResponseCode']) || $r['ResponseCode'] !== '0') {
            throw new Exception($r['errorMessage'] ?? $r['ResponseDescription'] ?? ('STK push rejected, HTTP ' . $r['_http']));
        }
        return $r;
    }

    /**
     * Ask Safaricom for the result of a prompt.
     * Returns ['state' => 'paid'|'pending'|'failed', 'code' => .., 'message' => .., 'raw' => ..]
     */
    public static function stkQuery($checkoutRequestId)
    {
        $c = self::config();
        $ts = date('YmdHis');
        $r = self::request('POST', '/mpesa/stkpushquery/v1/query', [
            'BusinessShortCode' => $c['shortcode'],
            'Password' => self::password($ts),
            'Timestamp' => $ts,
            'CheckoutRequestID' => $checkoutRequestId,
        ], self::token());
        if (isset($r['ResultCode'])) {
            return [
                'state' => (string) $r['ResultCode'] === '0' ? 'paid' : 'failed',
                'code' => (string) $r['ResultCode'],
                'message' => $r['ResultDesc'] ?? '',
                'raw' => $r,
            ];
        }
        // "The transaction is being processed" comes back as an error until the customer answers
        return ['state' => 'pending', 'code' => $r['errorCode'] ?? '', 'message' => $r['errorMessage'] ?? 'Waiting for customer', 'raw' => $r];
    }

    public static function resultMessage($code, $fallback = '')
    {
        $m = [
            '0' => 'Payment received',
            '1' => 'Not enough M-Pesa balance',
            '1032' => 'You cancelled the M-Pesa prompt',
            '1037' => 'Phone could not be reached, make sure it is on and try again',
            '2001' => 'Wrong M-Pesa PIN',
            '1001' => 'Another M-Pesa transaction is in progress on this phone, try again shortly',
            '1019' => 'The prompt expired, please try again',
        ];
        return isset($m[(string) $code]) ? $m[(string) $code] : ($fallback ?: 'Payment not completed');
    }

    /**
     * Activate the package for a paid transaction (idempotent).
     */
    public static function markPaid($trx, $receipt, $raw)
    {
        if ($trx['status'] == 2) {
            return true;
        }
        $user = ORM::for_table('tbl_customers')->find_one($trx['user_id']);
        if (!$user) {
            $user = ORM::for_table('tbl_customers')->where('username', $trx['username'])->find_one();
        }
        if (!$user) {
            throw new Exception('Customer not found for transaction ' . $trx['id']);
        }
        // rechargeUser writes the invoice number into the global $trx
        $GLOBALS['trx'] = $trx;
        if (!Package::rechargeUser($user['id'], $trx['routers'], $trx['plan_id'], $trx['gateway'], 'M-Pesa')) {
            throw new Exception('Failed to activate package');
        }
        $trx->pg_paid_response = json_encode($raw);
        $trx->payment_method = 'M-Pesa';
        $trx->payment_channel = $receipt ?: 'STK';
        $trx->paid_date = date('Y-m-d H:i:s');
        $trx->status = 2;
        $trx->save();
        _log('M-Pesa payment ' . $receipt . ' for ' . $trx['username'] . ' ' . $trx['plan_name'] . ' ' . $trx['price'], 'Customer', $user['id']);
        return true;
    }
}
