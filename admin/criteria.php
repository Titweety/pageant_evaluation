<?php
require_once __DIR__ . '/../db.php';
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }

$msg = '';
$msg_type = 'success';

// --- CATEGORY ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $cat_name = trim($_POST['category_name']);
    if ($cat_name !== '') {
        $stmt = $conn->prepare("INSERT INTO categories (category_name) VALUES (?)");
        $stmt->bind_param('s', $cat_name);
        $stmt->execute(); $stmt->close();
        $msg = 'Category added successfully.';
    }
}

if (isset($_GET['delete_category'])) {
    $id = (int)$_GET['delete_category'];
    $stmt = $conn->prepare("DELETE FROM categories WHERE category_id = ?");
    $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close();
    $msg = 'Category and its criteria deleted.';
}

// --- CRITERIA ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_criteria'])) {
    $cat_id     = (int)$_POST['category_id'];
    $crit_name  = trim($_POST['criteria_name']);
    $percentage = floatval($_POST['percentage']);
    if ($crit_name !== '' && $cat_id > 0) {
        $stmt = $conn->prepare("INSERT INTO criteria (criteria_name, percentage, category_id) VALUES (?, ?, ?)");
        $stmt->bind_param('sdi', $crit_name, $percentage, $cat_id);
        $stmt->execute(); $stmt->close();
        $msg = 'Criteria added successfully.';
    }
}

if (isset($_GET['delete_criteria'])) {
    $id = (int)$_GET['delete_criteria'];
    $stmt = $conn->prepare("DELETE FROM criteria WHERE criteria_id = ?");
    $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close();
    $msg = 'Criteria deleted.';
}

// Inline edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_criteria'])) {
    $id         = (int)$_POST['criteria_id'];
    $crit_name  = trim($_POST['criteria_name_edit']);
    $percentage = floatval($_POST['percentage_edit']);
    $stmt = $conn->prepare("UPDATE criteria SET criteria_name = ?, percentage = ? WHERE criteria_id = ?");
    $stmt->bind_param('sdi', $crit_name, $percentage, $id);
    $stmt->execute(); $stmt->close();
    $msg = 'Criteria updated.';
}

// --- FETCH ---
$active_cat = isset($_GET['cat']) ? (int)$_GET['cat'] : null;

$cats = $conn->query("SELECT * FROM categories ORDER BY created_at ASC");
$categories = [];
while ($c = $cats->fetch_assoc()) $categories[] = $c;

// Set default active category
if ($active_cat === null && count($categories) > 0) {
    $active_cat = $categories[0]['category_id'];
}

$criteria_list = [];
if ($active_cat) {
    $stmt = $conn->prepare("SELECT * FROM criteria WHERE category_id = ? ORDER BY created_at ASC");
    $stmt->bind_param('i', $active_cat);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) $criteria_list[] = $r;
    $stmt->close();
}

$active_cat_name = '';
foreach ($categories as $c) {
    if ($c['category_id'] == $active_cat) { $active_cat_name = $c['category_name']; break; }
}

