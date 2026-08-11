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
| Handle form
|--------------------------------------------------------------------------
*/

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token    = $_POST['csrf_token'] ?? '';
    $name     = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';
    $newRole  = $_POST['role'] ?? '';

    if (!hash_equals($csrf, $token)) {
        $error = 'Invalid request. Please try again.';
    } elseif ($name === '' || $username === '' || $password === '') {
        $error = 'All fields are required.';
    } elseif (!preg_match('/^[a-zA-Z0-9._-]{3,50}$/', $username)) {
        $error = 'Username must be 3–50 characters using only letters, numbers, dots, dashes, or underscores.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (!in_array($newRole, $allowedRoles, true)) {
        $error = 'Please select a valid role.';
    } else {
        // Check for duplicate username
        $stmt = db()->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $exists = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($exists) {
            $error = 'That username is already taken.';
        } else {
            // make_password_hash() (auth.php) stores plain text while
            // PLAIN_PASSWORDS = true (testing only) and bcrypt when false.
            $stored = make_password_hash($password);

            $stmt = db()->prepare(
                'INSERT INTO users (name, username, password, role)
                 VALUES (?, ?, ?, ?)'
            );

            $stmt->bind_param('ssss', $name, $username, $stored, $newRole);

            if ($stmt->execute()) {
                $success = 'User "' . $username . '" created successfully.';
            } else {
                $error = 'Something went wrong while creating the user. Please try again.';
            }

            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>WETLI Admin | Add User</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@700;800&family=Open+Sans:wght@400;500;600&display=swap" rel="stylesheet" />
<style>
:root {
    --purple:   #572472;   /* primary */
    --yellow:   #F3CD00;   /* accent */
    --red:      #B31613;   /* secondary accent */
    --white:    #ffffff;
    --offwhite: #f5f2f7;
    --silver:   #cfc6d6;
    --dark:     #28232B;
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
    color: var(--purple);
    margin: 0 0 4px;
}

p.sub {
    margin: 0 0 24px;
    color: #777;
    font-size: 0.9rem;
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
    background: #fff;
}

input:focus, select:focus {
    outline: 2px solid var(--purple);
    border-color: transparent;
}

.hint {
    font-size: 0.78rem;
    color: #999;
    margin-top: 4px;
}

button {
    width: 100%;
    background: var(--yellow);
    color: var(--purple);
    border: 0;
    padding: 13px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    margin-top: 22px;
}


.err {
    background: #fdecec;
    color: #8b0000;
    padding: 12px 14px;
    border-radius: 8px;
    font-size: 0.85rem;
    margin-bottom: 12px;
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
    <h1>Add New User</h1>
    <p class="sub">Create an account for the WETLI admin system.</p>

    <?php if ($error): ?><div class="err"><?= e($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="ok"><?= e($success) ?></div><?php endif; ?>

    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">

      <label>Full Name</label>
      <input type="text" name="name" required>

      <label>Username</label>
      <input type="text" name="username" required>
      <div class="hint">3–50 characters. Letters, numbers, dots, dashes, underscores.</div>

      <label>Password</label>
      <input type="password" name="password" required>
      <div class="hint">Minimum 8 characters.</div>

      <label>Confirm Password</label>
      <input type="password" name="confirm" required>

      <label>Role</label>
      <select name="role" required>
        <option value="">— Select role —</option>
        <option value="superadmin">Superadmin</option>
        <option value="hr">HR</option>
        <option value="content_manager">Content Manager</option>
      </select>

      <button type="submit">Create User</button>
    </form>

    <a class="back" href="dashboard.php">&larr; Back to Dashboard</a>
  </div>
</body>
</html>