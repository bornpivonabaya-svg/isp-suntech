<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{$_title} - {$_c['CompanyName']}</title>
    <link rel="shortcut icon" href="{$app_url}/ui/ui/images/logo.png" type="image/x-icon" />
    <script>
        var appUrl = '{$app_url}';
    </script>
    <link rel="stylesheet" href="{$app_url}/ui/ui/styles/bootstrap.min.css">
    <link rel="stylesheet" href="{$app_url}/ui/ui/fonts/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="{$app_url}/ui/ui/styles/sweetalert2.min.css" />
    <link rel="stylesheet" href="{$app_url}/ui/ui/styles/suntech.css?2026.10.2e" />
    <script src="{$app_url}/ui/ui/scripts/sweetalert2.all.min.js"></script>
</head>

<body class="st-auth">
    {if isset($notify)}
        <script>
            Swal.fire({
                icon: '{if $notify_t == "s"}success{else}warning{/if}',
                title: '{$notify}',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 5000,
                timerProgressBar: true
            });
        </script>
    {/if}
    <div class="st-auth-wrap">
        <section class="st-auth-hero">
            <div class="brand">
                <img src="{$app_url}/{$login_logo|replace:'\\':'/'}" alt="" onerror="this.style.display='none'">
                <span>{$_c['CompanyName']}</span>
            </div>
            <div>
                <h1>{Lang::T('Fast, reliable internet')} <span>{Lang::T('for every home')}</span>.</h1>
                <p class="lead">{Lang::T('Buy packages, pay with M-Pesa and manage your connection from one place.')}</p>
                {$Announcement = "{$PAGES_PATH}/Announcement.html"}
                {if file_exists($Announcement)}
                    <div class="announce">{include file=$Announcement}</div>
                {/if}
                <div class="features">
                    <span><i class="fa fa-bolt"></i> {Lang::T('Instant activation')}</span>
                    <span><i class="fa fa-mobile"></i> M-Pesa</span>
                    <span><i class="fa fa-wifi"></i> Hotspot &amp; Home</span>
                </div>
            </div>
            <div class="foot">&copy; {$smarty.now|date_format:"%Y"} {$_c['CompanyName']}{if $_c['phone']} &middot; {$_c['phone']}{/if}</div>
        </section>

        <section class="st-auth-form">
            <div class="st-auth-card">
                <h2>{Lang::T('Welcome back')}</h2>
                <p class="sub">{Lang::T('Log in to your account to continue.')}</p>
                <form action="{Text::url('login/post')}" method="post">
                    <input type="hidden" name="csrf_token" value="{$csrf_token}">
                    <label for="username">
                        {if $_c['registration_username'] == 'phone'}{Lang::T('Phone Number')}
                        {elseif $_c['registration_username'] == 'email'}{Lang::T('Email')}
                        {else}{Lang::T('Username or phone')}{/if}
                    </label>
                    <div class="st-field">
                        <i class="fa {if $_c['registration_username'] == 'email'}fa-envelope{elseif $_c['registration_username'] == 'phone'}fa-mobile{else}fa-user{/if}"></i>
                        <input type="text" class="form-control" id="username" name="username" required autofocus autocomplete="username"
                            placeholder="{if $_c['registration_username'] == 'email'}you@example.com{else}07xx xxx xxx{/if}">
                    </div>
                    <label for="password">{Lang::T('Password')}</label>
                    <div class="st-field">
                        <i class="fa fa-lock"></i>
                        <input type="password" class="form-control" id="password" name="password" required autocomplete="current-password" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;">
                    </div>
                    <div class="links">
                        <span></span>
                        <a href="{Text::url('forgot')}">{Lang::T('Forgot Password')}?</a>
                    </div>
                    <button type="submit" class="btn btn-st btn-lg btn-block">{Lang::T('Login')}</button>
                    {if $_c['disable_registration'] != 'noreg'}
                        <div class="st-divider">{Lang::T('New here?')}</div>
                        <a href="{Text::url('register')}" class="btn btn-default btn-lg btn-block">{Lang::T('Create an account')}</a>
                    {/if}
                </form>
                <div class="st-legal">
                    <a href="javascript:showPrivacy()">{Lang::T('Privacy')}</a> &middot;
                    <a href="javascript:showTaC()">{Lang::T('Terms')}</a>
                </div>
            </div>
        </section>
    </div>

    <div class="modal fade" id="HTMLModal" tabindex="-1" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body" id="HTMLModal_konten"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">&times;</button>
                </div>
            </div>
        </div>
    </div>
    <script src="{$app_url}/ui/ui/scripts/vendors.js?v=1"></script>
</body>

</html>