$edit_id = isset($_GET['edit']) ? (int)$_GET['edit'] : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Manage Criteria</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Playfair+Display:wght@600&display=swap" rel="stylesheet"/>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  :root {
    --navy: #0f1c3f;
    --navy-mid: #1a2e5a;
    --navy-light: #243a70;
    --accent: #4f8ef7;
    --accent-soft: #e8f0fe;
    --green: #1a9e6a;
    --green-soft: #e3f5ec;
    --amber: #d97c10;
    --amber-soft: #fff3e0;
    --danger: #ef4444;
    --danger-soft: #fee2e2;
    --text-main: #0f1c3f;
    --text-muted: #6b7a99;
    --border: #e4e8f0;
    --bg: #f4f6fb;
    --card: #ffffff;
    --radius: 14px;
    --radius-sm: 8px;
  }

  body {
    font-family: 'DM Sans', sans-serif;
    background: var(--bg);
    color: var(--text-main);
    min-height: 100vh;
  }

  /* TOPBAR */
  header {
    background: var(--navy);
    padding: 0 2rem;
    height: 62px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: sticky;
    top: 0;
    z-index: 100;
  }
  .brand { display: flex; align-items: center; gap: 10px; }
  .brand-icon {
    width: 32px; height: 32px;
    background: var(--accent);
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
  }
  .brand-icon svg { width: 18px; height: 18px; fill: none; stroke: #fff; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
  .brand-name { font-size: 16px; font-weight: 600; color: #fff; }
  .header-right { display: flex; align-items: center; gap: 14px; }
  .avatar {
    width: 34px; height: 34px;
    background: var(--navy-light);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 12px; font-weight: 600; color: #c5d3f5;
    border: 1.5px solid rgba(255,255,255,0.15);
    text-transform: uppercase;
  }
  .btn-logout {
    background: rgba(255,255,255,0.08);
    border: 1px solid rgba(255,255,255,0.18);
    color: #c5d3f5; font-family: 'DM Sans', sans-serif;
    font-size: 13px; font-weight: 500;
    padding: 6px 14px; border-radius: var(--radius-sm);
    cursor: pointer; text-decoration: none; transition: background 0.2s;
  }
  .btn-logout:hover { background: rgba(255,255,255,0.14); color: #fff; }

  /* LAYOUT */
  .layout {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2.5rem 2rem;
    display: grid;
    grid-template-columns: 260px 1fr;
    gap: 2rem;
    align-items: start;
  }

  /* PAGE HEADER (spans full width above layout) */
  .page-top {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem 2rem 0;
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    margin-bottom: 0;
  }
  .eyebrow { font-size: 12px; font-weight: 500; letter-spacing: 0.1em; text-transform: uppercase; color: var(--accent); margin-bottom: 4px; }
  .page-top h1 { font-family: 'Playfair Display', serif; font-size: 28px; font-weight: 600; color: var(--text-main); }

  /* ALERT */
  .alert-wrap { max-width: 1200px; margin: 1rem auto 0; padding: 0 2rem; }
  .alert {
    padding: 0.9rem 1.2rem;
    border-radius: var(--radius-sm);
    font-size: 14px; font-weight: 500;
    animation: fadeUp 0.3s ease both;
  }
  .alert-success { background: var(--green-soft); color: var(--green); border: 1px solid #c6ebd7; }

  /* SIDEBAR */
  .sidebar {}
  .sidebar-card {
    background: var(--card);
    border-radius: var(--radius);
    border: 1px solid var(--border);
    box-shadow: 0 2px 10px rgba(15,28,63,0.03);
    overflow: hidden;
    animation: fadeUp 0.35s ease both;
  }
  .sidebar-header {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
  }
  .sidebar-header span { font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-muted); }

  .cat-list { list-style: none; }
  .cat-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem 1.25rem;
    border-bottom: 1px solid var(--border);
    cursor: pointer;
    transition: background 0.15s;
    gap: 8px;
  }
  .cat-item:last-child { border-bottom: none; }
  .cat-item:hover { background: #f8fafc; }
  .cat-item.active { background: var(--accent-soft); }
  .cat-item a.cat-link {
    text-decoration: none;
    color: var(--text-main);
    font-size: 14px;
    font-weight: 500;
    flex: 1;
  }
  .cat-item.active a.cat-link { color: var(--accent); }
  .cat-del-btn {
    background: none; border: none; cursor: pointer;
    color: var(--text-muted); padding: 2px 4px; border-radius: 4px;
    line-height: 1; transition: color 0.15s, background 0.15s;
  }
  .cat-del-btn:hover { color: var(--danger); background: var(--danger-soft); }
  .cat-del-btn svg { width: 14px; height: 14px; display: block; }

  .add-cat-form { padding: 1rem 1.25rem; border-top: 1px solid var(--border); display: flex; gap: 8px; }
  .add-cat-form input { flex: 1; }

  /* MAIN PANEL */
  .panel {}
  .card {
    background: var(--card);
    border-radius: var(--radius);
    border: 1px solid var(--border);
    margin-bottom: 1.5rem;
    box-shadow: 0 2px 10px rgba(15,28,63,0.03);
    animation: fadeUp 0.4s ease both;
    overflow: hidden;
  }
  .card-header {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--border);
    display: flex; align-items: center; justify-content: space-between;
  }
  .card-header h2 { font-size: 16px; font-weight: 600; color: var(--text-main); }
  .card-body { padding: 1.25rem 1.5rem; }

  .add-form {
    display: grid;
    grid-template-columns: 2fr 1fr auto;
    gap: 1rem;
    align-items: center;
  }

  /* EMPTY STATE */
  .empty-state {
    text-align: center;
    padding: 3rem 1rem;
    color: var(--text-muted);
  }
  .empty-state svg { width: 48px; height: 48px; stroke: var(--border); margin-bottom: 1rem; }
  .empty-state p { font-size: 14px; }

  /* NO CATEGORY STATE */
  .no-cat-state {
    text-align: center;
    padding: 4rem 2rem;
    color: var(--text-muted);
  }
  .no-cat-state svg { width: 56px; height: 56px; stroke: #d1d9ee; margin-bottom: 1rem; }
  .no-cat-state p { font-size: 15px; }

  /* FORM CONTROLS */
  .form-control {
    width: 100%;
    padding: 0.7rem 1rem;
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    font-family: inherit; font-size: 14px; color: var(--text-main);
    outline: none; transition: border-color 0.2s, box-shadow 0.2s;
  }
  .form-control::placeholder { color: #9aa6c0; }
  .form-control:focus { border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-soft); }

  /* BUTTONS */
  .btn {
    display: inline-flex; align-items: center; justify-content: center;
    padding: 0.7rem 1.1rem;
    border-radius: var(--radius-sm);
    font-family: inherit; font-size: 14px; font-weight: 500;
    cursor: pointer; text-decoration: none; border: none;
    transition: all 0.2s; white-space: nowrap;
  }
  .btn-primary { background: var(--accent); color: #fff; }
  .btn-primary:hover { background: var(--navy-light); }
  .btn-secondary { background: #fff; border: 1px solid var(--border); color: var(--text-main); }
  .btn-secondary:hover { border-color: var(--text-muted); }
  .btn-sm { padding: 0.35rem 0.75rem; font-size: 12px; }
  .btn-danger { background: var(--danger-soft); color: var(--danger); border: 1px solid #fecaca; }
  .btn-danger:hover { background: #fecaca; }
  .btn-success { background: var(--green-soft); color: var(--green); border: 1px solid #c6ebd7; }
  .btn-success:hover { background: #c6ebd7; }
  .btn-warning { background: var(--amber-soft); color: var(--amber); border: 1px solid #ffe0b2; }
  .btn-warning:hover { background: #ffe0b2; }

  /* TABLE */
  .table-wrapper { overflow-x: auto; }
  .table { width: 100%; border-collapse: collapse; text-align: left; }
  .table th {
    background: #f8fafc; padding: 0.85rem 1.5rem;
    font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em;
    color: var(--text-muted); font-weight: 600; border-bottom: 1px solid var(--border);
  }
  .table td {
    padding: 0.85rem 1.5rem; font-size: 14px;
    color: var(--text-main); border-bottom: 1px solid var(--border);
    vertical-align: middle;
  }
  .table tr:last-child td { border-bottom: none; }
  .table tr:hover td { background: #fafbfc; }

  .edit-input {
    padding: 0.4rem 0.7rem;
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    font-family: inherit; font-size: 14px; color: var(--text-main);
    outline: none; width: 100%;
    transition: border-color 0.2s, box-shadow 0.2s;
  }
  .edit-input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-soft); }

  .actions-cell { display: flex; gap: 6px; align-items: center; }

  /* TOTAL BAR */
  .total-row { padding: 0.85rem 1.5rem; background: #f8fafc; border-top: 2px solid var(--border); display: flex; justify-content: flex-end; align-items: center; gap: 8px; }
  .total-label { font-size: 13px; color: var(--text-muted); font-weight: 500; }
  .total-value { font-size: 15px; font-weight: 700; color: var(--text-main); }
  .total-warn { color: var(--amber); }
  .total-ok { color: var(--green); }

  @keyframes fadeUp {
    from { opacity: 0; transform: translateY(14px); }
    to   { opacity: 1; transform: translateY(0); }
  }

  @media (max-width: 768px) {
    .layout { grid-template-columns: 1fr; }
    .add-form { grid-template-columns: 1fr; }
    .page-top { flex-direction: column; align-items: flex-start; gap: 1rem; }
  }
</style>
</head>
<body>

<header>
  <div class="brand">
    <div class="brand-icon">
      <svg viewBox="0 0 24 24"><path d="M12 2l2.4 7.4H22l-6.2 4.5 2.4 7.4L12 17l-6.2 4.3 2.4-7.4L2 9.4h7.6z"/></svg>
    </div>
    <span class="brand-name">Admin Dashboard</span>
  </div>
  <div class="header-right">
    <div class="avatar"><?= htmlspecialchars(mb_substr($_SESSION['admin_user'] ?? 'AD', 0, 2)) ?></div>
    <a href="../logout.php" class="btn-logout">Logout</a>
  </div>
</header>

<!-- Page Top -->
<div class="page-top">
  <div>
    <p class="eyebrow">Administration</p>
    <h1>Manage Criteria</h1>
  </div>
  <a href="dashboard.php" class="btn btn-secondary">
    <svg style="width:15px;height:15px;margin-right:6px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
    Back to Dashboard
  </a>
</div>

<?php if (!empty($msg)): ?>
<div class="alert-wrap">
  <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
</div>
<?php endif; ?>

<div class="layout">

  <!-- SIDEBAR: Categories -->
  <aside class="sidebar">
    <div class="sidebar-card">
      <div class="sidebar-header">
        <span>Categories</span>
      </div>
      <ul class="cat-list">
        <?php if (empty($categories)): ?>
          <li style="padding:1rem 1.25rem; font-size:13px; color:var(--text-muted);">No categories yet.</li>
        <?php endif; ?>
        <?php foreach ($categories as $c): ?>
          <li class="cat-item <?= $c['category_id'] == $active_cat ? 'active' : '' ?>">
            <a class="cat-link" href="criteria.php?cat=<?= $c['category_id'] ?>">
              <?= htmlspecialchars($c['category_name']) ?>
            </a>
            <button class="cat-del-btn"
              onclick="if(confirm('Delete category &quot;<?= htmlspecialchars(addslashes($c['category_name'])) ?>&quot; and ALL its criteria?')) window.location='criteria.php?delete_category=<?= $c['category_id'] ?><?= $active_cat ? '&cat='.$active_cat : '' ?>'">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/>
              </svg>
            </button>
          </li>
        <?php endforeach; ?>
      </ul>

      <!-- Add Category Form -->
      <form method="post" class="add-cat-form">
        <input type="hidden" name="add_category" value="1">
        <input type="text" name="category_name" class="form-control" placeholder="New category…" required>
        <button type="submit" class="btn btn-primary btn-sm" style="white-space:nowrap">+ Add</button>
      </form>
    </div>
  </aside>

  <!-- MAIN PANEL: Criteria -->
  <div class="panel">
    <?php if ($active_cat && $active_cat_name): ?>

      <!-- Add Criteria Form -->
      <div class="card" style="animation-delay:0.05s">
        <div class="card-header">
          <h2>Add Criteria to <em><?= htmlspecialchars($active_cat_name) ?></em></h2>
        </div>
        <div class="card-body">
          <form method="post" class="add-form">
            <input type="hidden" name="add_criteria" value="1">
            <input type="hidden" name="category_id" value="<?= $active_cat ?>">
            <input type="text" name="criteria_name" class="form-control" placeholder="e.g. Stage Presence" required>
            <input type="number" name="percentage" step="0.01" min="0" max="100" class="form-control" placeholder="Percentage (%)" required>
            <button type="submit" class="btn btn-primary">Add</button>
          </form>
        </div>
      </div>

      <!-- Criteria Table -->
      <div class="card" style="animation-delay:0.1s">
        <div class="card-header">
          <h2>Criteria — <?= htmlspecialchars($active_cat_name) ?></h2>
          <?php
            $total = array_sum(array_column($criteria_list, 'percentage'));
          ?>
          <span style="font-size:13px; color:var(--text-muted)">
            Total: <strong class="<?= abs($total - 100) < 0.01 ? 'total-ok' : 'total-warn' ?>"><?= number_format($total, 2) ?>%</strong>
          </span>
        </div>

        <div class="table-wrapper">
          <?php if (empty($criteria_list)): ?>
            <div class="empty-state">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/>
              </svg>
              <p>No criteria yet. Add one above.</p>
            </div>
          <?php else: ?>
          <table class="table">
            <thead>
              <tr>
                <th>#</th>
                <th>Criteria Name</th>
                <th>Percentage</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($criteria_list as $r):
              $is_editing = ($edit_id === (int)$r['criteria_id']);
            ?>
              <tr>
                <td><?= htmlspecialchars($r['criteria_id']) ?></td>
                <?php if ($is_editing): ?>
                  <form method="post" style="display:contents">
                    <input type="hidden" name="edit_criteria" value="1">
                    <input type="hidden" name="criteria_id" value="<?= $r['criteria_id'] ?>">
                    <td><input type="text" name="criteria_name_edit" class="edit-input" value="<?= htmlspecialchars($r['criteria_name']) ?>" required></td>
                    <td><input type="number" name="percentage_edit" step="0.01" min="0" max="100" class="edit-input" style="width:100px" value="<?= htmlspecialchars($r['percentage']) ?>" required></td>
                    <td>
                      <div class="actions-cell">
                        <button type="submit" class="btn btn-sm btn-success">Save</button>
                        <a class="btn btn-sm btn-secondary" href="criteria.php?cat=<?= $active_cat ?>">Cancel</a>
                      </div>
                    </td>
                  </form>
                <?php else: ?>
                  <td><?= htmlspecialchars($r['criteria_name']) ?></td>
                  <td><?= number_format((float)$r['percentage'], 2) ?>%</td>
                  <td>
                    <div class="actions-cell">
                      <a class="btn btn-sm btn-warning" href="criteria.php?cat=<?= $active_cat ?>&edit=<?= $r['criteria_id'] ?>">Edit</a>
                      <a class="btn btn-sm btn-danger" href="criteria.php?cat=<?= $active_cat ?>&delete_criteria=<?= $r['criteria_id'] ?>" onclick="return confirm('Delete this criteria?')">Delete</a>
                    </div>
                  </td>
                <?php endif; ?>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
          <?php endif; ?>
        </div>
      </div>

    <?php else: ?>
      <div class="card">
        <div class="no-cat-state">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
          </svg>
          <p>Add a category on the left to get started.</p>
        </div>
      </div>
    <?php endif; ?>
  </div>

</div><!-- /layout -->

</body>
</html>