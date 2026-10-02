<?php

/**
 *  SunTech ISP - M-Pesa payment gateway (Safaricom Daraja STK Push)
 *
 *  Flow: customer picks a package -> mpesa_create_transaction sends the PIN
 *  prompt to their phone -> mpesa/pay/<id> page waits -> payment confirmed by
 *  Safaricom callback (callback/mpesa) or by polling the STK query API.
 **/

function mpesa_validate_config()
{
    if (!Mpesa::isConfigured()) {
        sendTelegram('M-Pesa payment gateway is not configured');
        r2(getUrl('order/package'), 'w', Lang::T('M-Pesa is not available right now, please contact support'));
    }
}

function mpesa_show_config()
{
    global $ui;
    $ui->assign('_title', 'M-Pesa - Payment Gateway');
    $ui->assign('mp', Mpesa::config());
    $ui->assign('callback', Mpesa::callbackUrl());
    $ui->display('mpesa.tpl');
}

function mpesa_save_config()
{
    global $admin;
    $keys = ['env', 'consumer_key', 'consumer_secret', 'shortcode', 'passkey', 'type', 'till', 'account_ref', 'callback_url'];
    foreach ($keys as $k) {
        $v = trim(_post('mpesa_' . $k));
        if ($k == 'env') {
            $v = $v == 'production' ? 'production' : 'sandbox';
        }
        if ($k == 'type') {
            $v = $v == 'CustomerBuyGoodsOnline' ? 'CustomerBuyGoodsOnline' : 'CustomerPayBillOnline';
        }
        $d = ORM::for_table('tbl_appconfig')->where('setting', 'mpesa_' . $k)->find_one();
        if (!$d) {
            $d = ORM::for_table('tbl_appconfig')->create();
            $d->setting = 'mpesa_' . $k;
        }
        $d->value = $v;
        $d->save();
    }
    _log('[' . $admin['username'] . ']: M-Pesa ' . Lang::T('Settings_Saved_Successfully'), 'Admin', $admin['id']);
    if (_post('test_token')) {
        // reload saved values and try to log in to Daraja
        global $config;
        foreach ($keys as $k) {
            $config['mpesa_' . $k] = trim(_post('mpesa_' . $k));
        }
        try {
            Mpesa::token();
            r2(getUrl('paymentgateway/mpesa'), 's', 'Saved. Connected to M-Pesa ' . Mpesa::config()['env'] . ' successfully');
        } catch (Throwable $e) {
            r2(getUrl('paymentgateway/mpesa'), 'e', 'Saved, but M-Pesa login failed: ' . htmlspecialchars($e->getMessage()));
        }
    }
    r2(getUrl('paymentgateway/mpesa'), 's', Lang::T('Settings_Saved_Successfully'));
}

/**
 * Called right after the customer chose M-Pesa. Sends the prompt and opens the waiting page.
 */
function mpesa_create_transaction($trx, $user)
{
    $trx->pg_url_payment = getUrl('mpesa/pay/' . $trx['id']);
    $trx->expired_date = date('Y-m-d H:i:s', strtotime('+1 hour'));
    $trx->save();
    $phone = Mpesa::normalizePhone($user['phonenumber']);
    if ($phone) {
        try {
            mpesa_send_prompt($trx, $phone);
            r2(getUrl('mpesa/pay/' . $trx['id']), 's', 'Check your phone ' . $phone . ' and enter your M-Pesa PIN');
        } catch (Throwable $e) {
            r2(getUrl('mpesa/pay/' . $trx['id']), 'e', htmlspecialchars($e->getMessage()));
        }
    }
    // no valid number on the account: let the customer type one
    r2(getUrl('mpesa/pay/' . $trx['id']), 's', 'Enter your M-Pesa number to pay');
}

function mpesa_send_prompt($trx, $phone)
{
    $r = Mpesa::stkPush($phone, $trx['price'], Mpesa::config()['account_ref'], $trx['plan_name']);
    $trx->gateway_trx_id = $r['CheckoutRequestID'];
    $trx->payment_channel = $phone;
    $trx->pg_request = json_encode($r);
    $trx->pg_paid_response = '';
    $trx->save();
    return $r;
}

