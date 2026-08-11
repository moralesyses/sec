<?php

require_once __DIR__ . '/auth.php';

/*
|--------------------------------------------------------------------------
| Superadmin only
|--------------------------------------------------------------------------
*/

require_login();

if (($_SESSION['role'] ?? '') !== 'superadmin') {
    header('Location: dashboard.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| CSRF token
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrf = $_SESSION['csrf_token'];

/*
|--------------------------------------------------------------------------
| Handle unlock
|--------------------------------------------------------------------------
*/

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token  = $_POST['csrf_token'] ?? '';
    $userId = (int) ($_POST['user_id'] ?? 0);

    if (!hash_equals($csrf, $token)) {
        $message = 'Invalid request. Please try again.';
    } elseif ($userId > 0) {
        $stmt = db()->prepare(
            'UPDATE users
             SET is_locked = 0, failed_attempts = 0
             WHERE id = ?'
        );

        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->close();

        $message = 'Account unlocked successfully.';
    }
}

/*
|--------------------------------------------------------------------------
| Fetch locked accounts
|--------------------------------------------------------------------------
*/

$locked = db()
    ->query(
        'SELECT id, name, username, role
         FROM users
         WHERE is_locked = 1
         ORDER BY username'
    )
    ->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>WETLI Admin | Locked Accounts</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@700;800&family=Open+Sans:wght@400;500;600&display=swap" rel="stylesheet" />
<style>
:root {
    --purple:  #572472;   /* primary */
    --yellow:  #F3CD00;   /* accent */
    --red:     #B31613;   /* secondary accent */
    --white:   #ffffff;
    --offwhite:#f5f2f7;
    --silver:  #cfc6d6;
    --dark:    #28232B;
}

* {
    box-sizing: border-box;
}

body {
    font-family: 'Open Sans', sans-serif;
    background: #f4f4f6;
    margin: 0;
    padding: 32px 24px;
}

.wrap {
    max-width: 760px;
    margin: 0 auto;
}

h1 {
    font-family: 'Poppins', sans-serif;
    font-size: 1.3rem;
    color: var(--purple);
    margin: 0 0 6px;
}

p.sub {
    color: #777;
    font-size: 0.9rem;
    margin: 0 0 22px;
}

.msg {
    background: #e8f6ef;
    color: #05603a;
    padding: 12px 14px;
    border-radius: 8px;
    font-size: 0.88rem;
    margin-bottom: 16px;
}

table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
}

th, td {
    text-align: left;
    padding: 13px 16px;
    font-size: 0.9rem;
}

th {
    background: var(--purple);
    color: #fff;
    font-family: 'Poppins', sans-serif;
    font-size: 0.82rem;
    letter-spacing: 0.03em;
}

tr + tr td {
    border-top: 1px solid #eee;
}

.role-pill {
    display: inline-block;
    background: #f3ead9;
    color: var(--gold);
    font-weight: 600;
    font-size: 0.75rem;
    padding: 3px 10px;
    border-radius: 999px;
}

button.unlock {
    background: var(--yellow);
    color: var(--purple);
    border: 0;
    padding: 8px 16px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.85rem;
    cursor: pointer;
}

.empty {
    background: #fff;
    padding: 28px;
    border-radius: 12px;
    text-align: center;
    color: #888;
    font-size: 0.92rem;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
}

a.back {
    display: inline-block;
    margin-top: 18px;
    color: var(--purple);
    font-weight: 600;
    font-size: 0.88rem;
    text-decoration: none;
}
</style>
</head>
<body>
  <div class="wrap">
    <h1>Locked Accounts</h1>
    <p class="sub">Accounts locked after too many failed login attempts. Unlocking resets the attempt counter.</p>

    <?php if ($message): ?><div class="msg"><?= e($message) ?></div><?php endif; ?>

    <?php if ($locked): ?>
      <table>
        <tr>
          <th>Name</th>
          <th>Username</th>
          <th>Role</th>
          <th></th>
        </tr>
        <?php foreach ($locked as $u): ?>
        <tr>
          <td><?= e($u['name']) ?></td>
          <td><?= e($u['username']) ?></td>
          <td><span class="role-pill"><?= e($u['role']) ?></span></td>
          <td>
            <form method="post" style="margin:0">
              <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
              <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
              <button type="submit" class="unlock">Unlock</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </table>
    <?php else: ?>
      <div class="empty">No locked accounts. </div>
    <?php endif; ?>

    <a class="back" href="dashboard.php">&larr; Back to Dashboard</a>
  </div>
</body>
</html>