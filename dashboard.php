<?php

require_once __DIR__ . '/auth.php';

require_login();

$role      = user_role();
$roleLabel = ROLE_LABELS[$role] ?? $role;

$jobCount = null;

if (can('jobs')) {
    $jobCount = db()->query("SELECT COUNT(*) c FROM job_listings WHERE is_active = 1")->fetch_assoc()['c'];
}

$eventCount = null;

if (can('gallery')) {
    $eventCount = db()->query("SELECT COUNT(*) c FROM gallery_events WHERE is_active = 1")->fetch_assoc()['c'];
}

/*
|--------------------------------------------------------------------------
| Locked accounts count (superadmin only)
|--------------------------------------------------------------------------
*/

$lockedCount = null;
$userCount   = null;
$allUsers    = [];
$userMsg     = '';
$userErr     = '';

if ($role === 'superadmin') {
    /*
    |--------------------------------------------------------------------------
    | CSRF token (for delete actions)
    |--------------------------------------------------------------------------
    */

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    $csrf = $_SESSION['csrf_token'];

    /*
    |--------------------------------------------------------------------------
    | Handle delete user
    |--------------------------------------------------------------------------
    | Protections:
    | - superadmin only (outer check)
    | - CSRF token required
    | - cannot delete your own account
    | - cannot delete the last remaining superadmin
    */

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_user') {
        $token    = $_POST['csrf_token'] ?? '';
        $targetId = (int) ($_POST['user_id'] ?? 0);

        if (!hash_equals($csrf, $token)) {
            $userErr = 'Invalid request. Please try again.';
        } elseif ($targetId === (int) $_SESSION['user_id']) {
            $userErr = 'You cannot delete your own account.';
        } elseif ($targetId > 0) {
            $stmt = db()->prepare('SELECT role FROM users WHERE id = ? LIMIT 1');
            $stmt->bind_param('i', $targetId);
            $stmt->execute();
            $target = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$target) {
                $userErr = 'User not found.';
            } else {
                $superCount = (int) db()
                    ->query("SELECT COUNT(*) c FROM users WHERE role = 'superadmin'")
                    ->fetch_assoc()['c'];

                if ($target['role'] === 'superadmin' && $superCount <= 1) {
                    $userErr = 'You cannot delete the last superadmin account.';
                } else {
                    $stmt = db()->prepare('DELETE FROM users WHERE id = ?');
                    $stmt->bind_param('i', $targetId);
                    $stmt->execute();
                    $stmt->close();

                    $userMsg = 'User deleted successfully.';
                }
            }
        }
    }

    $lockedCount = db()->query("SELECT COUNT(*) c FROM users WHERE is_locked = 1")->fetch_assoc()['c'];
    $userCount   = db()->query("SELECT COUNT(*) c FROM users")->fetch_assoc()['c'];

    $allUsers = db()
        ->query(
            'SELECT id, name, username, role, is_locked
             FROM users
             ORDER BY role, username'
        )
        ->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>WETLI | Dashboard</title>
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
    color: #222;
}

header {
    background: var(--purple);
    color: #fff;
    padding: 16px 28px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

header h1 {
    font-family: 'Poppins', sans-serif;
    font-size: 1.05rem;
    margin: 0;
}

header .who {
    font-size: 0.82rem;
    opacity: 0.85;
}

.logout {
    background: var(--yellow);
    color: var(--purple);
    text-decoration: none;
    padding: 8px 16px;
    border-radius: 6px;
}

.wrap {
    max-width: 900px;
    margin: 48px auto;
    padding: 0 20px;
}

h2 {
    font-size: 1.2rem;
    margin: 0 0 6px;
}

p.sub {
    color: #777;
    margin: 0 0 32px;
    font-size: 0.92rem;
}

.tiles {
    display: grid;
    grid-template-columns: repeat(
        auto-fit,
        minmax(260px, 1fr)
    );
    gap: 24px;
}

a.tile {
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
    padding: 32px;
    text-decoration: none;
    color: #222;
    transition:
        transform 0.15s,
        box-shadow 0.15s;
    display: block;
}

a.tile:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 28px rgba(0, 0, 0, 0.1);
}

.tile-icon {
    font-size: 2rem;
    margin-bottom: 14px;
}

.tile-title {
    font-family: 'Poppins', sans-serif;
    font-weight: 700;
    font-size: 1.05rem;
    color: var(--dark);
    margin-bottom: 6px;
}

.tile-desc {
    font-size: 0.88rem;
    color: #666;
    line-height: 1.5;
}

.tile-count {
    display: inline-block;
    margin-top: 14px;
    background: var(--yellow);
    color: var(--purple);
    font-weight: 600;
    font-size: 0.8rem;
    padding: 4px 12px;
    border-radius: 999px;
}

/* Alert variant for the Locked Accounts tile when there are locked users */
.tile-count.alert {
    background: #fdecec;
    color: #8b0000;
}

/* ---------- User list (superadmin) ---------- */

.users-section {
    margin-top: 44px;
}

.users-section h2 {
    margin-bottom: 14px;
}

table.users {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
}

table.users th,
table.users td {
    text-align: left;
    padding: 12px 16px;
    font-size: 0.88rem;
}

table.users th {
    background: var(--purple);
    color: #fff;
    font-family: 'Poppins', sans-serif;
    font-size: 0.8rem;
    letter-spacing: 0.03em;
}

table.users tr + tr td {
    border-top: 1px solid #eee;
}

