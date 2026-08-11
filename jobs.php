<?php

require_once __DIR__ . '/auth.php';

require_role('hr');

/*
|--------------------------------------------------------------------------
| CSRF token
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

function check_csrf(): void {
    if (($_POST['csrf'] ?? '') !== $_SESSION['csrf']) {
        die('Invalid request token. Go back and try again.');
    }
}

/*
|--------------------------------------------------------------------------
| Handle actions
|--------------------------------------------------------------------------
*/

$types = ['Full-time', 'Part-time', 'Internship'];
$flash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();

    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $title      = trim($_POST['title'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $type       = in_array($_POST['employment_type'] ?? '', $types) ? $_POST['employment_type'] : 'Full-time';
        $desc       = trim($_POST['description'] ?? '');
        $active     = isset($_POST['is_active']) ? 1 : 0;

        if ($title === '' || $desc === '') {
            $flash = 'Title and description are required.';
        } elseif ($action === 'create') {
            $stmt = db()->prepare(
                'INSERT INTO job_listings (
                    title,
                    department,
                    employment_type,
                    description,
                    is_active
                ) VALUES (?, ?, ?, ?, ?)'
            );

            $stmt->bind_param('ssssi', $title, $department, $type, $desc, $active);
            $stmt->execute();

            $flash = 'Job listing added.';
        } else {
            $id = (int) ($_POST['id'] ?? 0);

            $stmt = db()->prepare(
                'UPDATE job_listings
                 SET title = ?,
                     department = ?,
                     employment_type = ?,
                     description = ?,
                     is_active = ?
                 WHERE id = ?'
            );

            $stmt->bind_param('ssssii', $title, $department, $type, $desc, $active, $id);
            $stmt->execute();

            $flash = 'Job listing updated.';
        }
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);

        $stmt = db()->prepare('DELETE FROM job_listings WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();

        $flash = 'Job listing deleted.';
    } elseif ($action === 'toggle') {
        $id = (int) ($_POST['id'] ?? 0);

        $stmt = db()->prepare('UPDATE job_listings SET is_active = 1 - is_active WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();

        $flash = 'Visibility updated.';
    }
}

/*
|--------------------------------------------------------------------------
| Editing?
|--------------------------------------------------------------------------
*/

$edit = null;

if (isset($_GET['edit'])) {
    $id = (int) $_GET['edit'];

    $stmt = db()->prepare('SELECT * FROM job_listings WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();

    $edit = $stmt->get_result()->fetch_assoc();
}

$jobs = db()->query('SELECT * FROM job_listings ORDER BY created_at DESC')->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>WETLI | Job Listings</title>
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

header h1 a {
    color: #fff;
    text-decoration: none;
}

.logout {
    background: var(--yellow);
    color: var(--purple);
    text-decoration: none;
    padding: 8px 16px;
    border-radius: 6px;
}

.wrap {
    max-width: 1000px;
    margin: 32px auto;
    padding: 0 20px;
}

.flash {
    background: #e8f6ec;
    border: 1px solid #bfe5c9;
    color: #1c6b32;
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.panel {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
    padding: 28px;
    margin-bottom: 28px;
}

.panel h2 {
    margin: 0 0 18px;
    font-size: 1.05rem;
}

label {
    display: block;
    font-size: 0.82rem;
    font-weight: 600;
    margin: 12px 0 4px;
}

input[type=text],
select,
textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-family: inherit;
    font-size: 0.92rem;
    background: #fff;
}

textarea {
    min-height: 110px;
    resize: vertical;
}

.row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.check {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 14px;
    font-size: 0.9rem;
}

.btn {
    background: var(--yellow);
    color: var(--purple);
    border: 0;
    padding: 11px 22px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    margin-top: 18px;
}

.btn-ghost {
    background: #eee;
    color: #333;
}

table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.9rem;
}

th,
td {
    text-align: left;
    padding: 12px 10px;
    border-bottom: 1px solid #eee;
    vertical-align: top;
}

th {
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #888;
}

.badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 600;
}

