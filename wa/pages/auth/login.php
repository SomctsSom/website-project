<?php
declare(strict_types=1);

if (wa_user()) {
    wa_redirect('/dashboard');
}

$error = '';
if (request_is_post()) {
    $login = trim((string) ($_POST['login'] ?? $_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $res = wa_login($login, $password);
    if (wa_api_ok($res)) {
        wa_redirect('/dashboard');
    }
    $error = (string) ($res['message'] ?? 'Login failed');
}

ob_start();
?>
<div class="auth-wrap">
  <div class="auth-card">
    <h1>Sign in</h1>
    <p class="muted">Website Admin — authenticate against Website Core</p>
    <?php if ($error): ?><div class="alert alert-error"><?= wa_e($error) ?></div><?php endif; ?>
    <form method="post" class="form-grid" style="margin-top:1rem">
      <label class="full">Name or email
        <input type="text" name="login" required autocomplete="username" value="<?= wa_e($_POST['login'] ?? $_POST['email'] ?? '') ?>" placeholder="Super Admin or you@example.com">
      </label>
      <label class="full">Password
        <input type="password" name="password" required autocomplete="current-password">
      </label>
      <div class="actions full">
        <button class="btn" type="submit">Login</button>
      </div>
    </form>
  </div>
</div>
<?php
$content = ob_get_clean();
// Minimal layout without nav
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login · Website Admin</title>
  <link rel="stylesheet" href="<?= wa_e(wa_url('/assets/css/admin.css')) ?>">
</head>
<body><?= $content ?></body>
</html>
<?php
exit;

function request_is_post(): bool
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}
