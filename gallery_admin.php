<?php

require_once __DIR__ . '/auth.php';

require_role('content_manager');

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
| Upload settings
|--------------------------------------------------------------------------
*/

$uploadDir  = __DIR__ . '/uploads/gallery/';
$allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

$categories = [
    'Proof of Shipments',
    'Expanding Our Network',
    'Company Culture',
    'Corporate Social Responsibility',
];

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

/*
|--------------------------------------------------------------------------
| Handle actions
|--------------------------------------------------------------------------
*/

$flash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();

    $action = $_POST['action'] ?? '';

    if ($action === 'create_event') {
        $title = trim($_POST['title'] ?? '');
        $desc  = trim($_POST['description'] ?? '');
        $date  = $_POST['event_date'] ?: null;
        $cat   = in_array($_POST['category'] ?? '', $categories) ? $_POST['category'] : 'Company Culture';

        if ($title === '') {
            $flash = 'Event title is required.';
        } else {
            $stmt = db()->prepare(
                'INSERT INTO gallery_events (title, description, category, event_date) VALUES (?, ?, ?, ?)'
            );

            $stmt->bind_param('ssss', $title, $desc, $cat, $date);
            $stmt->execute();

            $eventId = $stmt->insert_id;
            $stmt->close();

            $uploaded = upload_photos($eventId, $uploadDir, $allowedExt);
            $flash = "Event created with {$uploaded} photo(s).";
        }
    } elseif ($action === 'update_event') {
        $id    = (int) ($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $desc  = trim($_POST['description'] ?? '');
        $date  = $_POST['event_date'] ?: null;
        $cat   = in_array($_POST['category'] ?? '', $categories) ? $_POST['category'] : 'Company Culture';

        $stmt = db()->prepare(
            'UPDATE gallery_events SET title = ?, description = ?, category = ?, event_date = ? WHERE id = ?'
        );

        $stmt->bind_param('ssssi', $title, $desc, $cat, $date, $id);
        $stmt->execute();

        $uploaded = upload_photos($id, $uploadDir, $allowedExt);
        $flash = 'Event updated' . ($uploaded ? " with {$uploaded} new photo(s)." : '.');
    } elseif ($action === 'toggle_event') {
        $id = (int) ($_POST['id'] ?? 0);

        $stmt = db()->prepare('UPDATE gallery_events SET is_active = 1 - is_active WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();

        $flash = 'Event visibility updated.';
    } elseif ($action === 'delete_event') {
        $id = (int) ($_POST['id'] ?? 0);

        $stmt = db()->prepare('SELECT filename FROM gallery_photos WHERE event_id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $path = $uploadDir . basename($row['filename']);
            if (is_file($path)) unlink($path);
        }

        $stmt = db()->prepare('DELETE FROM gallery_events WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();

        $flash = 'Event and its photos deleted.';
    } elseif ($action === 'delete_photo') {
        $id = (int) ($_POST['id'] ?? 0);

        $stmt = db()->prepare('SELECT filename FROM gallery_photos WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        if ($row) {
            $path = $uploadDir . basename($row['filename']);
            if (is_file($path)) unlink($path);

            $stmt = db()->prepare('DELETE FROM gallery_photos WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
        }

        $flash = 'Photo deleted.';
    } elseif ($action === 'set_cover') {
        $id      = (int) ($_POST['id'] ?? 0);
        $eventId = (int) ($_POST['event_id'] ?? 0);

        db()->query('UPDATE gallery_photos SET is_cover = 0 WHERE event_id = ' . $eventId);

        $stmt = db()->prepare('UPDATE gallery_photos SET is_cover = 1 WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();

        $flash = 'Cover photo updated.';
    }
}

/**
 * Save all files from the photos[] input for an event.
 * Returns the number of photos stored.
 */
function upload_photos(int $eventId, string $uploadDir, array $allowedExt): int {
    if (empty($_FILES['photos']) || !is_array($_FILES['photos']['name'])) {
        return 0;
    }

    $count = 0;

    foreach ($_FILES['photos']['name'] as $i => $name) {
        if ($_FILES['photos']['error'][$i] !== UPLOAD_ERR_OK) {
            continue;
        }

        $tmp  = $_FILES['photos']['tmp_name'][$i];
        $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $mime = mime_content_type($tmp);

        if (!in_array($ext, $allowedExt) || strpos($mime, 'image/') !== 0) {
            continue;
        }

        if ($_FILES['photos']['size'][$i] > 20 * 1024 * 1024) {
            continue;
        }

        $filename = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;

        if (move_uploaded_file($tmp, $uploadDir . $filename)) {
            $stmt = db()->prepare(
                'INSERT INTO gallery_photos (event_id, filename, sort_order) VALUES (?, ?, ?)'
            );

            $stmt->bind_param('isi', $eventId, $filename, $count);
            $stmt->execute();
            $stmt->close();

            $count++;
        }
    }

    return $count;
}

/*
|--------------------------------------------------------------------------
| Editing?
|--------------------------------------------------------------------------
*/

$edit = null;

if (isset($_GET['edit'])) {
    $id = (int) $_GET['edit'];

    $stmt = db()->prepare('SELECT * FROM gallery_events WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();

    $edit = $stmt->get_result()->fetch_assoc();
}

/*
|--------------------------------------------------------------------------
| Load events + photos
|--------------------------------------------------------------------------
*/

$events = db()->query(
    'SELECT * FROM gallery_events ORDER BY event_date DESC, created_at DESC'
)->fetch_all(MYSQLI_ASSOC);

$photosByEvent = [];

$result = db()->query('SELECT * FROM gallery_photos ORDER BY is_cover DESC, sort_order ASC, id ASC');

while ($row = $result->fetch_assoc()) {
    $photosByEvent[$row['event_id']][] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sunfreight | Gallery Manager</title>
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
    color: var(--purple);
}

label {
    display: block;
    font-size: 0.82rem;
    font-weight: 600;
    margin: 12px 0 4px;
}

input[type=text],
input[type=date],
input[type=file],
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
    min-height: 100px;
    resize: vertical;
}

.row {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr;
    gap: 16px;
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

.event-block {
    border: 1px solid #eee;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
}

.event-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
    flex-wrap: wrap;
}

.event-title {
    font-weight: 700;
    font-size: 1rem;
}

.event-meta {
    font-size: 0.82rem;
    color: #888;
    margin-top: 3px;
}

.badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 999px;
    font-size: 0.72rem;
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

.cat-badge {
    background: #eef4ff;
    color: #2b5cad;
}

.event-actions {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}

.event-actions form {
    display: inline;
}

.mini {
    border: 0;
    background: #f3f3f3;
    color: #333;
    cursor: pointer;
    font-weight: 600;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 0.78rem;
    text-decoration: none;
    display: inline-block;
}

.mini.del {
    background: #fdecec;
    color: #b00;
}

.thumbs {
    display: grid;
    grid-template-columns: repeat(
        auto-fill,
        minmax(110px, 1fr)
    );
    gap: 12px;
    margin-top: 16px;
}

.thumb {
    position: relative;
    border-radius: 8px;
    overflow: hidden;
    border: 2px solid transparent;
}

.thumb.cover {
    border-color: var(--purple);
}

.thumb img {
    width: 100%;
    height: 90px;
    object-fit: cover;
    display: block;
    background: #ddd;
}

.thumb-actions {
    display: flex;
    gap: 4px;
    padding: 6px;
    background: #fafafa;
}

.thumb-actions button {
    flex: 1;
    border: 0;
    background: #eee;
    font-size: 0.68rem;
    font-weight: 600;
    padding: 4px 2px;
    border-radius: 4px;
    cursor: pointer;
}

.thumb-actions .del {
    background: #fdecec;
    color: #b00;
}

.cover-tag {
    position: absolute;
    top: 4px;
    left: 4px;
    background: var(--purple);
    color: #fff;
    font-size: 0.62rem;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 999px;
}
</style>
</head>
<body>
<header>
  <h1><a href="dashboard.php">← Dashboard</a> &nbsp;|&nbsp; Gallery Manager — <?= e($_SESSION['name']) ?> (<?= e(ROLE_LABELS[user_role()] ?? '') ?>)</h1>
  <a class="logout" href="logout.php">Log out</a>
</header>

<div class="wrap">
  <?php if ($flash): ?><div class="flash"><?= e($flash) ?></div><?php endif; ?>

  <!-- Create / Edit event -->
  <div class="panel">
    <h2><?= $edit ? 'Edit Event' : 'Create New Event' ?></h2>
    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
      <input type="hidden" name="action" value="<?= $edit ? 'update_event' : 'create_event' ?>">
      <?php if ($edit): ?><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>"><?php endif; ?>

      <div class="row">
        <div>
          <label>Event Title *</label>
          <input type="text" name="title" required value="<?= e($edit['title'] ?? '') ?>" placeholder="e.g. Sunfreight Christmas Party 2025">
        </div>
        <div>
          <label>Category *</label>
          <select name="category" required>
            <?php $sel = $edit['category'] ?? 'Company Culture'; ?>
            <?php foreach ($categories as $c): ?>
            <option value="<?= e($c) ?>" <?= $c === $sel ? 'selected' : '' ?>><?= e($c) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label>Event Date</label>
          <input type="date" name="event_date" value="<?= e($edit['event_date'] ?? '') ?>">
        </div>
      </div>

      <label>Description of the Event</label>
      <textarea name="description" placeholder="What happened at this event? Shown in the popup."><?= e($edit['description'] ?? '') ?></textarea>

      <label><?= $edit ? 'Add More Photos' : 'Photos' ?> (jpg, png, gif, webp — max 20 MB each, select multiple)</label>
      <input type="file" name="photos[]" accept="image/*" multiple <?= $edit ? '' : 'required' ?>>

      <button class="btn" type="submit"><?= $edit ? 'Save Changes' : 'Create Event' ?></button>
      <?php if ($edit): ?><a href="gallery_admin.php" class="btn btn-ghost" style="text-decoration:none;display:inline-block">Cancel</a><?php endif; ?>
    </form>
  </div>

  <!-- Events list -->
  <div class="panel">
    <h2>All Events (<?= count($events) ?>)</h2>

    <?php if (!$events): ?>
      <p>No events yet. Create one above — the first photo (or your chosen cover) shows on the public gallery grid.</p>
    <?php endif; ?>

    <?php foreach ($events as $ev):
        $photos = $photosByEvent[$ev['id']] ?? [];
    ?>
    <div class="event-block">
      <div class="event-head">
        <div>
          <div class="event-title"><?= e($ev['title']) ?></div>
          <div class="event-meta">
            <?= $ev['event_date'] ? e(date('F j, Y', strtotime($ev['event_date']))) . ' · ' : '' ?>
            <span class="badge cat-badge"><?= e($ev['category'] ?? '') ?></span> ·
            <?= count($photos) ?> photo<?= count($photos) == 1 ? '' : 's' ?> ·
            <span class="badge <?= $ev['is_active'] ? 'on' : 'off' ?>"><?= $ev['is_active'] ? 'Visible' : 'Hidden' ?></span>
          </div>
        </div>
        <div class="event-actions">
          <a class="mini" href="gallery_admin.php?edit=<?= (int)$ev['id'] ?>">Edit / Add Photos</a>
          <form method="post">
            <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
            <input type="hidden" name="action" value="toggle_event">
            <input type="hidden" name="id" value="<?= (int)$ev['id'] ?>">
            <button class="mini" type="submit"><?= $ev['is_active'] ? 'Hide' : 'Show' ?></button>
          </form>
          <form method="post" onsubmit="return confirm('Delete this event and ALL its photos permanently?');">
            <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
            <input type="hidden" name="action" value="delete_event">
            <input type="hidden" name="id" value="<?= (int)$ev['id'] ?>">
            <button class="mini del" type="submit">Delete</button>
          </form>
        </div>
      </div>

      <?php if ($photos): ?>
      <div class="thumbs">
        <?php foreach ($photos as $p): ?>
        <div class="thumb <?= $p['is_cover'] ? 'cover' : '' ?>">
          <?php if ($p['is_cover']): ?><span class="cover-tag">COVER</span><?php endif; ?>
          <img src="uploads/gallery/<?= e($p['filename']) ?>" alt="">
          <div class="thumb-actions">
            <?php if (!$p['is_cover']): ?>
            <form method="post" style="flex:1;display:flex">
              <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
              <input type="hidden" name="action" value="set_cover">
              <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
              <input type="hidden" name="event_id" value="<?= (int)$ev['id'] ?>">
              <button type="submit" style="width:100%">Cover</button>
            </form>
            <?php endif; ?>
            <form method="post" style="flex:1;display:flex" onsubmit="return confirm('Delete this photo?');">
              <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
              <input type="hidden" name="action" value="delete_photo">
              <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
              <button class="del" type="submit" style="width:100%">✕</button>
            </form>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>

  <p style="color:#888;font-size:.85rem">Public page: <a href="gallery.php">gallery.php</a></p>
</div>
</body>
</html>