.role-pill {
    display: inline-block;
    background: #f3ead9;
    color: var(--gold);
    font-weight: 600;
    font-size: 0.74rem;
    padding: 3px 10px;
    border-radius: 999px;
}

.status-pill {
    display: inline-block;
    font-weight: 600;
    font-size: 0.74rem;
    padding: 3px 10px;
    border-radius: 999px;
}

.status-pill.active {
    background: var(--yellow);
    color: var(--purple);
}

.status-pill.locked {
    background: #fdecec;
    color: #8b0000;
}

.unlock-link {
    color: var(--red);
    font-weight: 600;
    font-size: 0.82rem;
    text-decoration: none;
}

.actions {
    display: flex;
    gap: 10px;
    align-items: center;
}

a.edit-link {
    color: var(--red);
    font-weight: 600;
    font-size: 0.82rem;
    text-decoration: none;
}

button.delete-btn {
    background: none;
    border: 0;
    color: #8b0000;
    font-family: inherit;
    font-weight: 600;
    font-size: 0.82rem;
    cursor: pointer;
    padding: 0;
}

button.delete-btn:hover,
a.edit-link:hover,
.unlock-link:hover {
    text-decoration: underline;
}

.banner {
    padding: 12px 14px;
    border-radius: 8px;
    font-size: 0.88rem;
    margin-bottom: 14px;
}

.banner.ok {
    background: var(--yellow);
    color: var(--purple);
}

.banner.err {
    background: #fdecec;
    color: #8b0000;
}
</style>
</head>
<body>
<header>
  <div>
    <h1>WETLI Dashboard</h1>
    <div class="who">Logged in as <?= e($_SESSION['name']) ?> · <?= e($roleLabel) ?></div>
  </div>
  <a class="logout" href="logout.php">Log out</a>
</header>

<div class="wrap">
  <h2>What would you like to manage?</h2>
  <p class="sub">You only see the sections your role has access to.</p>

  <div class="tiles">
    <?php if (can('jobs')): ?>
    <a class="tile" href="jobs.php">
      <div class="tile-title">Job Listings</div>
      <div class="tile-desc">Add, edit, hide, or delete openings shown on the public careers page.</div>
      <span class="tile-count"><?= (int)$jobCount ?> active listing<?= $jobCount == 1 ? '' : 's' ?></span>
    </a>
    <?php endif; ?>

    <?php if (can('gallery')): ?>
    <a class="tile" href="gallery_admin.php">
      <div class="tile-title">Gallery</div>
      <div class="tile-desc">Manage the photos shown on the public gallery page.</div>
      <span class="tile-count"><?= (int)$eventCount ?> active event<?= $eventCount == 1 ? '' : 's' ?></span>
    </a>
    <?php endif; ?>

    <?php if ($role === 'superadmin'): ?>
    <a class="tile" href="add_user.php">
      <div class="tile-title">Add New User</div>
      <div class="tile-desc">Create accounts for HR, content managers, or other superadmins.</div>
      <span class="tile-count"><?= (int)$userCount ?> total user<?= $userCount == 1 ? '' : 's' ?></span>
    </a>

    <a class="tile" href="unlock_accounts.php">
      <div class="tile-title">Locked Accounts</div>
      <div class="tile-desc">Review and unlock user accounts that were locked after too many failed login attempts.</div>
      <span class="tile-count<?= $lockedCount > 0 ? ' alert' : '' ?>">
        <?= (int)$lockedCount ?> locked account<?= $lockedCount == 1 ? '' : 's' ?>
      </span>
    </a>
    <?php endif; ?>
  </div>

  <?php if ($role === 'superadmin'): ?>
  <div class="users-section">
    <h2>All Users</h2>

    <?php if ($userMsg): ?><div class="banner ok"><?= e($userMsg) ?></div><?php endif; ?>
    <?php if ($userErr): ?><div class="banner err"><?= e($userErr) ?></div><?php endif; ?>

    <table class="users">
      <tr>
        <th>Name</th>
        <th>Username</th>
        <th>Role</th>
        <th>Status</th>
        <th>Actions</th>
      </tr>
      <?php foreach ($allUsers as $u): ?>
      <tr>
        <td><?= e($u['name']) ?></td>
        <td><?= e($u['username']) ?></td>
        <td><span class="role-pill"><?= e(ROLE_LABELS[$u['role']] ?? $u['role']) ?></span></td>
        <td>
          <?php if ((int) $u['is_locked'] === 1): ?>
            <span class="status-pill locked">Locked</span>
          <?php else: ?>
            <span class="status-pill active">Active</span>
          <?php endif; ?>
        </td>
        <td>
          <div class="actions">
            <a class="edit-link" href="edit_user.php?id=<?= (int) $u['id'] ?>">Edit</a>

            <?php if ((int) $u['id'] !== (int) $_SESSION['user_id']): ?>
            <form method="post" style="margin:0"
                  onsubmit="return confirm('Delete user <?= e($u['username']) ?>? This cannot be undone.');">
              <input type="hidden" name="action" value="delete_user">
              <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
              <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
              <button type="submit" class="delete-btn">Delete</button>
            </form>
            <?php endif; ?>

            <?php if ((int) $u['is_locked'] === 1): ?>
              <a class="unlock-link" href="unlock_accounts.php">Unlock &rarr;</a>
            <?php endif; ?>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
  </div>
  <?php endif; ?>
</div>
</body>
</html>