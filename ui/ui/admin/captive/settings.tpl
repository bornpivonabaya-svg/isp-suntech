{include file="sections/header.tpl"}

<div class="row">
    <div class="col-md-6">
        <form class="form-horizontal" method="post" action="{Text::url('captive/save')}">
            <input type="hidden" name="csrf_token" value="{$csrf_token}">
            <div class="panel panel-primary">
                <div class="panel-heading">{Lang::T('Portal Appearance')}</div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-md-4 control-label"><b>{Lang::T('Active Theme')}</b></label>
                        <div class="col-md-8">
                            <select class="form-control" name="cp_theme" id="cpTheme">
                                {foreach $themes as $tid => $thm}
                                    <option value="{$tid}" {if $cp['cp_theme'] == $tid}selected{/if} data-color="{$thm['color']}" data-color2="{$thm['color2']}">
                                        {$thm['name']} ({$thm['category']})
                                    </option>
                                {/foreach}
                            </select>
                            <p class="help-block"><small class="text-muted">{Lang::T('Select any theme from the SunTech catalog. The live phone preview updates instantly.')}</small></p>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-md-12">
                            <div class="row" style="margin-left:-4px;margin-right:-4px;max-height:220px;overflow-y:auto;border:1px solid #e2e8f0;border-radius:10px;padding:8px;background:#f8fafc;">
                                {foreach $themes as $tid => $thm}
                                <div class="col-xs-6 col-sm-4" style="padding:3px">
                                    <div class="theme-card {if $cp['cp_theme'] == $tid}active{/if}" data-theme="{$tid}" style="border:2px solid {if $cp['cp_theme'] == $tid}#0284c7{else}#cbd5e1{/if};border-radius:8px;padding:7px;background:#fff;cursor:pointer;min-height:85px;position:relative;transition:all .15s">
                                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:3px">
                                            <span style="display:inline-block;width:13px;height:13px;border-radius:50%;background:{$thm['color']};border:1px solid rgba(0,0,0,0.1)"></span>
                                            <span class="badge" style="font-size:9px;background:{if $thm['appearance']=='dark'}#1e293b{else}#e2e8f0{/if};color:{if $thm['appearance']=='dark'}#fff{else}#334155{/if}">{$thm['appearance']}</span>
                                        </div>
                                        <b style="font-size:11px;display:block;line-height:1.2;color:#0f172a">{$thm['name']}</b>
                                        <small style="font-size:9.5px;color:#64748b;display:block;margin-top:2px;line-height:1.2">{$thm['category']}</small>
                                    </div>
                                </div>
                                {/foreach}
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-4 control-label">{Lang::T('Title')}</label>
                        <div class="col-md-8"><input type="text" class="form-control" name="cp_title" value="{$cp['cp_title']|escape}"></div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-4 control-label">{Lang::T('Tagline')}</label>
                        <div class="col-md-8"><input type="text" class="form-control" name="cp_tagline" value="{$cp['cp_tagline']|escape}"></div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-4 control-label">{Lang::T('Colors')}</label>
                        <div class="col-md-4"><input type="color" class="form-control" name="cp_color" value="{$cp['cp_color']|escape}" title="{Lang::T('Accent')}"></div>
                        <div class="col-md-4"><input type="color" class="form-control" name="cp_color2" value="{$cp['cp_color2']|escape}" title="{Lang::T('Header')}"></div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-4 control-label">{Lang::T('Support Phone')}</label>
                        <div class="col-md-8"><input type="text" class="form-control" name="cp_phone" value="{$cp['cp_phone']|escape}" placeholder="+255 7xx xxx xxx"></div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-4 control-label">WhatsApp</label>
                        <div class="col-md-8"><input type="text" class="form-control" name="cp_whatsapp" value="{$cp['cp_whatsapp']|escape}" placeholder="2557xxxxxxxx"></div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-4 control-label">{Lang::T('Notice')}</label>
                        <div class="col-md-8"><textarea class="form-control" name="cp_notice" rows="2" placeholder="{Lang::T('Optional announcement shown on the login page')}">{$cp['cp_notice']|escape}</textarea></div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-4 control-label">{Lang::T('Show')}</label>
                        <div class="col-md-8">
                            <label class="checkbox-inline"><input type="checkbox" name="cp_show_voucher" value="1" {if $cp['cp_show_voucher']=='yes'}checked{/if}> {Lang::T('Voucher login')}</label>
                            <label class="checkbox-inline"><input type="checkbox" name="cp_show_member" value="1" {if $cp['cp_show_member']=='yes'}checked{/if}> {Lang::T('Account login')}</label><br>
                            <label class="checkbox-inline"><input type="checkbox" name="cp_show_plans" value="1" {if $cp['cp_show_plans']=='yes'}checked{/if}> {Lang::T('Package prices')}</label>
                            <label class="checkbox-inline"><input type="checkbox" name="cp_show_buy" value="1" {if $cp['cp_show_buy']=='yes'}checked{/if}> {Lang::T('Buy button')}</label>
                        </div>
                    <div class="form-group">
                        <label class="col-md-4 control-label">{Lang::T('Hotspot Security')}</label>
                        <div class="col-md-8">
                            <label class="checkbox"><input type="checkbox" name="cp_block_vpn" value="1" {if $cp['cp_block_vpn']=='yes'}checked{/if}> <b>{Lang::T('Block VPN Tunnels')}</b> <small class="text-muted">(Drops WireGuard, OpenVPN, BadVPN, IPsec before login)</small></label>
                            <label class="checkbox"><input type="checkbox" name="cp_block_dns_tunnel" value="1" {if $cp['cp_block_dns_tunnel']=='yes'}checked{/if}> <b>{Lang::T('Block DNS Tunneling')}</b> <small class="text-muted">(Forces local DNS and drops external port 53 queries)</small></label>
                            <label class="checkbox"><input type="checkbox" name="cp_block_protocols" value="1" {if $cp['cp_block_protocols']=='yes'}checked{/if}> <b>{Lang::T('Block Unauthorized UDP/GRE')}</b> <small class="text-muted">(Prevents UDP tunnel bypasses before authentication)</small></label>
                            <label class="checkbox"><input type="checkbox" name="cp_mpesa_pay" value="1" {if $cp['cp_mpesa_pay']=='yes'}checked{/if}> <b>{Lang::T('Direct M-Pesa STK Push')}</b> <small class="text-muted">(Instant 1-tap payment modal on captive portal packages)</small></label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="panel panel-default">
                <div class="panel-heading">{Lang::T('Router Connection')}</div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-md-4 control-label">{Lang::T('Server URL')}</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="cp_server_url" value="{$cp['cp_server_url']|escape}">
                            {if $local_only}
                                <p class="help-block text-danger"><b>{Lang::T('localhost is only reachable from this PC.')}</b>
                                    {Lang::T('Hotspot clients and the router need the LAN address of this PC')}{if $lan_urls}, {Lang::T('for example')}:
                                    {foreach $lan_urls as $u}<br><a href="#" onclick="$('[name=cp_server_url]').val('{$u}');return false"><code>{$u}</code></a>{/foreach}{/if}
                                </p>
                            {else}
                                <p class="help-block">{Lang::T('The login page loads packages and activates vouchers from this address. It is added to the walled garden.')}</p>
                            {/if}
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-4 control-label">{Lang::T('Hotspot Folder')}</label>
                        <div class="col-md-4"><input type="text" class="form-control" name="cp_prefix" value="{$cp['cp_prefix']|escape}" placeholder="flash/" title="{Lang::T('Path prefix')}"></div>
                        <div class="col-md-4"><input type="text" class="form-control" name="cp_dir" value="{$cp['cp_dir']|escape}"></div>
                        <p class="help-block col-md-offset-4 col-md-8">{Lang::T('Prefix: leave empty, or flash/ on routers that store files in flash.')}</p>
                    </div>
                    <div class="form-group">
                        <label class="col-md-4 control-label">{Lang::T('Extra Walled Garden')}</label>
                        <div class="col-md-8">
                            <textarea class="form-control" name="cp_walled_extra" rows="2" placeholder="*.safaricom.co.ke, api.payment.com">{$cp['cp_walled_extra']|escape}</textarea>
                            <p class="help-block">{Lang::T('Hosts reachable before login, like your payment gateway.')}</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-md-offset-4 col-md-8">
                            <button class="btn btn-primary" type="submit">{Lang::T('Save Changes')}</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <div class="panel panel-success">
            <div class="panel-heading">{Lang::T('Install on Router')}</div>
            <div class="panel-body">
                {if !$routers}
                    <p>{Lang::T('Add a router first')}: <a href="{Text::url('routers/add')}">{Lang::T('Routers')}</a></p>
                {else}
                    <form method="post" id="pushForm" class="form-horizontal" action="">
                        <div class="form-group">
                            <label class="col-md-4 control-label">{Lang::T('Router')}</label>
                            <div class="col-md-8">
                                <select class="form-control" id="cpRouter">
                                    {foreach $routers as $r}<option value="{$r['id']}">{$r['name']} ({$r['ip_address']})</option>{/foreach}
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-md-offset-4 col-md-8">
                                <label class="checkbox-inline"><input type="checkbox" name="all_profiles" id="cpAll" value="1"> {Lang::T('Use on all existing hotspot profiles')}</label>
                            </div>
                        </div>
                        <div class="btn-group btn-group-justified" role="group">
                            <a class="btn btn-default" id="cpZip" href="#"><i class="fa fa-file-archive-o"></i> ZIP</a>
                            <a class="btn btn-default" id="cpScript" href="#" target="_blank"><i class="fa fa-file-code-o"></i> {Lang::T('Script')}</a>
                            <a class="btn btn-success" id="cpPush" href="#"><i class="fa fa-upload"></i> {Lang::T('Push to Router')}</a>
                        </div>
                    </form>
                    <p class="help-block" style="margin-top:10px">
                        <b>ZIP</b>: {Lang::T('extract and drag the folder into Winbox Files.')}
                        <b>{Lang::T('Script')}</b>: {Lang::T('walled garden and file download commands to paste in the terminal.')}
                        <b>{Lang::T('Push')}</b>: {Lang::T('does it all over the API.')}
                        {Lang::T('Hotspot VLANs created in')} <a href="{Text::url('vlan/list')}">VLANs</a> {Lang::T('already point to this folder.')}
                    </p>
                {/if}
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="pull-right">
                    <select id="pvPage" class="input-sm">
                        <option value="login.html">login</option>
                        <option value="login.html" data-error="1">login (error)</option>
                        <option value="login.html" data-trial="1">login (trial on)</option>
                        <option value="alogin.html">alogin</option>
                        <option value="status.html">status</option>
                        <option value="logout.html">logout</option>
                        <option value="error.html" data-error="1">error</option>
                    </select>
                    <a class="btn btn-default btn-xs" id="pvOpen" target="_blank" href="#"><i class="fa fa-external-link"></i></a>
                </div>
                {Lang::T('Live Preview')} <small class="text-muted">({Lang::T('save to refresh')})</small>
            </div>
            <div class="panel-body" style="background:#e9ecf1; text-align:center">
                <iframe id="pv" style="width: 380px; max-width: 100%; height: 700px; border: 8px solid #222; border-radius: 28px; background: #fff"></iframe>
            </div>
        </div>
    </div>
