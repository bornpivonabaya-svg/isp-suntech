{include file="sections/header.tpl"}
<!-- vlan -->
<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-hovered mb20 panel-primary">
            <div class="panel-heading">
                {if $router_id}
                    <div class="btn-group pull-right">
                        <a class="btn btn-primary btn-xs" href="{Text::url('vlan/script/router/', $router_id)}"><i
                                class="fa fa-file-code-o"></i> {Lang::T('Router Script')}</a>
                        <a class="btn btn-warning btn-xs" href="{Text::url('vlan/sync-all/', $router_id)}"
                            onclick="return ask(this, '{Lang::T('Send all VLANs of this router to Mikrotik')}?')"><i
                                class="glyphicon glyphicon-refresh"></i> {Lang::T('Sync All')}</a>
                    </div>
                {/if}
                {Lang::T('VLANs')}
            </div>
            <div class="panel-body">
                <div class="md-whiteframe-z1 mb20 text-center" style="padding: 15px">
                    <div class="col-md-8">
                        <form method="get" action="">
                            <input type="hidden" name="_route" value="vlan/list">
                            <div class="input-group">
                                <div class="input-group-addon"><span class="fa fa-server"></span></div>
                                <select name="router" class="form-control" onchange="this.form.submit()">
                                    <option value="0">{Lang::T('All Routers')}</option>
                                    {foreach $routers as $r}
                                        <option value="{$r['id']}" {if $router_id==$r['id']}selected{/if}>{$r['name']}</option>
                                    {/foreach}
                                </select>
                            </div>
                        </form>
                    </div>
                    <div class="col-md-4">
                        <a href="{Text::url('vlan/add')}" class="btn btn-primary btn-block"><i
                                class="ion ion-android-add"> </i> {Lang::T('New VLAN')}</a>
                    </div>&nbsp;
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-condensed">
                        <thead>
                            <tr>
                                <th>VLAN ID</th>
                                <th>{Lang::T('Name')}</th>
                                <th>{Lang::T('Router')}</th>
                                <th>{Lang::T('Parent Interface')}</th>
                                <th>{Lang::T('Service')}</th>
                                <th>{Lang::T('Gateway')}</th>
                                <th>{Lang::T('Pool')}</th>
                                <th>{Lang::T('Status')}</th>
                                <th>{Lang::T('Manage')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach $d as $ds}
                                <tr {if !$ds['enabled']}class="text-muted" {/if}>
                                    <td><b>{$ds['vlan_id']}</b></td>
                                    <td>{$ds['name']}{if $ds['description']}<br><small class="text-muted">{$ds['description']}</small>{/if}</td>
                                    <td>{if isset($routers[$ds['router_id']])}{$routers[$ds['router_id']]['name']}{else}<span class="label label-danger">{Lang::T('Router deleted')}</span>{/if}</td>
                                    <td>{$ds['parent_interface']}</td>
                                    <td>
                                        {if $ds['service']=='Hotspot'}<span class="label label-info">Hotspot</span>
                                        {elseif $ds['service']=='PPPoE'}<span class="label label-primary">PPPoE</span>
                                        {else}<span class="label label-default">{Lang::T('None')}</span>{/if}
                                        {if $ds['dhcp']}<span class="label label-default">DHCP</span>{/if}
                                    </td>
                                    <td>{$ds['gateway']}</td>
                                    <td><small>{if $ds['pool_range']}{$ds['pool_range']}{else}<i>{Lang::T('auto')}</i>{/if}</small></td>
                                    <td title="{$ds['sync_message']|escape}">
                                        {if $ds['sync_status']=='synced'}<span class="label label-success">{Lang::T('Synced')}</span>
                                        {elseif $ds['sync_status']=='error'}<span class="label label-danger">{Lang::T('Error')}</span>
                                        {else}<span class="label label-warning">{Lang::T('Pending')}</span>{/if}
                                        {if $ds['last_sync']}<br><small class="text-muted">{$ds['last_sync']}</small>{/if}
                                    </td>
                                    <td align="center" style="white-space: nowrap">
                                        <a href="{Text::url('vlan/sync/', $ds['id'])}" class="btn btn-success btn-xs"
                                            title="{Lang::T('Send to router')}"
                                            onclick="return ask(this, '{Lang::T('Send this VLAN to Mikrotik')}?')"><i
                                                class="glyphicon glyphicon-refresh"></i></a>
                                        <a href="{Text::url('vlan/script/', $ds['id'])}" class="btn btn-default btn-xs"
                                            title="{Lang::T('RouterOS Script')}"><i class="fa fa-file-code-o"></i></a>
                                        <a href="{Text::url('vlan/edit/', $ds['id'])}"
                                            class="btn btn-info btn-xs">{Lang::T('Edit')}</a>
                                        {if $ds['sync_status']=='synced'}
                                            <a href="{Text::url('vlan/unsync/', $ds['id'])}" class="btn btn-warning btn-xs"
                                                title="{Lang::T('Remove from router, keep here')}"
                                                onclick="return ask(this, '{Lang::T('Remove this VLAN from Mikrotik')}?')"><i
                                                    class="fa fa-unlink"></i></a>
                                        {/if}
                                        <a href="{Text::url('vlan/delete/', $ds['id'])}"
                                            onclick="return ask(this, '{Lang::T('Delete')}? {Lang::T('It will also be removed from the router')}')"
                                            class="btn btn-danger btn-xs"><i class="glyphicon glyphicon-trash"></i></a>
                                    </td>
                                </tr>
                            {foreachelse}
                                <tr>
                                    <td colspan="9" class="text-center text-muted">{Lang::T('No VLAN yet')}</td>
                                </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>
                {include file="pagination.tpl"}
                <div class="bs-callout bs-callout-info">
                    <h4>{Lang::T('How it works')}</h4>
                    <p>{Lang::T('Each VLAN creates a VLAN interface on the parent port, a gateway IP, an IP pool, a DHCP server and a Hotspot (captive portal) or PPPoE server.')}
                        {Lang::T('Hotspot VLANs use the SunTech captive portal pages, set them up in')} <a
                            href="{Text::url('captive')}">{Lang::T('Captive Portal')}</a>.</p>
                    <p>{Lang::T('No router connected? Use the script button and paste it in the Mikrotik terminal.')}</p>
                </div>
            </div>
        </div>
    </div>
</div>

{include file="sections/footer.tpl"}
