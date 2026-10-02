<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{Lang::T('Login')} - {$_c['CompanyName']}</title>
    <link rel="shortcut icon" href="{$app_url}/ui/ui/images/logo.png" type="image/x-icon" />
    <link rel="stylesheet" href="{$app_url}/ui/ui/styles/bootstrap.min.css">
    <link rel="stylesheet" href="{$app_url}/ui/ui/fonts/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="{$app_url}/ui/ui/styles/suntech.css?2026.10.2e" />
</head>

<body class="st-auth">
    <div class="st-auth-wrap">
        <section class="st-auth-hero">
            <div class="brand">
                <img src="{$app_url}/system/uploads/logo.default.png" alt="" onerror="this.style.display='none'">
                <span>{$_c['CompanyName']}</span>
            </div>
            <div>
                <h1>{Lang::T('ISP control')} <span>{Lang::T('center')}</span>.</h1>
                <p class="lead">{Lang::T('Customers, hotspot and PPPoE plans, routers, VLANs and M-Pesa payments in one dashboard.')}</p>
                <div class="features">
                    <span><i class="fa fa-server"></i> MikroTik</span>
                    <span><i class="fa fa-sitemap"></i> VLANs</span>
                    <span><i class="fa fa-wifi"></i> {Lang::T('Captive Portal')}</span>
                    <span><i class="fa fa-mobile"></i> M-Pesa</span>
                </div>
            </div>
            <div class="foot">&copy; {$smarty.now|date_format:"%Y"} {$_c['CompanyName']}</div>
        </section>

        <section class="st-auth-form">
            <div class="st-auth-card">
                <h2>{Lang::T('Admin sign in')}</h2>
                <p class="sub">{Lang::T('Enter Admin Area')}</p>
                {if isset($notify)}
                    <div class="alert alert-{if $notify_t == 's'}success{else}danger{/if}">{$notify}</div>
                {/if}
                <form action="{Text::url('admin/post')}" method="post">
                    <input type="hidden" name="csrf_token" value="{$csrf_token}">
                    <label for="username">{Lang::T('Username')}</label>
                    <div class="st-field">
                        <i class="fa fa-user"></i>
                        <input type="text" required autofocus class="form-control" id="username" name="username" autocomplete="username" placeholder="admin">
                    </div>
                    <label for="password">{Lang::T('Password')}</label>
                    <div class="st-field">
                        <i class="fa fa-lock"></i>
                        <input type="password" required class="form-control" id="password" name="password" autocomplete="current-password" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;">
                    </div>
                    <button type="submit" class="btn btn-st btn-lg btn-block" style="margin-top:8px">{Lang::T('Login')}</button>
                </form>
                <div class="st-legal"><a href="{Text::url('login')}">&larr; {Lang::T('Customer login')}</a></div>
            </div>
        </section>
    </div>
</body>

</html>
