<?php

require_once __DIR__ . '/auth.php';

/*
|--------------------------------------------------------------------------
| Superadmin only
|--------------------------------------------------------------------------
*/

require_login();

if (user_role() !== 'superadmin') {
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
| Allowed roles
|--------------------------------------------------------------------------
*/

$allowedRoles = ['superadmin', 'hr', 'content_manager'];

/*
|--------------------------------------------------------------------------
| Load the user being edited
|--------------------------------------------------------------------------
*/

$userId = (int) ($_GET['id'] ?? $_POST['user_id'] ?? 0);

$stmt = db()->prepare(
    'SELECT id, name, username, role, is_locked
     FROM users
     WHERE id = ?
     LIMIT 1'
);

$stmt->bind_param('i', $userId);
$stmt->execute();

$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    header('Location: dashboard.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Handle update
|--------------------------------------------------------------------------
*/

$errors  = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token    = $_POST['csrf_token'] ?? '';
    $name     = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $newRole  = $_POST['role'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if (!hash_equals($csrf, $token)) {
        $errors[] = 'Invalid request. Please try again.';
    }

    if ($name === '') {
        $errors[] = 'Name is required.';
    }

    if (!preg_match('/^[a-zA-Z0-9._-]{3,50}$/', $username)) {
        $errors[] = 'Username must be 3–50 characters using only letters, numbers, dots, dashes, or underscores.';
    }

    if (!in_array($newRole, $allowedRoles, true)) {
        $errors[] = 'Please choose a valid role.';
    }

    // Optional password change: only validate if a new password was entered
    $changePassword = ($password !== '' || $confirm !== '');

    if ($changePassword) {
        if (strlen($password) < 8) {
            $errors[] = 'New password must be at least 8 characters.';
        }

        if ($password !== $confirm) {
            $errors[] = 'Passwords do not match.';
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Guard: don't demote the last superadmin
    |--------------------------------------------------------------------------
    */

    if (empty($errors) && $user['role'] === 'superadmin' && $newRole !== 'superadmin') {
        $superCount = (int) db()
            ->query("SELECT COUNT(*) c FROM users WHERE role = 'superadmin'")
            ->fetch_assoc()['c'];

        if ($superCount <= 1) {
            $errors[] = 'You cannot change the role of the last superadmin account.';
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Guard: duplicate username (excluding this user)
    |--------------------------------------------------------------------------
    */

    if (empty($errors)) {
        $stmt = db()->prepare(
            'SELECT id FROM users WHERE username = ? AND id != ? LIMIT 1'
        );

        $stmt->bind_param('si', $username, $userId);
        $stmt->execute();

        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = 'That username is already taken by another account.';
        }

        $stmt->close();
    }

    /*
    |--------------------------------------------------------------------------
    | Save changes
    |--------------------------------------------------------------------------
    */

    if (empty($errors)) {
        if ($changePassword) {
            $stored = make_password_hash($password);

            $stmt = db()->prepare(
                'UPDATE users
                 SET name = ?, username = ?, role = ?, password = ?
                 WHERE id = ?'
            );

            $stmt->bind_param('ssssi', $name, $username, $newRole, $stored, $userId);
        } else {
            $stmt = db()->prepare(
                'UPDATE users
                 SET name = ?, username = ?, role = ?
                 WHERE id = ?'
            );

            $stmt->bind_param('sssi', $name, $username, $newRole, $userId);
        }

        if ($stmt->execute()) {
            $success = 'User updated successfully.';

            // Refresh local copy so the form shows the saved values
            $user['name']     = $name;
            $user['username'] = $username;
            $user['role']     = $newRole;

            // If the superadmin edited their own account, keep the session in sync
            if ($userId === (int) $_SESSION['user_id']) {
                $_SESSION['name']     = $name;
                $_SESSION['username'] = $username;
                $_SESSION['role']     = $newRole;
            }
        } else {
            $errors[] = 'Something went wrong while saving. Please try again.';
        }

        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sunfreight Admin | Edit User</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@700;800&family=Open+Sans:wght@400;500;600&display=swap" rel="stylesheet" />
<style>
:root {
    --green: #07824E;
    --green-dark: #056a40;
    --gold: #CC9035;
}

* {
    box-sizing: border-box;
}

body {
    font-family: 'Open Sans', sans-serif;
    background: #f4f4f6;
    margin: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
    padding: 24px;
}

.card {
    background: #fff;
    padding: 40px;
    border-radius: 14px;
    box-shadow: 0 8px 28px rgba(0, 0, 0, 0.08);
    width: 100%;
    max-width: 460px;
}

h1 {
    font-family: 'Poppins', sans-serif;
    font-size: 1.3rem;
    color: var(--green);
    margin: 0 0 4px;
}

p.sub {
    margin: 0 0 24px;
    color: #777;
    font-size: 0.9rem;
}

.locked-note {
    background: #fdecec;
    color: #8b0000;
    padding: 12px 14px;
    border-radius: 8px;
    font-size: 0.85rem;
    margin-bottom: 12px;
}

.locked-note a {
    color: #8b0000;
    font-weight: 600;
}

label {
    display: block;
    font-size: 0.83rem;
    font-weight: 600;
    margin: 14px 0 4px;
}

input, select {
    width: 100%;
    padding: 11px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 0.93rem;
    font-family: inherit;
    background: #fff;
}

input:focus, select:focus {
    outline: 2px solid var(--green);
    border-color: transparent;
}

.hint {
    font-size: 0.78rem;
    color: #999;
    margin-top: 4px;
}

.divider {
    border-top: 1px solid #eee;
    margin: 26px 0 8px;
    padding-top: 14px;
    font-family: 'Poppins', sans-serif;
    font-size: 0.85rem;
    color: #555;
}

button {
    width: 100%;
    background: var(--green);
    color: #fff;
    border: 0;
    padding: 13px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    margin-top: 22px;
}

button:hover {
    background: var(--green-dark);
}

.err {
    background: #fdecec;
    color: #8b0000;
    padding: 12px 14px;
    border-radius: 8px;
    font-size: 0.85rem;
    margin-bottom: 12px;
}

.err ul {
    margin: 0;
    padding-left: 18px;
}

.ok {
    background: #e8f6ef;
    color: #05603a;
    padding: 12px 14px;
    border-radius: 8px;
    font-size: 0.85rem;
    margin-bottom: 12px;
}

a.back {
    display: inline-block;
    margin-top: 18px;
    color: var(--green);
    font-weight: 600;
    font-size: 0.88rem;
    text-decoration: none;
}
</style>
</head>
<body>
  <div class="card">
    <h1>Edit User</h1>
    <p class="sub">Editing account: <strong><?= e($user['username']) ?></strong></p>

    <?php if ((int) $user['is_locked'] === 1): ?>
      <div class="locked-note">
        This account is currently locked.
        <a href="unlock_accounts.php">Unlock it here</a>.
      </div>
    <?php endif; ?>

    <?php if ($errors): ?>
      <div class="err"><ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <?php if ($success): ?><div class="ok"><?= e($success) ?></div><?php endif; ?>

    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
      <input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>">

      <label>Full Name</label>
      <input type="text" name="name" required value="<?= e($user['name']) ?>">

      <label>Username</label>
      <input type="text" name="username" required value="<?= e($user['username']) ?>">
      <div class="hint">3–50 characters. Letters, numbers, dots, dashes, underscores.</div>

      <label>Role</label>
      <select name="role" required>
        <option value="superadmin" <?= $user['role'] === 'superadmin' ? 'selected' : '' ?>>Superadmin</option>
        <option value="hr" <?= $user['role'] === 'hr' ? 'selected' : '' ?>>HR</option>
        <option value="content_manager" <?= $user['role'] === 'content_manager' ? 'selected' : '' ?>>Content Manager</option>
      </select>

      <div class="divider">Reset password (optional)</div>

      <label>New Password</label>
      <input type="password" name="password">
      <div class="hint">Leave blank to keep the current password. Minimum 8 characters if changing.</div>

      <label>Confirm New Password</label>
      <input type="password" name="confirm">

      <button type="submit">Save Changes</button>
    </form>

    <a class="back" href="dashboard.php">&larr; Back to Dashboard</a>
  </div>
</body>
</html>