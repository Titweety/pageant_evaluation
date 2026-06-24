<?php
require_once 'db.php';
session_start();

// Fetch all categories
$cats_res = $conn->query("SELECT * FROM categories ORDER BY created_at ASC");
$categories = [];
while ($c = $cats_res->fetch_assoc()) $categories[] = $c;

// Active category filter (null = show all)
$active_cat = isset($_GET['cat']) && $_GET['cat'] !== '' ? (int)$_GET['cat'] : null;
$active_cat_name = 'All Categories';
if ($active_cat) {
    foreach ($categories as $c) {
        if ($c['category_id'] == $active_cat) { $active_cat_name = $c['category_name']; break; }
    }
}

// Filtered View: Pull strictly from the results table to ensure data matches perfectly
if ($active_cat) {
    $stmt = $conn->prepare("
        SELECT 
            c.full_name,
            r.total_score,
            cat.category_name
        FROM results r
        INNER JOIN contestants c ON c.contestant_id = r.contestant_id
        INNER JOIN categories cat ON cat.category_id = c.category_id
        WHERE c.category_id = ?
        ORDER BY r.total_score DESC
    ");
    $stmt->bind_param('i', $active_cat);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = [];
    $rank = 1;
    // Dynamically rank them 1, 2, 3 just for this category view
    while ($row = $res->fetch_assoc()) {
        $row['rank'] = $rank++;
        $rows[] = $row;
    }
} else {
    // Show all — use results table for stored overall ranks
    $res = $conn->query("
        SELECT 
            r.rank,
            c.full_name,
            r.total_score,
            cat.category_name
        FROM results r
        INNER JOIN contestants c ON c.contestant_id = r.contestant_id
        LEFT JOIN categories cat ON cat.category_id = c.category_id
        ORDER BY r.rank ASC
    ");
    $rows = [];
    while ($row = $res->fetch_assoc()) $rows[] = $row;
}

// Count per category for tab badges
function getCatCount($conn, $cat_id) {
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT s.contestant_id) as n
        FROM scores s
        INNER JOIN contestants c ON c.contestant_id = s.contestant_id
        WHERE c.category_id = ?
    ");
    $stmt->bind_param('i', $cat_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc()['n'];
}

$all_count = $conn->query("SELECT COUNT(*) as n FROM results")->fetch_assoc()['n'];
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
    --silver: #8a95a3;
    --bronze: #b87333;
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
  .brand-icon { width: 32px; height: 32px; background: var(--cyan); border-radius: 8px; display: flex; align-items: center; justify-content: center; }
  .brand-icon svg { width: 18px; height: 18px; fill: none; stroke: #fff; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
  .brand-name { font-size: 16px; font-weight: 600; color: #fff; }
  .header-right { display: flex; align-items: center; gap: 14px; }
  .avatar { width: 34px; height: 34px; background: var(--navy-light); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 600; color: #c5d3f5; border: 1.5px solid rgba(255,255,255,0.15); text-transform: uppercase; }

  main { max-width: 1100px; margin: 0 auto; padding: 2.5rem 2rem; }

  .page-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 2rem; }
  .eyebrow { font-size: 12px; font-weight: 500; letter-spacing: 0.1em; text-transform: uppercase; color: var(--cyan); margin-bottom: 4px; }
  .page-header h1 { font-family: 'Playfair Display', serif; font-size: 28px; font-weight: 600; color: var(--text-main); }

  .btn { display: inline-flex; align-items: center; justify-content: center; padding: 0.75rem 1.2rem; border-radius: var(--radius-sm); font-family: inherit; font-size: 14px; font-weight: 500; cursor: pointer; text-decoration: none; border: none; transition: all 0.2s; }
  .btn-secondary { background: #fff; border: 1px solid var(--border); color: var(--text-main); }
  .btn-secondary:hover { border-color: var(--text-muted); color: var(--navy); }

  .section-label { font-size: 12px; font-weight: 500; letter-spacing: 0.08em; text-transform: uppercase; color: var(--text-muted); margin-bottom: 12px; }
  .filter-tabs { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 1.75rem; }
  .filter-tab {
    padding: 8px 18px; border-radius: 10px; font-size: 13px; font-weight: 500;
    text-decoration: none; border: 1.5px solid var(--border); color: var(--text-muted);
    background: #fff; transition: all 0.18s; display: flex; align-items: center; gap: 6px;
  }
  .filter-tab:hover { border-color: var(--cyan); color: var(--cyan); background: var(--cyan-soft); }
  .filter-tab.active { background: var(--navy); color: #fff; border-color: var(--navy); }
  .filter-tab .count { background: rgba(255,255,255,0.2); border-radius: 20px; font-size: 11px; padding: 1px 7px; font-weight: 600; }
  .filter-tab:not(.active) .count { background: var(--bg); color: var(--text-muted); }

  .card { background: var(--card); border-radius: var(--radius); border: 1px solid var(--border); margin-bottom: 2rem; animation: fadeUp 0.4s ease both; box-shadow: 0 2px 10px rgba(15,28,63,0.03); overflow: hidden; }
  .card-header { padding: 1rem 1.5rem; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; }
  .card-header h2 { font-size: 15px; font-weight: 600; color: var(--text-main); }
  .result-count { font-size: 13px; color: var(--text-muted); }

  .table-wrapper { overflow-x: auto; }
  .table { width: 100%; border-collapse: collapse; text-align: left; }
  .table th { background: #f8fafc; padding: 1rem 1.5rem; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); font-weight: 600; border-bottom: 1px solid var(--border); }
  .table td { padding: 1rem 1.5rem; font-size: 14px; color: var(--text-main); border-bottom: 1px solid var(--border); }
  .table tr:last-child td { border-bottom: none; }
  .table tr:hover td { background: #fafbfc; }

  .rank-cell { display: flex; align-items: center; gap: 8px; font-weight: 600; }
  .medal { width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 700; flex-shrink: 0; }
  .medal-1 { background: #fff8e1; color: var(--amber); border: 1.5px solid #ffe082; }
  .medal-2 { background: #f5f5f5; color: var(--silver); border: 1.5px solid #e0e0e0; }
  .medal-3 { background: #fbe9e7; color: var(--bronze); border: 1.5px solid #ffccbc; }
  .medal-n { background: #f0f2f8; color: var(--text-muted); border: 1.5px solid var(--border); font-size: 12px; }

  .score-pill { display: inline-block; background: var(--accent-soft); color: var(--accent); border-radius: 20px; padding: 3px 12px; font-size: 13px; font-weight: 600; }
  .cat-pill { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 500; background: var(--cyan-soft); color: var(--cyan); }

  .empty-state { text-align: center; padding: 4rem 2rem; color: var(--text-muted); }
  .empty-state svg { width: 52px; height: 52px; stroke: var(--border); margin-bottom: 1rem; }
  .empty-state p { font-size: 15px; }

  @keyframes fadeUp { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }

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
      <svg style="width:16px;height:16px;margin-right:6px;vertical-align:middle" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
      Back to Dashboard
    </a>
  </div>

  <div class="section-label">Filter by Category</div>
  <div class="filter-tabs">
    <a href="results.php" class="filter-tab <?= !$active_cat ? 'active' : '' ?>">
      🏆 All <span class="count"><?= $all_count ?></span>
    </a>
    <?php foreach ($categories as $c):
      $cnt = getCatCount($conn, $c['category_id']);
    ?>
      <a href="results.php?cat=<?= $c['category_id'] ?>"
         class="filter-tab <?= $active_cat === (int)$c['category_id'] ? 'active' : '' ?>">
        <?= htmlspecialchars($c['category_name']) ?>
        <span class="count"><?= $cnt ?></span>
      </a>
    <?php endforeach; ?>
  </div>

  <div class="card">
    <div class="card-header">
      <h2><?= htmlspecialchars($active_cat_name) ?></h2>
      <span class="result-count"><?= count($rows) ?> contestant<?= count($rows) !== 1 ? 's' : '' ?></span>
    </div>
    <div class="table-wrapper">
      <?php if (empty($rows)): ?>
        <div class="empty-state">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
          </svg>
          <p>No results found<?= $active_cat ? ' for this category' : '' ?>.</p>
        </div>
      <?php else: ?>
      <table class="table">
        <thead>
          <tr>
            <th>Rank</th>
            <th>Contestant</th>
            <th>Category</th>
            <th>Total Score</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $row):
            $rank = (int)$row['rank'];
          ?>
          <tr>
            <td>
              <div class="rank-cell">
                <?php if ($rank === 1): ?>
                  <div class="medal medal-1">🥇</div>
                <?php elseif ($rank === 2): ?>
                  <div class="medal medal-2">🥈</div>
                <?php elseif ($rank === 3): ?>
                  <div class="medal medal-3">🥉</div>
                <?php else: ?>
                  <div class="medal medal-n"><?= $rank ?></div>
                <?php endif; ?>
                #<?= $rank ?>
              </div>
            </td>
            <td><strong><?= htmlspecialchars($row['full_name']) ?></strong></td>
            
            <td>
              <?php if (!empty($row['category_name'])): ?>
                <span class="cat-pill"><?= htmlspecialchars($row['category_name']) ?></span>
              <?php else: ?>
                <span style="color:var(--text-muted);font-size:13px">—</span>
              <?php endif; ?>
            </td>
            
            <td><span class="score-pill"><?= number_format((float)$row['total_score'], 2) ?></span></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>
</main>

</body>
</html>