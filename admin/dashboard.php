<?php
require_once __DIR__ . '/../db.php';
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }

// Helper to count rows using MySQLi
function db_count($conn, $table) {
    $res = $conn->query("SELECT COUNT(*) FROM `$table`");
    if (!$res) return 0;
    return (int)$res->fetch_row()[0];
}

// Support $conn, $pdo, or $db variable names
$db_conn = isset($conn) ? $conn : (isset($pdo) ? $pdo : (isset($db) ? $db : null));

$judges      = $db_conn ? db_count($db_conn, 'judges')      : 0;
$contestants = $db_conn ? db_count($db_conn, 'contestants') : 0;
$categories  = $db_conn ? db_count($db_conn, 'categories')  : 0; 
$scores      = $db_conn ? db_count($db_conn, 'scores')      : 0;
$total_expected = max(3, $judges * $contestants);
$score_pct   = $total_expected > 0 ? round(($scores / $total_expected) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Admin Dashboard</title>
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

  .page-header { margin-bottom: 2rem; }
  .page-header .eyebrow {
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

  /* BALANCED STATS ROW (Changed to 3 columns) */
  .stats-row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-bottom: 2.5rem;
  }

  .stat-card {
    background: var(--card);
    border-radius: var(--radius);
    border: 1px solid var(--border);
    padding: 1.25rem 1.5rem;
    transition: box-shadow 0.2s, transform 0.2s;
  }
  .stat-card:hover { box-shadow: 0 4px 20px rgba(15,28,63,0.08); transform: translateY(-2px); }

  .stat-icon {
    width: 36px;
    height: 36px;
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 12px;
  }
  .stat-icon svg { width: 18px; height: 18px; fill: none; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }
  .stat-icon.blue { background: var(--accent-soft); }
  .stat-icon.blue svg { stroke: var(--accent); }
  .stat-icon.green { background: var(--green-soft); }
  .stat-icon.green svg { stroke: var(--green); }
  .stat-icon.amber { background: var(--amber-soft); }
  .stat-icon.amber svg { stroke: var(--amber); }

  .stat-val { font-size: 28px; font-weight: 600; color: var(--text-main); line-height: 1; margin-bottom: 4px; }
  .stat-label { font-size: 13px; color: var(--text-muted); font-weight: 500; }

  /* SECTION TITLE */
  .section-title {
    font-size: 12px;
    font-weight: 600;
    letter-spacing: 0.09em;
    text-transform: uppercase;
    color: var(--text-muted);
    margin-bottom: 14px;
  }

  /* ACTION CARDS GRID */
  .actions-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-bottom: 2.5rem;
  }

  /* BOTTOM ROW (Centered single card) */
  .bottom-row {
    display: flex;
    justify-content: center;
    margin-bottom: 2rem;
  }
  .bottom-row .action-card {
    width: 100%;
    max-width: 350px; /* Keeps the card perfectly proportional to the grid above it */
  }

  .action-card {
    background: var(--card);
    border-radius: var(--radius);
    border: 1px solid var(--border);
    padding: 1.4rem 1.5rem;
    cursor: pointer;
    text-decoration: none;
    color: inherit;
    display: flex;
    align-items: center;
    gap: 14px;
    transition: box-shadow 0.2s, transform 0.2s, border-color 0.2s;
  }
  .action-card:hover {
    box-shadow: 0 6px 24px rgba(15,28,63,0.10);
    transform: translateY(-2px);
    border-color: var(--accent);
    color: inherit;
  }

  .action-icon {
    width: 44px;
    height: 44px;
    border-radius: 11px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    background: var(--accent-soft);
  }
  .action-icon svg { width: 22px; height: 22px; stroke: var(--accent); fill: none; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }

  .action-text { flex: 1; min-width: 0; }
  .action-title { font-size: 15px; font-weight: 600; color: var(--text-main); margin-bottom: 2px; }
  .action-sub { font-size: 12px; color: var(--text-muted); }

  .action-arrow {
    width: 28px;
    height: 28px;
    border-radius: 8px;
    background: var(--bg);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: background 0.2s;
  }
  .action-card:hover .action-arrow { background: var(--accent); }
  .action-arrow svg { width: 14px; height: 14px; stroke: var(--text-muted); fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
  .action-card:hover .action-arrow svg { stroke: #fff; }

  /* Color variants */
  .action-card.results .action-icon { background: var(--cyan-soft); }
  .action-card.results .action-icon svg { stroke: var(--cyan); }
  .action-card.results:hover { border-color: var(--cyan); }
  .action-card.results:hover .action-arrow { background: var(--cyan); }

  /* Animations */
  @keyframes fadeUp {
    from { opacity: 0; transform: translateY(16px); }
    to   { opacity: 1; transform: translateY(0); }
  }
  .stat-card, .action-card { animation: fadeUp 0.45s ease both; }
  .stat-card:nth-child(1) { animation-delay: 0.05s; }
  .stat-card:nth-child(2) { animation-delay: 0.10s; }
  .stat-card:nth-child(3) { animation-delay: 0.15s; }
  .actions-grid .action-card:nth-child(1) { animation-delay: 0.18s; }
  .actions-grid .action-card:nth-child(2) { animation-delay: 0.23s; }
  .actions-grid .action-card:nth-child(3) { animation-delay: 0.28s; }
  .bottom-row .action-card { animation-delay: 0.35s; }

  /* RESPONSIVE DESIGN */
  @media (max-width: 900px) {
    .stats-row, .actions-grid { grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); }
  }
  @media (max-width: 600px) {
    .stats-row, .actions-grid { grid-template-columns: 1fr; }
    .bottom-row .action-card { max-width: 100%; }
    main { padding: 1.5rem 1rem; }
  }
</style>
</head>
<body>

<header>
  <div class="brand">
    <div class="brand-icon">
      <svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
    </div>
    <span class="brand-name">Admin Dashboard</span>
  </div>
  <div class="header-right">
    <div class="avatar"><?= htmlspecialchars(mb_substr($_SESSION['admin_user'], 0, 2)) ?></div>
    <a href="../logout.php" class="btn-logout">Logout</a>
  </div>
</header>

<main>
  <div class="page-header">
    <p class="eyebrow">Control Panel</p>
    <h1>Welcome back, <?= htmlspecialchars($_SESSION['admin_user']) ?></h1>
  </div>

  <div class="stats-row">
    <div class="stat-card">
      <div class="stat-icon blue">
        <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
      </div>
      <div class="stat-val"><?= (int)$judges ?></div>
      <div class="stat-label">Judges</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon green">
        <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
      </div>
      <div class="stat-val"><?= (int)$contestants ?></div>
      <div class="stat-label">Contestants</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon amber">
        <svg viewBox="0 0 24 24"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
      </div>
      <div class="stat-val"><?= (int)$categories ?></div>
      <div class="stat-label">Categories</div> 
    </div>
  </div>

  <p class="section-title">Manage</p>
  <div class="actions-grid">
    <a href="judges.php" class="action-card">
      <div class="action-icon">
        <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
      </div>
      <div class="action-text">
        <div class="action-title">Manage Judges</div>
        <div class="action-sub">Add, edit, or remove judges</div>
      </div>
      <div class="action-arrow">
        <svg viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
      </div>
    </a>

    <a href="contestants.php" class="action-card">
      <div class="action-icon">
        <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
      </div>
      <div class="action-text">
        <div class="action-title">Manage Contestants</div>
        <div class="action-sub">Register and manage participants</div>
      </div>
      <div class="action-arrow">
        <svg viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
      </div>
    </a>

    <a href="categories.php" class="action-card">
      <div class="action-icon">
        <svg viewBox="0 0 24 24"><path d="M12 2l2.4 7.4H22l-6.2 4.5 2.4 7.4L12 17l-6.2 4.3 2.4-7.4L2 9.4h7.6z"/></svg>
      </div>
      <div class="action-text">
        <div class="action-title">Manage Categories</div>
        <div class="action-sub">Add or manage event categories</div>
      </div>
      <div class="action-arrow">
        <svg viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
      </div>
    </a>
  </div>

  <p class="section-title">Reports & Outcomes</p>
  <div class="bottom-row">
    <a href="../results.php" class="action-card results">
      <div class="action-icon">
        <svg viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
      </div>
      <div class="action-text">
        <div class="action-title">View Results</div>
        <div class="action-sub">See final rankings &amp; scores</div>
      </div>
      <div class="action-arrow">
        <svg viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
      </div>
    </a>
  </div>
</main>

</body>
</html>