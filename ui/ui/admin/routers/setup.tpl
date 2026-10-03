{include file="sections/header.tpl"}

<div class="row">
    <div class="col-md-8">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <div class="btn-group pull-right">
                    <button class="btn btn-default btn-xs" type="button" onclick="copyScript(this)"><i class="fa fa-copy"></i> {Lang::T('Copy')}</button>
                </div>
                {Lang::T('Setup Script')}: {$d['name']}
            </div>
            <div class="panel-body">
                {if $local_only}
                    <div class="alert alert-danger">
                        <b>{Lang::T('The router cannot reach localhost.')}</b>
                        {Lang::T('Set the address of this PC on your network, then copy the script.')}
                        <form method="post" action="{Text::url('routers/server-url')}" class="form-inline" style="margin-top:8px">
                            <input type="hidden" name="id" value="{$d['id']}">
                            <select name="server_url" class="form-control input-sm">
                                {foreach $lan_urls as $u}<option>{$u}</option>{/foreach}
                            </select>
                            <button class="btn btn-sm btn-primary" type="submit">{Lang::T('Use this address')}</button>
                        </form>
                    </div>
                {/if}
                <div class="alert alert-success" style="background:#eafaf1; border:1px solid #2ecc71; color:#1e8449; border-radius:6px; padding:15px; margin-bottom:20px;">
                    <h4 style="margin-top:0; font-weight:bold;"><i class="fa fa-flash"></i> 1-Click Router Setup (Recommended)</h4>
                    <p style="margin-bottom:10px; font-size:13px;">Paste into <b>Winbox &gt; New Terminal</b> (or SSH). Downloads and installs the complete configuration (API, Hotspot, Walled Garden &amp; Captive Portal) cleanly without terminal crashes:</p>
                    <div class="input-group">
                        <input type="text" id="loaderCmd" class="form-control" readonly value="/tool fetch url=&quot;{$loader_url}&quot; dst-path=&quot;suntech.rsc&quot;; :delay 2s; /import suntech.rsc; /file remove suntech.rsc" style="font-family: monospace; font-size:12px; font-weight: bold; background: #fff; color: #222;">
                        <span class="input-group-btn">
                            <button class="btn btn-success" type="button" onclick="copyLoader(this)"><i class="fa fa-copy"></i> {Lang::T('Copy Command')}</button>
                        </span>
                    </div>
                    <p style="margin-top:8px; margin-bottom:0; font-size:11px; color:#555;">
                        <i class="fa fa-info-circle"></i> <i>For low-memory routers (hAP lite / RouterOS v6), you can also run in 2 steps:</i><br>
                        <code>/tool fetch url="{$loader_url}" dst-path="suntech.rsc"</code><br>
                        <code>/import suntech.rsc; /file remove suntech.rsc</code>
                    </p>
                </div>

                <details style="margin-bottom:15px">
                    <summary style="cursor:pointer; font-weight:bold; color:#337ab7"><i class="fa fa-code"></i> View / Copy Full Plaintext Script (Optional)</summary>
                    <div style="margin-top:10px">
                        <textarea id="rsc" class="form-control" rows="12" readonly style="font-family: monospace; font-size: 11px; white-space: pre">{$script|escape}</textarea>
                    </div>
                </details>

                <p class="help-block">
                    {Lang::T('Billing server address used')}: <code>{$server_url}</code>
                    {if !$local_only}&middot; <a href="#" onclick="$('#changeUrl').toggle();return false">{Lang::T('change')}</a>{/if}
                </p>
                <form method="post" action="{Text::url('routers/server-url')}" class="form-inline" id="changeUrl" style="display:none">
                    <input type="hidden" name="id" value="{$d['id']}">
                    <input type="text" name="server_url" class="form-control input-sm" style="width:320px" value="{$server_url}">
                    <button class="btn btn-sm btn-primary" type="submit">{Lang::T('Save')}</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="panel panel-default">
            <div class="panel-heading">{Lang::T('Router Status')}</div>
            <div class="panel-body text-center">
                <h3 id="st" style="margin-top:0">
                    {if $d['ip_address']}<span class="label label-success">{Lang::T('Connected')}</span>
                    {else}<span class="label label-warning">{Lang::T('Waiting for router')}</span>{/if}
                </h3>
                <p>{Lang::T('IP Address')}: <b id="stIp">{if $d['ip_address']}{$d['ip_address']}{else}-{/if}</b></p>
                <p>{Lang::T('Last seen')}: <b id="stSeen">{if $d['last_seen']}{$d['last_seen']}{else}-{/if}</b></p>
                <a href="{Text::url('routers/list')}" class="btn btn-default btn-block">{Lang::T('Back to Routers')}</a>
            </div>
        </div>
        <div class="bs-callout bs-callout-info">
            <h4>{Lang::T('How it works')}</h4>
            <p>{Lang::T('The script configures WAN internet access, enables wireless Wi-Fi (SSID: SunTech WiFi) on a LAN bridge, creates an API user, whitelists the billing server in Walled Garden, downloads captive portal files, and configures the Hotspot service.')}</p>
        </div>
    </div>
</div>

<script>
    var stUrl = '{Text::url('routers/setup-status/', $d['id'])}';
    var stOk = '{Lang::T('Connected')}';
</script>
{literal}
<script>
    function copyLoader(btn) {
        var t = document.getElementById('loaderCmd');
        t.select();
        (navigator.clipboard ? navigator.clipboard.writeText(t.value) : Promise.reject()).catch(function () { document.execCommand('copy'); });
        btn.innerHTML = '<i class="fa fa-check"></i> Copied!';
    }
    function copyScript(btn) {
        var t = document.getElementById('rsc');
        t.select();
        (navigator.clipboard ? navigator.clipboard.writeText(t.value) : Promise.reject()).catch(function () { document.execCommand('copy'); });
        btn.innerHTML = '<i class="fa fa-check"></i> Copied!';
    }
    document.addEventListener('DOMContentLoaded', function () {
        setInterval(function () {
            $.getJSON(stUrl, function (r) {
                if (!r || !r.ip) return;
                $('#stIp').text(r.ip);
                $('#stSeen').text(r.last_seen || '-');
                $('#st').html('<span class="label label-success">' + stOk + '</span>');
            });
        }, 4000);
    });
</script>
{/literal}

{include file="sections/footer.tpl"}
