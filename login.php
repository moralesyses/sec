<?php

require_once __DIR__ . '/auth.php';

/*
|--------------------------------------------------------------------------
| Redirect logged-in users
|--------------------------------------------------------------------------
*/

redirect_if_logged_in('dashboard.php');

/*
|--------------------------------------------------------------------------
| Login attempt limiting config
|--------------------------------------------------------------------------
| After MAX_ATTEMPTS consecutive wrong passwords, the account is locked
| (users.is_locked = 1) and can only be unlocked by a superadmin.
*/

const MAX_ATTEMPTS = 5;

/**
 * Increment the failed attempt counter; lock the account when it hits the cap.
 * Returns the new attempt count.
 */
function register_failed_attempt(int $userId): int
{
    $stmt = db()->prepare(
        'UPDATE users
         SET failed_attempts = failed_attempts + 1,
             is_locked = IF(failed_attempts + 1 >= ?, 1, is_locked)
         WHERE id = ?'
    );

    $max = MAX_ATTEMPTS;
    $stmt->bind_param('ii', $max, $userId);
    $stmt->execute();
    $stmt->close();

    $stmt = db()->prepare('SELECT failed_attempts FROM users WHERE id = ?');
    $stmt->bind_param('i', $userId);
    $stmt->execute();

    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return (int) ($row['failed_attempts'] ?? 0);
}

/**
 * Reset the counter after a successful login.
 */
function reset_failed_attempts(int $userId): void
{
    $stmt = db()->prepare(
        'UPDATE users SET failed_attempts = 0 WHERE id = ?'
    );

    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->close();
}

/*
|--------------------------------------------------------------------------
| Handle login form
|--------------------------------------------------------------------------
*/

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = db()->prepare(
        'SELECT id, name, username, role, password, is_locked, failed_attempts
         FROM users
         WHERE username = ?
         LIMIT 1'
    );

    $stmt->bind_param('s', $username);
    $stmt->execute();

    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($user && (int) $user['is_locked'] === 1) {
        $error = 'This account has been locked due to too many failed login attempts. Please contact your administrator to unlock it.';
    } elseif ($user && verify_password($password, (string) $user['password'])) {
        reset_failed_attempts((int) $user['id']);

        log_user_in(
            (int) $user['id'],
            $user['name'],
            $user['username'],
            $user['role']
        );

        header('Location: dashboard.php');
        exit;
    } else {
        if ($user) {
            $attempts  = register_failed_attempt((int) $user['id']);
            $remaining = MAX_ATTEMPTS - $attempts;

            if ($remaining <= 0) {
                $error = 'This account has been locked due to too many failed login attempts. Please contact your administrator to unlock it.';
            } else {
                $error = 'Invalid username or password. '
                       . $remaining . ' attempt' . ($remaining === 1 ? '' : 's')
                       . ' remaining before the account is locked.';
            }
        } else {
            // Unknown username: generic message, no counter to increment.
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sunfreight | Log In</title>
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
    max-width: 400px;
}

h1 {
    font-family: 'Poppins', sans-serif;
    font-size: 1.4rem;
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

input {
    width: 100%;
    padding: 11px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 0.93rem;
}

input:focus {
    outline: 2px solid var(--purple);
    border-color: transparent;
}

button {
    width: 100%;
    background: var(--yellow);
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
    background: var(--red);
}

.err {
    background: #fdecec;
    color: #8b0000;
    padding: 12px 14px;
    border-radius: 8px;
    font-size: 0.85rem;
    margin-bottom: 12px;
}

.public{
    text-decoration: none;
    color: var(--purple);
    font-size: 0.9rem;
}

.foot {
    text-align: center;
    font-size: 0.87rem;
    margin-top: 18px;
    color: #666;
}

.foot a {
    color: var(--green);
    font-weight: 600;
    text-decoration: none;
}
</style>
</head>
<body>
  <div class="card">
    <h1>Welcome back</h1>
    <p class="sub">Log in to your <a class="public" href="index.html">Sunfreight</a> account</p>

    <?php if ($error): ?><div class="err"><?= e($error) ?></div><?php endif; ?>

    <form method="post">
      <label>Username</label>
      <input type="text" name="username" required autofocus>

      <label>Password</label>
      <input type="password" name="password" required>

      <button type="submit">Log In</button>
    </form>
  </div>
</body>
</html>