</div>

<script>
    var cpBase = '{Text::url('captive/')}';
    var cpQs = cpBase.indexOf('?') >= 0 ? '&' : '?';
</script>
{literal}
<script>
    document.addEventListener('DOMContentLoaded', function () {
    function rid() { return $('#cpRouter').val() || 0; }
    function currentTheme() { return $('#cpTheme').val() || 'suntech-blue'; }
    function pvUrl() {
        var o = $('#pvPage option:selected');
        var theme = currentTheme();
        return cpBase + 'preview/' + rid() + '/' + o.val() + cpQs + 'theme=' + encodeURIComponent(theme) + (o.data('error') ? '&error=1' : '') + (o.data('trial') ? '&trial=1' : '');
    }
    function refreshLinks() {
        var u = pvUrl();
        $('#pv').attr('src', u); $('#pvOpen').attr('href', u);
        var all = $('#cpAll').is(':checked') ? 1 : 0;
        $('#cpZip').attr('href', cpBase + 'download/' + rid());
        $('#cpScript').attr('href', cpBase + 'script/' + rid() + cpQs + 'all=' + all);
    }
    $('#pvPage, #cpRouter, #cpAll, #cpTheme').on('change input', refreshLinks);
    $('.theme-card').on('click', function() {
        var t = $(this).data('theme');
        $('#cpTheme').val(t).trigger('change');
        $('.theme-card').css('border-color', '#cbd5e1').removeClass('active');
        $(this).css('border-color', '#0284c7').addClass('active');
        var opt = $('#cpTheme option:selected');
        if (opt.data('color')) $('[name=cp_color]').val(opt.data('color'));
        if (opt.data('color2')) $('[name=cp_color2]').val(opt.data('color2'));
    });
    $('#cpPush').on('click', function (e) {
        e.preventDefault();
        if (!confirm('Install the captive portal on this router now?')) return;
        $('#pushForm').attr('action', cpBase + 'push/' + rid()).submit();
    });
    refreshLinks();
    });
</script>
{/literal}

{include file="sections/footer.tpl"}
