{include file="customer/header.tpl"}

<div class="row">
    <div class="col-md-6 col-md-offset-3">
        <div class="box box-solid mpesa-card">
            <div class="mpesa-head">
                <div class="mpesa-logo">M<span>-</span>PESA</div>
                <div class="mpesa-amount">{Lang::moneyFormat($trx['price'])}</div>
                <div class="mpesa-plan">{$trx['plan_name']}{if $trx['routers'] && $trx['routers'] != 'balance'} &middot; {$trx['routers']}{/if}</div>
            </div>
            <div class="box-body">
                <div id="mpState" class="mpesa-state {if !$sent}hidden{/if}">
                    <div class="mpesa-spinner"></div>
                    <h4 id="mpTitle">{Lang::T('Check your phone')}</h4>
                    <p id="mpText" class="text-muted">{Lang::T('Enter your M-Pesa PIN on the prompt we sent to')} <b>{$phone}</b>.</p>
                </div>
                {if $last_error}
                    <div class="alert alert-danger" id="mpError">{$last_error}</div>
                {else}
                    <div class="alert alert-danger hidden" id="mpError"></div>
                {/if}
                <form method="post" action="{Text::url('mpesa/pay/', $trx['id'])}">
                    <label for="phone">{Lang::T('M-Pesa phone number')}</label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-addon">+254</span>
                        <input type="tel" class="form-control" id="phone" name="phone" value="{$phone}" placeholder="0712 345 678" required inputmode="numeric">
                    </div>
                    <button type="submit" class="btn btn-mpesa btn-lg btn-block" style="margin-top:12px">
                        {if $sent}{Lang::T('Resend prompt')}{else}{Lang::T('Pay with M-Pesa')}{/if}
                    </button>
                </form>
                {if $sandbox}
                    <p class="text-center text-muted" style="margin-top:12px"><small><i class="fa fa-flask"></i> {Lang::T('Sandbox mode: no real money is charged.')}</small></p>
                {/if}
            </div>
            <div class="box-footer text-center">
                <a href="{Text::url('order/view/', $trx['id'], '/cancel')}" class="text-muted" onclick="return ask(this, '{Lang::T('Cancel it?')}')">{Lang::T('Cancel payment')}</a>
            </div>
        </div>
    </div>
</div>

<script>
    var mpStatusUrl = '{Text::url('mpesa/status/', $trx['id'])}';
    var mpPolling = {if $sent}true{else}false{/if};
</script>
{literal}
<script>
    (function () {
        if (!mpPolling) return;
        var tries = 0;
        function poll() {
            tries++;
            var x = new XMLHttpRequest();
            x.open('GET', mpStatusUrl);
            x.onload = function () {
                var r = {};
                try { r = JSON.parse(x.responseText); } catch (e) {}
                if (r.state === 'paid') {
                    document.getElementById('mpTitle').textContent = 'Payment received!';
                    document.getElementById('mpText').textContent = 'Activating your package...';
                    document.querySelector('.mpesa-spinner').className = 'mpesa-done';
                    setTimeout(function () { location.href = r.url; }, 1200);
                    return;
                }
                if (r.state === 'failed') {
                    document.getElementById('mpState').className = 'mpesa-state hidden';
                    var e = document.getElementById('mpError');
                    e.textContent = r.message + '. You can try again.';
                    e.className = 'alert alert-danger';
                    return;
                }
                if (tries < 60) setTimeout(poll, 4000);
                else document.getElementById('mpText').textContent = 'Still waiting. If you paid, use Check for Payment on your order.';
            };
            x.onerror = function () { if (tries < 60) setTimeout(poll, 6000); };
            x.send();
        }
        setTimeout(poll, 5000);
    })();
</script>
{/literal}

{include file="customer/footer.tpl"}
