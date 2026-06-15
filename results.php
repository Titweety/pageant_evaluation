<?php
require_once 'db.php';
session_start(); // Started session to check if admin avatar can be displayed safely
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Final Results</title>
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
    background: var(--cyan);
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
    color: var(--cyan);
    margin-bottom: 4px;
  }
  .page-header h1 {
    font-family: 'Playfair Display', serif;
    font-size: 28px;
    font-weight: 600;
    color: var(--text-main);
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
  }
  
  .btn-secondary {
    background: #fff;
    border: 1px solid var(--border);
    color: var(--text-main);
  }
  .btn-secondary:hover { border-color: var(--text-muted); color: var(--navy); }

  /* TABLE CARDS */
  .card {
    background: var(--card);
    border-radius: var(--radius);
    border: 1px solid var(--border);
    margin-bottom: 2rem;
    animation: fadeUp 0.4s ease both;
    box-shadow: 0 2px 10px rgba(15,28,63,0.03);
  }

  .table-wrapper { overflow-x: auto; }
  .table {
    width: 100%;
    border-collapse: collapse;
    text-align: left;
  }
  .table th {
    background: #f8fafc;
    padding: 1.1rem 1.5rem;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--text-muted);
    font-weight: 600;
    border-bottom: 1px solid var(--border);
  }
  .table td {
    padding: 1.1rem 1.5rem;
    font-size: 14px;
    color: var(--text-main);
    border-bottom: 1px solid var(--border);
  }
  .table tr:last-child td { border-bottom: none; }
  .table tr:hover td { background: #fafbfc; }

  /* Special Highlight for Rank 1 */
  .table tr:first-child td:first-child {
    font-weight: 600;
    color: var(--amber);
  }

  /* Animations */
  @keyframes fadeUp {
    from { opacity: 0; transform: translateY(16px); }
    to   { opacity: 1; transform: translateY(0); }
  }

  @media (max-width: 768px) {
    main { padding: 1.5rem 1rem; }
    .page-header { flex-direction: column; align-items: flex-start; gap: 1rem; }
  }
</style>
</head>
<body>

<header>
  <div class="brand">
    <div class="brand-icon">
      <svg viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
    </div>
    <span class="brand-name">Results Portal</span>
  </div>
  <?php if (isset($_SESSION['admin_user'])): ?>
  <div class="header-right">
    <div class="avatar"><?= htmlspecialchars(mb_substr($_SESSION['admin_user'], 0, 2)) ?></div>
  </div>
  <?php endif; ?>
</header>

<main>
  <div class="page-header">
    <div class="header-titles">
      <p class="eyebrow">Leaderboard</p>
      <h1>Final Results</h1>
    </div>
    <a href="./admin/dashboard.php" class="btn btn-secondary">
      <svg style="width:16px;height:16px;margin-right:6px;vertical-align:middle" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
      Back to Dashboard
    </a>
  </div>

  <div class="card table-wrapper">
    <table class="table">
      <thead>
        <tr>
          <th style="width: 15%">Rank</th>
          <th>Contestant</th>
          <th style="width: 25%">Total Score</th>
        </tr>
      </thead>
      <tbody>
      <?php
      $res = $conn->query("SELECT r.rank, c.full_name, r.total_score FROM results r JOIN contestants c ON r.contestant_id = c.contestant_id ORDER BY r.rank ASC");
      while ($row = $res->fetch_assoc()) {
          echo '<tr>
                  <td>' . htmlspecialchars($row['rank']) . '</td>
                  <td><strong>' . htmlspecialchars($row['full_name']) . '</strong></td>
                  <td>' . htmlspecialchars($row['total_score']) . '</td>
                </tr>';
      }
      ?>
      </tbody>
    </table>
  </div>
</main>

</body>
</html>