<?php
require_once __DIR__ . '/../db.php';
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }

$msg = '';

// Add contestant
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $name       = trim($_POST['name']);
    $age        = intval($_POST['age']);
    $remarks    = trim($_POST['remarks']);
    $category_id = $_POST['category_id'] !== '' ? (int)$_POST['category_id'] : null;

    $stmt = $conn->prepare("INSERT INTO contestants (full_name, age, remarks, category_id) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('sisi', $name, $age, $remarks, $category_id);
    $stmt->execute(); $stmt->close();
    $msg = 'Contestant added successfully.';
}

// Delete contestant
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM contestants WHERE contestant_id = ?");
    $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close();
    $msg = 'Contestant deleted successfully.';
}

// Edit contestant
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit'])) {
    $id          = (int)$_POST['contestant_id'];
    $name        = trim($_POST['name']);
    $age         = intval($_POST['age']);
    $remarks     = trim($_POST['remarks']);
    $category_id = $_POST['category_id'] !== '' ? (int)$_POST['category_id'] : null;

    $stmt = $conn->prepare("UPDATE contestants SET full_name=?, age=?, remarks=?, category_id=? WHERE contestant_id=?");
    $stmt->bind_param('sisii', $name, $age, $remarks, $category_id, $id);
    $stmt->execute(); $stmt->close();
    $msg = 'Contestant updated successfully.';
}

// Fetch categories for dropdown
$cats_res = $conn->query("SELECT * FROM categories ORDER BY category_name ASC");
$categories = [];
while ($c = $cats_res->fetch_assoc()) $categories[] = $c;