.on {
    background: #e8f6ec;
    color: #1c6b32;
}

.off {
    background: #f3f3f3;
    color: #888;
}

.actions form {
    display: inline;
}

.mini {
    border: 0;
    background: none;
    color: var(--purple);
    cursor: pointer;
    font-weight: 600;
    padding: 2px 6px;
}

.mini.del {
    color: #b00;
}

a.mini {
    text-decoration: none;
}

@media (max-width: 700px) {
    .row {
        grid-template-columns: 1fr;
    }
}
</style>
</head>
<body>
<header>
  <h1><a href="dashboard.php">← Dashboard</a> &nbsp;|&nbsp; Job Listings — <?= e($_SESSION['name']) ?> (<?= e(ROLE_LABELS[user_role()] ?? '') ?>)</h1>
  <a class="logout" href="logout.php">Log out</a>
</header>

<div class="wrap">
  <?php if ($flash): ?><div class="flash"><?= e($flash) ?></div><?php endif; ?>

  <!-- Add / Edit form -->
  <div class="panel">
    <h2><?= $edit ? 'Edit Job Listing' : 'Add New Job Listing' ?></h2>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
      <input type="hidden" name="action" value="<?= $edit ? 'update' : 'create' ?>">
      <?php if ($edit): ?><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>"><?php endif; ?>

      <label>Job Title *</label>
      <input type="text" name="title" required value="<?= e($edit['title'] ?? '') ?>">

      <div class="row">
        <div>
          <label>Department</label>
          <input type="text" name="department" value="<?= e($edit['department'] ?? '') ?>">
        </div>
        <div>
          <label>Employment Type</label>
          <select name="employment_type">
            <?php foreach ($types as $t): ?>
              <option value="<?= e($t) ?>" <?= (($edit['employment_type'] ?? 'Full-time') === $t) ? 'selected' : '' ?>><?= e($t) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <label>Description *</label>
      <textarea name="description" required><?= e($edit['description'] ?? '') ?></textarea>

      <label class="check">
        <input type="checkbox" name="is_active" <?= (!$edit || $edit['is_active']) ? 'checked' : '' ?>>
        Visible on careers page
      </label>

      <button class="btn" type="submit"><?= $edit ? 'Save Changes' : 'Add Listing' ?></button>
      <?php if ($edit): ?><a href="jobs.php" class="btn btn-ghost" style="text-decoration:none;display:inline-block">Cancel</a><?php endif; ?>
    </form>
  </div>

  <!-- Listings table -->
  <div class="panel">
    <h2>All Listings (<?= count($jobs) ?>)</h2>
    <?php if (!$jobs): ?>
      <p>No job listings yet. Add one above — it will appear on the careers page instantly.</p>
    <?php else: ?>
    <table>
      <tr><th>Title</th><th>Department</th><th>Type</th><th>Status</th><th>Actions</th></tr>
      <?php foreach ($jobs as $j): ?>
      <tr>
        <td><strong><?= e($j['title']) ?></strong></td>
        <td><?= e($j['department']) ?></td>
        <td><?= e($j['employment_type']) ?></td>
        <td><span class="badge <?= $j['is_active'] ? 'on' : 'off' ?>"><?= $j['is_active'] ? 'Visible' : 'Hidden' ?></span></td>
        <td class="actions">
          <a class="mini" href="jobs.php?edit=<?= (int)$j['id'] ?>">Edit</a>
          <form method="post">
            <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
            <input type="hidden" name="action" value="toggle">
            <input type="hidden" name="id" value="<?= (int)$j['id'] ?>">
            <button class="mini" type="submit"><?= $j['is_active'] ? 'Hide' : 'Show' ?></button>
          </form>
          <form method="post" onsubmit="return confirm('Delete this job listing permanently?');">
            <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= (int)$j['id'] ?>">
            <button class="mini del" type="submit">Delete</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
    <?php endif; ?>
  </div>

  <p style="color:#888;font-size:.85rem">Public page: <a href="careers.php">careers.php</a></p>
</div>
</body>
</html>