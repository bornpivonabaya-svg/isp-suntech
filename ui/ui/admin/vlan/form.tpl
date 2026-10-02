{include file="sections/header.tpl"}

<form class="form-horizontal" method="post" role="form" action="{Text::url('vlan/save')}">
    <input type="hidden" name="csrf_token" value="{$csrf_token}">
    <input type="hidden" name="id" value="{if $d}{$d['id']}{/if}">
    <div class="row">
        <div class="col-md-7">
            <div class="panel panel-primary panel-hovered panel-stacked mb30">
                <div class="panel-heading">{if $d}{Lang::T('Edit VLAN')} {$d['vlan_id']}{else}{Lang::T('New VLAN')}{/if}</div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Status')}</label>
                        <div class="col-md-9">
                            <label class="radio-inline"><input type="radio" name="enabled" value="1" {if !$d || $d['enabled']}checked{/if}> {Lang::T('Enable')}</label>
                            <label class="radio-inline"><input type="radio" name="enabled" value="0" {if $d && !$d['enabled']}checked{/if}> {Lang::T('Disable')}</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label"><a href="{Text::url('routers/add')}">{Lang::T('Router')}</a></label>
                        <div class="col-md-9">
                            <select id="router_id" name="router_id" class="form-control" required>
                                {foreach $routers as $r}
                                    <option value="{$r['id']}" {if $d && $d['router_id']==$r['id']}selected{/if}>{$r['name']} ({$r['ip_address']})</option>
                                {foreachelse}
                                    <option value="">{Lang::T('Add a router first')}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">VLAN ID</label>
                        <div class="col-md-3">
                            <input type="number" min="1" max="4094" class="form-control" id="vlan_id" name="vlan_id" required
                                value="{if $d}{$d['vlan_id']}{/if}" placeholder="10">
                        </div>
                        <label class="col-md-2 control-label">{Lang::T('Name')}</label>
                        <div class="col-md-4">
                            <input type="text" class="form-control" id="name" name="name" maxlength="32" required
                                value="{if $d}{$d['name']}{/if}" placeholder="Hotspot-Town">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Parent Interface')}</label>
                        <div class="col-md-9">
                            <div class="input-group">
                                <input type="text" class="form-control" id="parent_interface" name="parent_interface" required
                                    list="iface_list" value="{if $d}{$d['parent_interface']}{else}ether2{/if}" placeholder="ether2 / bridge-lan">
                                <datalist id="iface_list"></datalist>
                                <span class="input-group-btn">
                                    <button class="btn btn-default" type="button" id="loadIface"><i class="fa fa-download"></i> {Lang::T('Load from router')}</button>
                                </span>
                            </div>
                            <p class="help-block" id="ifaceHelp">{Lang::T('Physical port or bridge carrying the tagged VLAN')}</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Service')}</label>
                        <div class="col-md-9">
                            <select name="service" id="service" class="form-control">
                                {foreach ['Hotspot' => 'Hotspot (Captive Portal)', 'PPPoE' => 'PPPoE Server', 'None' => 'None (plain VLAN)'] as $k => $label}
                                    <option value="{$k}" {if ($d && $d['service']==$k) || (!$d && $k=='Hotspot')}selected{/if}>{$label}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Gateway')} (CIDR)</label>
                        <div class="col-md-9">
                            <input type="text" class="form-control" id="gateway" name="gateway" required
                                value="{if $d}{$d['gateway']}{/if}" placeholder="10.10.10.1/24">
                            <p class="help-block" id="cidrHelp">{Lang::T('Router IP on this VLAN, with prefix length')}</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('IP Pool Range')}</label>
                        <div class="col-md-9">
                            <input type="text" class="form-control" id="pool_range" name="pool_range"
                                value="{if $d}{$d['pool_range']}{/if}" placeholder="{Lang::T('auto from gateway')}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">DHCP</label>
                        <div class="col-md-3">
                            <label class="checkbox-inline"><input type="checkbox" name="dhcp" value="1" {if !$d || $d['dhcp']}checked{/if}> {Lang::T('Enable DHCP server')}</label>
                        </div>
                        <label class="col-md-2 control-label">DNS</label>
                        <div class="col-md-4">
                            <input type="text" class="form-control" name="dns" value="{if $d}{$d['dns']}{/if}" placeholder="{Lang::T('router')}">
                        </div>
                    </div>
                    <div class="form-group" id="dnsNameGroup">
                        <label class="col-md-3 control-label">{Lang::T('Hotspot DNS Name')}</label>
                        <div class="col-md-9">
                            <input type="text" class="form-control" name="dns_name" value="{if $d}{$d['dns_name']}{/if}" placeholder="login.suntech.net">
                            <p class="help-block">{Lang::T('Optional. Customers can open this name to reach the login page.')}</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{Lang::T('Description')}</label>
                        <div class="col-md-9">
                            <input type="text" class="form-control" name="description" maxlength="256" value="{if $d}{$d['description']}{/if}">
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-md-offset-3 col-md-9">
                            <button class="btn btn-primary" type="submit">{Lang::T('Save')}</button>
                            <button class="btn btn-success" type="submit" name="sync_now" value="1">{Lang::T('Save & Send to Router')}</button>
                            {Lang::T('Or')} <a href="{Text::url('vlan/list')}">{Lang::T('Cancel')}</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-5">
            <div class="panel panel-default">
                <div class="panel-heading">{Lang::T('What will be created on the router')}</div>
                <div class="panel-body">
                    <ul id="willCreate" style="padding-left: 18px; margin: 0"></ul>
                </div>
            </div>
            <div class="bs-callout bs-callout-warning">
                <h4>{Lang::T('Switch ports')}</h4>
                <p>{Lang::T('Your switch or access points must tag this VLAN ID towards the router parent interface. Untagged clients will not reach this VLAN.')}</p>
            </div>
        </div>
    </div>
