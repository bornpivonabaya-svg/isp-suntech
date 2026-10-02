<?php

/**
 *  SunTech ISP - M-Pesa checkout page for customers
 *
 *  mpesa/pay/<trx id>      waiting page, POST phone to (re)send the prompt
 *  mpesa/status/<trx id>   JSON polled by the page
 **/

_auth();
$user = User::_info();
$ui->assign('_user', $user);
$ui->assign('_system_menu', 'order');

require_once $PAYMENTGATEWAY_PATH . DIRECTORY_SEPARATOR . 'mpesa.php';

$trx = ORM::for_table('tbl_payment_gateway')
    ->where('username', $user['username'])->where('gateway', 'mpesa')
    ->find_one((int) $routes['2']);
if (!$trx) {
    r2(getUrl('order/package'), 'e', Lang::T('Transaction Not found'));
}

switch ($routes['1']) {
    case 'pay':
        if ($trx['status'] == 2) {
            r2(getUrl('order/view/' . $trx['id']), 's', Lang::T('Transaction has been paid.'));
        }
        if ($trx['status'] != 1) {
            r2(getUrl('order/view/' . $trx['id']), 'w', Lang::T('Transaction Not found'));
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $phone = Mpesa::normalizePhone(_post('phone'));
            if (!$phone) {
                r2(getUrl('mpesa/pay/' . $trx['id']), 'e', 'Enter a valid Safaricom number, like 0712 345 678');
            }
            try {
                mpesa_send_prompt($trx, $phone);
                r2(getUrl('mpesa/pay/' . $trx['id']), 's', 'Prompt sent to ' . $phone . ', enter your M-Pesa PIN');
            } catch (Throwable $e) {
                r2(getUrl('mpesa/pay/' . $trx['id']), 'e', htmlspecialchars($e->getMessage()));
            }
        }
        $phone = $trx['payment_channel'] ?: Mpesa::normalizePhone($user['phonenumber']);
        $last = json_decode($trx['pg_paid_response'], true);
        $ui->assign('trx', $trx);
        $ui->assign('phone', $phone ? '0' . substr($phone, 3) : '');
        $ui->assign('sent', !empty($trx['gateway_trx_id']));
        $ui->assign('last_error', !empty($last['code']) ? Mpesa::resultMessage($last['code'], $last['message']) : '');
        $ui->assign('sandbox', Mpesa::config()['env'] == 'sandbox');
        $ui->assign('_title', 'M-Pesa');
        $ui->display('customer/mpesa-pay.tpl');
        break;

    case 'status':
        header('Content-Type: application/json');
        if ($trx['status'] == 2) {
            die(json_encode(['state' => 'paid', 'url' => getUrl('order/view/' . $trx['id'])]));
        }
        if (empty($trx['gateway_trx_id'])) {
            die(json_encode(['state' => 'idle']));
        }
        // callback may already have reported a failure
        $last = json_decode($trx['pg_paid_response'], true);
        if (!empty($last['code'])) {
            die(json_encode(['state' => 'failed', 'message' => Mpesa::resultMessage($last['code'], $last['message'])]));
        }
        // ask Safaricom at most every 5 seconds per transaction
        $key = 'mpesa_q_' . $trx['id'];
        if (!empty($_SESSION[$key]) && $_SESSION[$key] > time() - 5) {
            die(json_encode(['state' => 'pending']));
        }
        $_SESSION[$key] = time();
        $res = mpesa_check($trx);
        if ($res['state'] == 'paid') {
            $res['url'] = getUrl('order/view/' . $trx['id']);
        }
        die(json_encode($res));

    default:
        r2(getUrl('order/package'));
}