// Fetch contestants with category name
$res = $conn->query("
    SELECT c.*, cat.category_name
    FROM contestants c
    LEFT JOIN categories cat ON cat.category_id = c.category_id
    ORDER BY c.created_at ASC
");

$edit_id = isset($_GET['edit']) ? (int)$_GET['edit'] : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Manage Contestants</title>
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
    --danger: #ef4444;
    --danger-soft: #fee2e2;
    --amber: #d97c10;
    --amber-soft: #fff3e0;
    --text-main: #0f1c3f;
    --text-muted: #6b7a99;
    --border: #e4e8f0;
    --bg: #f4f6fb;
    --card: #ffffff;
    --radius: 14px;
    --radius-sm: 8px;
  }

  body { font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--text-main); min-height: 100vh; }

  header {
    background: var(--navy); padding: 0 2rem; height: 62px;
    display: flex; align-items: center; justify-content: space-between;
    position: sticky; top: 0; z-index: 100;
  }
  .brand { display: flex; align-items: center; gap: 10px; }
  .brand-icon { width: 32px; height: 32px; background: var(--accent); border-radius: 8px; display: flex; align-items: center; justify-content: center; }
  .brand-icon svg { width: 18px; height: 18px; fill: none; stroke: #fff; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
  .brand-name { font-size: 16px; font-weight: 600; color: #fff; }
  .header-right { display: flex; align-items: center; gap: 14px; }
  .avatar { width: 34px; height: 34px; background: var(--navy-light); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 600; color: #c5d3f5; border: 1.5px solid rgba(255,255,255,0.15); text-transform: uppercase; }
  .btn-logout { background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.18); color: #c5d3f5; font-family: 'DM Sans', sans-serif; font-size: 13px; font-weight: 500; padding: 6px 14px; border-radius: var(--radius-sm); cursor: pointer; text-decoration: none; transition: background 0.2s; }
  .btn-logout:hover { background: rgba(255,255,255,0.14); color: #fff; }

  main { max-width: 1100px; margin: 0 auto; padding: 2.5rem 2rem; }

  .page-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 2rem; }
  .eyebrow { font-size: 12px; font-weight: 500; letter-spacing: 0.1em; text-transform: uppercase; color: var(--accent); margin-bottom: 4px; }
  .page-header h1 { font-family: 'Playfair Display', serif; font-size: 28px; font-weight: 600; color: var(--text-main); }

  .alert { padding: 1rem 1.5rem; border-radius: var(--radius-sm); margin-bottom: 1.5rem; font-size: 14px; font-weight: 500; animation: fadeUp 0.3s ease both; }
  .alert-success { background: var(--green-soft); color: var(--green); border: 1px solid #c6ebd7; }

  .card { background: var(--card); border-radius: var(--radius); border: 1px solid var(--border); margin-bottom: 2rem; animation: fadeUp 0.4s ease both; box-shadow: 0 2px 10px rgba(15,28,63,0.03); }
  .card-body { padding: 1.5rem; }

  .add-form {
    display: grid;
    grid-template-columns: 2fr 0.6fr 1.5fr 1.5fr auto;
    gap: 1rem;
    align-items: center;
  }

  .form-control {
    width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--border);
    border-radius: var(--radius-sm); font-family: inherit; font-size: 14px;
    color: var(--text-main); outline: none; transition: border-color 0.2s, box-shadow 0.2s;
    background: #fff;
  }
  .form-control::placeholder { color: #9aa6c0; }
  .form-control:focus { border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-soft); }

  select.form-control { appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%236b7a99' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 12px center; padding-right: 32px; }

  .btn { display: inline-flex; align-items: center; justify-content: center; padding: 0.75rem 1.2rem; border-radius: var(--radius-sm); font-family: inherit; font-size: 14px; font-weight: 500; cursor: pointer; text-decoration: none; border: none; transition: all 0.2s; white-space: nowrap; }
  .btn-primary { background: var(--accent); color: #fff; }
  .btn-primary:hover { background: var(--navy-light); }
  .btn-secondary { background: #fff; border: 1px solid var(--border); color: var(--text-main); width: auto; }
  .btn-secondary:hover { border-color: var(--text-muted); color: var(--navy); }
  .btn-sm { padding: 0.4rem 0.8rem; font-size: 12px; width: auto; }
  .btn-danger { background: var(--danger-soft); color: var(--danger); border: 1px solid #fecaca; }
  .btn-danger:hover { background: #fecaca; }
  .btn-warning { background: var(--amber-soft); color: var(--amber); border: 1px solid #ffe0b2; }
  .btn-warning:hover { background: #ffe0b2; }
  .btn-success { background: var(--green-soft); color: var(--green); border: 1px solid #c6ebd7; }
  .btn-success:hover { background: #c6ebd7; }

  .table-wrapper { overflow-x: auto; }
  .table { width: 100%; border-collapse: collapse; text-align: left; }
  .table th { background: #f8fafc; padding: 1rem 1.5rem; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); font-weight: 600; border-bottom: 1px solid var(--border); }
  .table td { padding: 0.85rem 1.5rem; font-size: 14px; color: var(--text-main); border-bottom: 1px solid var(--border); vertical-align: middle; }
  .table tr:last-child td { border-bottom: none; }
  .table tr:hover td { background: #fafbfc; }

  .actions-cell { display: flex; gap: 6px; }

  .cat-pill {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
    background: var(--accent-soft);
    color: var(--accent);
  }
  .cat-pill.none { background: #f1f3f7; color: var(--text-muted); }

  .edit-input { padding: 0.4rem 0.7rem; border: 1px solid var(--border); border-radius: var(--radius-sm); font-family: inherit; font-size: 13px; color: var(--text-main); outline: none; width: 100%; transition: border-color 0.2s, box-shadow 0.2s; background: #fff; }
  .edit-input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-soft); }
  select.edit-input { appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%236b7a99' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 8px center; padding-right: 24px; }

  @keyframes fadeUp { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }

  @media (max-width: 768px) {
    .add-form { grid-template-columns: 1fr; }
    main { padding: 1.5rem 1rem; }
    .page-header { flex-direction: column; align-items: flex-start; gap: 1rem; }
  }
</style>
</head>
<body>

<header>
  <div class="brand">
    <div class="brand-icon">
      <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
    </div>
    <span class="brand-name">Admin Dashboard</span>
  </div>
  <div class="header-right">
    <div class="avatar"><?= htmlspecialchars(mb_substr($_SESSION['admin_user'] ?? 'AD', 0, 2)) ?></div>
    <a href="../logout.php" class="btn-logout">Logout</a>
  </div>
</header>

<main>
  <div class="page-header">
    <div class="header-titles">
      <p class="eyebrow">Administration</p>
      <h1>Manage Contestants</h1>
    </div>
    <a href="dashboard.php" class="btn btn-secondary">
      <svg style="width:16px;height:16px;margin-right:6px;vertical-align:middle" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
      Back to Dashboard
    </a>
  </div>

  <?php if (!empty($msg)): ?>
    <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>

  <!-- Add Form -->
  <div class="card">
    <div class="card-body">
      <form method="post" class="add-form">
        <input type="hidden" name="add" value="1">
        <input type="text" name="name" class="form-control" placeholder="Full name" required>
        <input type="number" name="age" class="form-control" placeholder="Age" min="1">
        <input type="text" name="remarks" class="form-control" placeholder="Remarks">
        <select name="category_id" class="form-control">
          <option value="">— No Category —</option>
          <?php foreach ($categories as $c): ?>
            <option value="<?= $c['category_id'] ?>"><?= htmlspecialchars($c['category_name']) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary">Add Contestant</button>
      </form>
    </div>
  </div>

  <!-- Table -->
  <div class="card table-wrapper" style="animation-delay: 0.1s;">
    <table class="table">
      <thead>
        <tr>
          <th>#</th>
          <th>Name</th>
          <th>Age</th>
          <th>Category</th>
          <th>Remarks</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
      <?php while ($r = $res->fetch_assoc()):
        $is_editing = ($edit_id === (int)$r['contestant_id']);
      ?>
        <tr>
          <td><?= htmlspecialchars($r['contestant_id']) ?></td>

          <?php if ($is_editing): ?>
            <form method="post" style="display:contents">
              <input type="hidden" name="edit" value="1">
              <input type="hidden" name="contestant_id" value="<?= $r['contestant_id'] ?>">
              <td><input type="text" name="name" class="edit-input" value="<?= htmlspecialchars($r['full_name']) ?>" required></td>
              <td><input type="number" name="age" class="edit-input" style="width:70px" value="<?= htmlspecialchars($r['age']) ?>" min="1"></td>
              <td>
                <select name="category_id" class="edit-input">
                  <option value="">— None —</option>
                  <?php foreach ($categories as $c): ?>
                    <option value="<?= $c['category_id'] ?>" <?= $c['category_id'] == $r['category_id'] ? 'selected' : '' ?>>
                      <?= htmlspecialchars($c['category_name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </td>
              <td><input type="text" name="remarks" class="edit-input" value="<?= htmlspecialchars($r['remarks']) ?>"></td>
              <td>
                <div class="actions-cell">
                  <button type="submit" class="btn btn-sm btn-success">Save</button>
                  <a href="contestants.php" class="btn btn-sm btn-secondary">Cancel</a>
                </div>
              </td>
            </form>
          <?php else: ?>
            <td><?= htmlspecialchars($r['full_name']) ?></td>
            <td><?= htmlspecialchars($r['age'] ?: '—') ?></td>
            <td>
              <?php if ($r['category_name']): ?>
                <span class="cat-pill"><?= htmlspecialchars($r['category_name']) ?></span>
              <?php else: ?>
                <span class="cat-pill none">Unassigned</span>
              <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($r['remarks'] ?: '—') ?></td>
            <td>
              <div class="actions-cell">
                <a class="btn btn-sm btn-warning" href="contestants.php?edit=<?= $r['contestant_id'] ?>">Edit</a>
                <a class="btn btn-sm btn-danger" href="contestants.php?delete=<?= $r['contestant_id'] ?>" onclick="return confirm('Delete this contestant?')">Delete</a>
              </div>
            </td>
          <?php endif; ?>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</main>

</body>
</html>