</form>

<script>
    var vlanIfaceUrl = '{Text::url('vlan/interfaces/')}';
</script>
{literal}
<script>
    document.addEventListener('DOMContentLoaded', function () {
    function ipToInt(ip) {
        var p = ip.split('.').map(Number);
        if (p.length != 4 || p.some(function (x) { return isNaN(x) || x < 0 || x > 255; })) return null;
        return ((p[0] << 24) >>> 0) + (p[1] << 16) + (p[2] << 8) + p[3];
    }
    function intToIp(n) {
        return [n >>> 24, (n >>> 16) & 255, (n >>> 8) & 255, n & 255].join('.');
    }
    function cidrInfo(v) {
        var m = /^(\d+\.\d+\.\d+\.\d+)\/(\d+)$/.exec(v.trim());
        if (!m) return null;
        var ip = ipToInt(m[1]), pre = +m[2];
        if (ip === null || pre < 8 || pre > 30) return null;
        var mask = (0xFFFFFFFF << (32 - pre)) >>> 0, net = (ip & mask) >>> 0, bc = (net | (~mask >>> 0)) >>> 0;
        if (ip == net || ip == bc) return null;
        var first = net + 1, last = bc - 1;
        if (ip == first) first++; else if (ip == last) last--;
        return { gw: m[1], net: intToIp(net) + '/' + pre, pool: intToIp(first) + '-' + intToIp(last), hosts: last - first + 1 };
    }
    function refresh() {
        var vid = $('#vlan_id').val() || '?', name = ($('#name').val() || '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
        var iface = 'vlan' + vid + (name ? '-' + name : '');
        var c = cidrInfo($('#gateway').val() || '');
        $('#cidrHelp').html(c ? 'Network <b>' + c.net + '</b>, ' + c.hosts + ' client addresses' : 'Router IP on this VLAN, with prefix length');
        if (c) $('#pool_range').attr('placeholder', c.pool);
        var svc = $('#service').val(), dhcp = $('input[name=dhcp]').is(':checked');
        $('#dnsNameGroup').toggle(svc == 'Hotspot');
        var li = [];
        li.push('VLAN interface <b>' + iface + '</b> (tag ' + vid + ') on <b>' + ($('#parent_interface').val() || '?') + '</b>');
        li.push('IP address <b>' + ($('#gateway').val() || '?') + '</b>');
        li.push('IP pool <b>pool-vlan' + vid + '</b> ' + ($('#pool_range').val() || (c ? c.pool : '')));
        if (dhcp) li.push('DHCP server <b>dhcp-vlan' + vid + '</b>');
        if (svc == 'Hotspot') li.push('Hotspot server <b>hs-vlan' + vid + '</b> using the SunTech captive portal');
        if (svc == 'PPPoE') li.push('PPPoE server <b>pppoe-vlan' + vid + '</b>');
        $('#willCreate').html('<li>' + li.join('</li><li>') + '</li>');
    }
    $('form input, form select').on('input change', refresh);
    refresh();

    $('#loadIface').on('click', function () {
        var btn = $(this).prop('disabled', true);
        $('#ifaceHelp').text('Connecting to router...');
        $.getJSON(vlanIfaceUrl + $('#router_id').val(), function (r) {
            if (!r.ok) { $('#ifaceHelp').html('<span class="text-danger">' + $('<i>').text(r.error).html() + '</span> - type the interface name manually.'); return; }
            var dl = $('#iface_list').empty();
            r.interfaces.forEach(function (i) { if (i.type != 'vlan') dl.append($('<option>').val(i.name).text(i.type)); });
            $('#ifaceHelp').text(r.interfaces.length + ' interfaces loaded, click the field to pick one.');
        }).fail(function () { $('#ifaceHelp').html('<span class="text-danger">Request failed</span>'); })
          .always(function () { btn.prop('disabled', false); });
    });
    });
</script>
{/literal}

{include file="sections/footer.tpl"}
