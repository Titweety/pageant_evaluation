<?php
require_once __DIR__ . '/../db.php';
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $name = trim($_POST['name']);
    $age = intval($_POST['age']);
    $remarks = trim($_POST['remarks']);
    $stmt = $conn->prepare("INSERT INTO contestants (full_name, age, remarks) VALUES (?, ?, ?)"); 
    $stmt->bind_param('sis', $name, $age, $remarks);
    $stmt->execute(); $stmt->close();
    $msg = 'Contestant added successfully.';
}
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM contestants WHERE contestant_id = ?"); $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close();
    $msg = 'Contestant deleted successfully.';
}

$res = $conn->query("SELECT * FROM contestants ORDER BY created_at ASC");
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
    --amber: #d97c10;
    --amber-soft: #fff3e0;
    --cyan: #00b4d8;
    --cyan-soft: #e0f7fb;
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

  .brand {
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .brand-icon {
    width: 32px;
    height: 32px;
    background: var(--accent);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  .brand-icon svg { width: 18px; height: 18px; fill: none; stroke: #fff; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }

  .brand-name {
    font-size: 16px;
    font-weight: 600;
    color: #fff;
    letter-spacing: 0.01em;
  }

  .header-right {
    display: flex;
    align-items: center;
    gap: 14px;
  }

  .avatar {
    width: 34px;
    height: 34px;
    background: var(--navy-light);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 600;
    color: #c5d3f5;
    border: 1.5px solid rgba(255,255,255,0.15);
    text-transform: uppercase;
  }

  .btn-logout {
    background: rgba(255,255,255,0.08);
    border: 1px solid rgba(255,255,255,0.18);
    color: #c5d3f5;
    font-family: 'DM Sans', sans-serif;
    font-size: 13px;
    font-weight: 500;
    padding: 6px 14px;
    border-radius: var(--radius-sm);
    cursor: pointer;
    text-decoration: none;
    transition: background 0.2s;
  }
  .btn-logout:hover { background: rgba(255,255,255,0.14); color: #fff; }

  /* MAIN */
  main {
    max-width: 1100px;
    margin: 0 auto;
    padding: 2.5rem 2rem;
  }

  .page-header { 
    display: flex; 
    justify-content: space-between; 
    align-items: flex-end; 
    margin-bottom: 2rem; 
  }
  
  .eyebrow {
    font-size: 12px;
    font-weight: 500;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: var(--accent);
    margin-bottom: 4px;
  }
  .page-header h1 {
    font-family: 'Playfair Display', serif;
    font-size: 28px;
    font-weight: 600;
    color: var(--text-main);
  }

  /* ALERTS */
  .alert {
    padding: 1rem 1.5rem;
    border-radius: var(--radius-sm);
    margin-bottom: 1.5rem;
    font-size: 14px;
    font-weight: 500;
    animation: fadeUp 0.3s ease both;
  }
  .alert-success {
    background: var(--green-soft);
    color: var(--green);
    border: 1px solid #c6ebd7;
  }

  /* CARDS & FORMS */
  .card {
    background: var(--card);
    border-radius: var(--radius);
    border: 1px solid var(--border);
    margin-bottom: 2rem;
    animation: fadeUp 0.4s ease both;
    box-shadow: 0 2px 10px rgba(15,28,63,0.03);
  }

  .card-body {
    padding: 1.5rem;
  }

  .add-form {
    display: grid;
    grid-template-columns: 2fr 1fr 2fr 1fr;
    gap: 1rem;
    align-items: center;
  }

  .form-control {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    font-family: inherit;
    font-size: 14px;
    color: var(--text-main);
    outline: none;
    transition: border-color 0.2s, box-shadow 0.2s;
  }
  .form-control::placeholder { color: #9aa6c0; }
  .form-control:focus { 
    border-color: var(--accent); 
    box-shadow: 0 0 0 3px var(--accent-soft);
  }

  /* BUTTONS */
  .btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.75rem 1.2rem;
    border-radius: var(--radius-sm);
    font-family: inherit;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    text-decoration: none;
    border: none;
    transition: all 0.2s;
    width: 100%;
  }
  .btn-primary { background: var(--accent); color: #fff; }
  .btn-primary:hover { background: var(--navy-light); }
  
  .btn-secondary {
    background: #fff;
    border: 1px solid var(--border);
    color: var(--text-main);
    width: auto;
  }
  .btn-secondary:hover { border-color: var(--text-muted); color: var(--navy); }

  .btn-sm { padding: 0.4rem 0.8rem; font-size: 12px; width: auto; }
  .btn-danger {
    background: var(--danger-soft);
    color: var(--danger);
    border: 1px solid #fecaca;
  }
  .btn-danger:hover { background: #fecaca; }

  /* TABLE */
  .table-wrapper { overflow-x: auto; }
  .table {
    width: 100%;
    border-collapse: collapse;
    text-align: left;
  }
  .table th {
    background: #f8fafc;
    padding: 1rem 1.5rem;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--text-muted);
    font-weight: 600;
    border-bottom: 1px solid var(--border);
  }
  .table td {
    padding: 1rem 1.5rem;
    font-size: 14px;
    color: var(--text-main);
    border-bottom: 1px solid var(--border);
  }
  .table tr:last-child td { border-bottom: none; }
  .table tr:hover td { background: #fafbfc; }

  /* Animations */
  @keyframes fadeUp {
    from { opacity: 0; transform: translateY(16px); }
    to   { opacity: 1; transform: translateY(0); }
  }

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

  <div class="card">
    <div class="card-body">
      <form method="post" class="add-form">
        <input type="hidden" name="add" value="1">
        <input type="text" name="name" class="form-control" placeholder="Full name" required>
        <input type="number" name="age" class="form-control" placeholder="Age">
        <input type="text" name="remarks" class="form-control" placeholder="Remarks">
        <button type="submit" class="btn btn-primary">Add Contestant</button>
      </form>
    </div>
  </div>

  <div class="card table-wrapper" style="animation-delay: 0.1s;">
    <table class="table">
      <thead>
        <tr>
          <th>#</th>
          <th>Name</th>
          <th>Age</th>
          <th>Remarks</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
      <?php while ($r = $res->fetch_assoc()): ?>
        <tr>
          <td><?= htmlspecialchars($r['contestant_id']) ?></td>
          <td><?= htmlspecialchars($r['full_name']) ?></td>
          <td><?= htmlspecialchars($r['age'] ?: '-') ?></td>
          <td><?= htmlspecialchars($r['remarks'] ?: '-') ?></td>
          <td>
            <a class="btn btn-sm btn-danger" href="contestants.php?delete=<?= $r['contestant_id'] ?>" onclick="return confirm('Are you sure you want to delete this contestant?')">Delete</a>
          </td>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</main>

</body>
</html>