/**
 * "Check for Payment" button on the order page.
 */
function mpesa_get_status($trx, $user)
{
    if ($trx['status'] == 2) {
        r2(getUrl('order/view/' . $trx['id']), 's', Lang::T('Transaction has been paid.'));
    }
    if (empty($trx['gateway_trx_id'])) {
        r2(getUrl('mpesa/pay/' . $trx['id']), 'w', 'Send the M-Pesa prompt first');
    }
    $res = mpesa_check($trx);
    if ($res['state'] == 'paid') {
        r2(getUrl('order/view/' . $trx['id']), 's', Lang::T('Transaction has been paid.'));
    }
    r2(getUrl('mpesa/pay/' . $trx['id']), $res['state'] == 'failed' ? 'e' : 'w', $res['message']);
}

/**
 * Query Safaricom and activate the package when paid.
 */
function mpesa_check($trx)
{
    try {
        $q = Mpesa::stkQuery($trx['gateway_trx_id']);
    } catch (Throwable $e) {
        return ['state' => 'pending', 'message' => $e->getMessage()];
    }
    if ($q['state'] == 'paid') {
        $receipt = '';
        $cb = json_decode($trx['pg_paid_response'], true);
        if (!empty($cb['receipt'])) {
            $receipt = $cb['receipt'];
        }
        Mpesa::markPaid($trx, $receipt, ['query' => $q['raw'], 'callback' => $cb]);
        return ['state' => 'paid', 'message' => 'Payment received'];
    }
    if ($q['state'] == 'failed') {
        $trx->pg_paid_response = json_encode(['code' => $q['code'], 'message' => $q['message']]);
        $trx->save();
        return ['state' => 'failed', 'code' => $q['code'], 'message' => Mpesa::resultMessage($q['code'], $q['message'])];
    }
    return ['state' => 'pending', 'message' => 'Waiting for you to enter your M-Pesa PIN...'];
}

/**
 * Safaricom result callback: callback/mpesa?k=<key>
 */
function mpesa_payment_notification()
{
    header('Content-Type: application/json');
    $ok = json_encode(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    if (!hash_equals(Mpesa::callbackKey(), (string) _get('k'))) {
        http_response_code(403);
        die(json_encode(['ResultCode' => 1, 'ResultDesc' => 'Forbidden']));
    }
    $body = json_decode(file_get_contents('php://input'), true);
    $cb = $body['Body']['stkCallback'] ?? null;
    if (!$cb || empty($cb['CheckoutRequestID'])) {
        die($ok);
    }
    $trx = ORM::for_table('tbl_payment_gateway')->where('gateway', 'mpesa')->where('gateway_trx_id', $cb['CheckoutRequestID'])->find_one();
    if (!$trx || $trx['status'] == 2) {
        die($ok);
    }
    $meta = [];
    foreach ($cb['CallbackMetadata']['Item'] ?? [] as $item) {
        $meta[$item['Name']] = $item['Value'] ?? null;
    }
    if ((string) $cb['ResultCode'] === '0') {
        if (isset($meta['Amount']) && (float) $meta['Amount'] < (float) ceil($trx['price'])) {
            _log('M-Pesa callback amount ' . $meta['Amount'] . ' lower than ' . $trx['price'] . ' for trx ' . $trx['id'], 'Customer', $trx['user_id']);
            die($ok);
        }
        try {
            Mpesa::markPaid($trx, $meta['MpesaReceiptNumber'] ?? '', ['callback' => $cb]);
        } catch (Throwable $e) {
            sendTelegram("M-Pesa paid but activation failed\nTRX: " . $trx['id'] . "\nUser: " . $trx['username'] . "\n" . $e->getMessage());
        }
    } else {
        $trx->pg_paid_response = json_encode(['code' => (string) $cb['ResultCode'], 'message' => $cb['ResultDesc'] ?? '']);
        $trx->save();
    }
    die($ok);
}
