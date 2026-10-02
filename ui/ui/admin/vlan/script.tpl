{include file="sections/header.tpl"}

<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <div class="btn-group pull-right">
                    <button class="btn btn-default btn-xs" type="button" onclick="copyScript(this)"><i class="fa fa-copy"></i> {Lang::T('Copy')}</button>
                    <a class="btn btn-success btn-xs" href="{$download_url}"><i class="fa fa-download"></i> {$file}</a>
                </div>
                {Lang::T('RouterOS Script')}
            </div>
            <div class="panel-body">
                <p>{Lang::T('Paste into Winbox / WebFig terminal, or upload the file to the router and run')} <code>/import {$file}</code>.
                    {Lang::T('It is safe to run more than once, existing items are updated.')}</p>
                <textarea id="rsc" class="form-control" rows="22" readonly style="font-family: monospace; font-size: 12px; white-space: pre">{$script|escape}</textarea>
                <br>
                <a href="{Text::url('vlan/list')}" class="btn btn-default">{Lang::T('Back')}</a>
            </div>
        </div>
    </div>
</div>
{literal}
<script>
    function copyScript(btn) {
        var t = document.getElementById('rsc');
        t.select();
        (navigator.clipboard ? navigator.clipboard.writeText(t.value) : Promise.reject()).catch(function () { document.execCommand('copy'); });
        btn.innerHTML = '<i class="fa fa-check"></i> Copied';
    }
</script>
{/literal}

{include file="sections/footer.tpl"}
