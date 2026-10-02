{include file="sections/header.tpl"}

<form class="form-horizontal" method="post" role="form" action="{Text::url('paymentgateway/mpesa')}">
    <div class="row">
        <div class="col-md-7">
            <div class="panel panel-success">
                <div class="panel-heading">M-Pesa (Safaricom Daraja STK Push)</div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-md-4 control-label">{Lang::T('Environment')}</label>
                        <div class="col-md-8">
                            <select class="form-control" name="mpesa_env">
                                <option value="sandbox" {if $mp['env']=='sandbox'}selected{/if}>Sandbox (testing)</option>
                                <option value="production" {if $mp['env']=='production'}selected{/if}>Production (live)</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-4 control-label">Consumer Key</label>
                        <div class="col-md-8"><input type="text" class="form-control" name="mpesa_consumer_key" value="{$mp['consumer_key']|escape}" autocomplete="off"></div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-4 control-label">Consumer Secret</label>
                        <div class="col-md-8"><input type="password" class="form-control" name="mpesa_consumer_secret" value="{$mp['consumer_secret']|escape}" autocomplete="new-password"
                                onmouseenter="this.type='text'" onmouseleave="this.type='password'"></div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-4 control-label">{Lang::T('Account Type')}</label>
                        <div class="col-md-8">
                            <select class="form-control" name="mpesa_type" id="mpType">
                                <option value="CustomerPayBillOnline" {if $mp['type']=='CustomerPayBillOnline'}selected{/if}>PayBill</option>
                                <option value="CustomerBuyGoodsOnline" {if $mp['type']=='CustomerBuyGoodsOnline'}selected{/if}>Till Number (Buy Goods)</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-4 control-label">{Lang::T('Business Shortcode')}</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="mpesa_shortcode" value="{$mp['shortcode']|escape}" placeholder="174379">
                            <p class="help-block">{Lang::T('PayBill number, or the Store / HO number for a Till. Sandbox uses 174379.')}</p>
                        </div>
                    </div>
                    <div class="form-group" id="tillRow">
                        <label class="col-md-4 control-label">{Lang::T('Till Number')}</label>
                        <div class="col-md-8"><input type="text" class="form-control" name="mpesa_till" value="{$mp['till']|escape}"></div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-4 control-label">Passkey</label>
                        <div class="col-md-8"><input type="password" class="form-control" name="mpesa_passkey" value="{$mp['passkey']|escape}"
                                onmouseenter="this.type='text'" onmouseleave="this.type='password'"></div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-4 control-label">{Lang::T('Account Reference')}</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="mpesa_account_ref" value="{$mp['account_ref']|escape}" maxlength="12">
                            <p class="help-block">{Lang::T('Shown to the customer on the M-Pesa prompt (max 12 characters).')}</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-4 control-label">{Lang::T('Callback URL')}</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="mpesa_callback_url" value="{$mp['callback_url']|escape}" placeholder="https://billing.example.co.ke/index.php?_route=callback/mpesa">
                            <p class="help-block">{Lang::T('Leave empty to use this server. Safaricom needs a public https address.')}<br>
                                {Lang::T('Current')}: <code style="word-break:break-all">{$callback}</code></p>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-md-offset-4 col-md-8">
                            <button class="btn btn-primary" type="submit">{Lang::T('Save Changes')}</button>
                            <button class="btn btn-success" type="submit" name="test_token" value="1">{Lang::T('Save & Test Connection')}</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-5">
            <div class="bs-callout bs-callout-info">
                <h4>{Lang::T('Getting credentials')}</h4>
                <ol style="padding-left:18px">
                    <li>{Lang::T('Create an account at')} <a href="https://developer.safaricom.co.ke" target="_blank">developer.safaricom.co.ke</a>.</li>
                    <li>{Lang::T('Create an app with Lipa na M-Pesa Sandbox, copy the Consumer Key and Secret here.')}</li>
                    <li>{Lang::T('Sandbox: leave Shortcode and Passkey empty, the test values are used. Pay with the sandbox test phone number.')}</li>
                    <li>{Lang::T('Going live: apply for Go-Live on the portal, then enter your PayBill/Till, Passkey and production keys.')}</li>
                </ol>
            </div>
            <div class="bs-callout bs-callout-warning">
                <h4>{Lang::T('Testing on this PC')}</h4>
                <p>{Lang::T('Safaricom cannot call back to localhost. Payments are still confirmed because the checkout page asks Safaricom for the result every few seconds.')}</p>
            </div>
            <div class="bs-callout bs-callout-success">
                <h4>{Lang::T('Enable it')}</h4>
                <p>{Lang::T('Tick mpesa in')} <a href="{Text::url('paymentgateway')}">{Lang::T('Payment Gateway')}</a> {Lang::T('and save, then customers see it when buying a package.')}</p>
            </div>
        </div>
    </div>
</form>
{literal}
<script>
    document.addEventListener('DOMContentLoaded', function () {
        function t() { $('#tillRow').toggle($('#mpType').val() == 'CustomerBuyGoodsOnline'); }
        $('#mpType').on('change', t); t();
    });
</script>
{/literal}

{include file="sections/footer.tpl"}
