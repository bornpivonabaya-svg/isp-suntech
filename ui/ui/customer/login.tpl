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
    <link rel="stylesheet" href="{$app_url}/ui/ui/styles/suntech.css?2026.10.2f" />
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
                <div style="display: flex; gap: 6px; background: #eef2f7; padding: 4px; border-radius: 12px; margin-bottom: 20px;">
                    <button type="button" class="cp-tab active" data-target="tab-voucher" style="flex:1; border:0; background:#fff; font-weight:700; font-size:13px; padding:10px 6px; border-radius:10px; color:#0f1f3d; box-shadow:0 1px 3px rgba(0,0,0,0.08); cursor:pointer;"><i class="fa fa-ticket"></i> Voucher</button>
                    <button type="button" class="cp-tab" data-target="tab-packages" style="flex:1; border:0; background:transparent; font-weight:600; font-size:13px; padding:10px 6px; border-radius:10px; color:#6b7385; cursor:pointer;"><i class="fa fa-bolt"></i> Packages</button>
                    <button type="button" class="cp-tab" data-target="tab-member" style="flex:1; border:0; background:transparent; font-weight:600; font-size:13px; padding:10px 6px; border-radius:10px; color:#6b7385; cursor:pointer;"><i class="fa fa-user"></i> Member</button>
                </div>

                <!-- TAB 1: VOUCHER ACTIVATION -->
                <div id="tab-voucher" class="cp-pane">
                    <h2 style="font-size:22px; font-weight:800; margin-bottom:4px;">Hotspot Voucher</h2>
                    <p class="sub" style="font-size:13px; margin-bottom:18px;">Enter your voucher code to connect instantly.</p>
                    <form action="{Text::url('login/activation')}" method="post">
                        <input type="hidden" name="csrf_token" value="{$csrf_token}">
                        <label for="voucher_only">Voucher Code</label>
                        <div class="st-field">
                            <i class="fa fa-ticket"></i>
                            <input type="text" class="form-control" id="voucher_only" name="voucher_only" required autofocus autocomplete="off" autocapitalize="characters" placeholder="e.g. ST8932" style="text-transform:uppercase; font-size:16px; font-weight:bold; letter-spacing:2px;">
                        </div>
                        <button type="submit" class="btn btn-st btn-lg btn-block" style="background:#f58c14; border-color:#f58c14; font-weight:bold; font-size:15px;"><i class="fa fa-wifi"></i> Connect to Internet</button>
                    </form>
                    <div style="text-align:center; margin-top:16px;">
                        <span style="font-size:13px; color:#6b7385;">Don't have a voucher? <a href="#" class="btn-show-packages" style="font-weight:700; color:#f58c14;">Buy a package &rarr;</a></span>
                    </div>
                </div>

                <!-- TAB 2: PACKAGES -->
                <div id="tab-packages" class="cp-pane" style="display:none;">
                    <h2 style="font-size:22px; font-weight:800; margin-bottom:4px;">Wi-Fi Packages</h2>
                    <p class="sub" style="font-size:13px; margin-bottom:16px;">Choose a package to pay via M-Pesa &amp; get online.</p>
                    <div style="display:flex; flex-direction:column; gap:10px; margin-bottom:16px;">
                        {foreach $hotspot_plans as $p}
                            <div style="border:1.5px solid #e6e9f0; border-radius:12px; padding:12px 16px; display:flex; justify-content:space-between; align-items:center; transition:all .2s; background:#fff;">
                                <div>
                                    <div style="font-weight:700; font-size:15px; color:#0f1f3d;">{$p['name_plan']}</div>
                                    <div style="font-size:12px; color:#6b7385;"><i class="fa fa-clock-o"></i> {$p['validity']} {$p['validity_unit']}</div>
                                </div>
                                <div style="text-align:right;">
                                    <div style="font-weight:800; font-size:16px; color:#16a34a;">KES {$p['price']|number_format:0}</div>
                                    <a href="{Text::url('order/package/'|cat:$p['id'])}" class="btn btn-xs btn-success" style="font-weight:700; margin-top:3px; background:#16a34a; border-color:#16a34a;"><i class="fa fa-mobile"></i> Buy</a>
                                </div>
                            </div>
                        {/foreach}
                    </div>
                    <a href="{Text::url('register')}" class="btn btn-default btn-block"><i class="fa fa-user-plus"></i> Register New Customer</a>
                </div>

                <!-- TAB 3: MEMBER LOGIN -->
                <div id="tab-member" class="cp-pane" style="display:none;">
                    <h2 style="font-size:22px; font-weight:800; margin-bottom:4px;">Member Login</h2>
                    <p class="sub" style="font-size:13px; margin-bottom:18px;">Sign in with your registered account.</p>
                    <form action="{Text::url('login/post')}" method="post">
                        <input type="hidden" name="csrf_token" value="{$csrf_token}">
                        <label for="username">
                            {if $_c['registration_username'] == 'phone'}{Lang::T('Phone Number')}
                            {elseif $_c['registration_username'] == 'email'}{Lang::T('Email')}
                            {else}{Lang::T('Username or phone')}{/if}
                        </label>
                        <div class="st-field">
                            <i class="fa {if $_c['registration_username'] == 'email'}fa-envelope{elseif $_c['registration_username'] == 'phone'}fa-mobile{else}fa-user{/if}"></i>
                            <input type="text" class="form-control" id="username" name="username" required autocomplete="username"
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
                </div>

                <div class="st-legal" style="margin-top:20px;">
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
    <script>
        document.querySelectorAll('.cp-tab').forEach(function(btn) {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.cp-tab').forEach(function(b) {
                    b.style.background = 'transparent';
                    b.style.color = '#6b7385';
                    b.style.boxShadow = 'none';
                    b.classList.remove('active');
                });
                this.style.background = '#fff';
                this.style.color = '#0f1f3d';
                this.style.boxShadow = '0 1px 3px rgba(0,0,0,0.08)';
                this.classList.add('active');

                document.querySelectorAll('.cp-pane').forEach(function(p) {
                    p.style.display = 'none';
                });
                var target = document.getElementById(this.getAttribute('data-target'));
                if (target) target.style.display = 'block';
            });
        });
        document.querySelectorAll('.btn-show-packages').forEach(function(el) {
            el.addEventListener('click', function(e) {
                e.preventDefault();
                var pkgTab = document.querySelector('[data-target="tab-packages"]');
                if (pkgTab) pkgTab.click();
            });
        });
    </script>
</body>

</html>
