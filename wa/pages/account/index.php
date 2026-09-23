<?php
declare(strict_types=1);

wa_require_login();
$message = '';
$error = '';

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $res = wa_api('POST', '/api/auth/change-password', [
        'current_password' => $_POST['current_password'] ?? '',
        'new_password' => $_POST['new_password'] ?? '',
        'confirm_password' => $_POST['confirm_password'] ?? '',
    ]);
    if (wa_api_ok($res)) {
        wa_flash('success', 'Password updated');
        wa_redirect('/account');
    }
    $error = (string) ($res['message'] ?? 'Update failed');
}

$user = wa_user();
ob_start();
?>
<div class="page-head"><div><h1>Account</h1><p>Change your password.</p></div></div>
<?php if ($error): ?><div class="alert alert-error"><?= wa_e($error) ?></div><?php endif; ?>
<div class="panel">
  <p><strong><?= wa_e($user['name'] ?? '') ?></strong> · <?= wa_e($user['email'] ?? '') ?> · <?= wa_e($user['role_name'] ?? '') ?></p>
  <form method="post" class="form-grid" style="margin-top:1rem">
    <?= wa_csrf_field() ?>
    <label>Current password<input type="password" name="current_password" required></label>
    <label>New password<input type="password" name="new_password" required minlength="10"></label>
    <label>Confirm password<input type="password" name="confirm_password" required minlength="10"></label>
    <div class="actions full"><button class="btn" type="submit">Update password</button></div>
  </form>
</div>
<?php
wa_render('Account', ob_get_clean());
