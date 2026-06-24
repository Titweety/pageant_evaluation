<?php
require_once __DIR__ . '/../db.php';
session_start();
if (!isset($_SESSION['judge_id'])) { header('Location: login.php'); exit; }

$judge_id = $_SESSION['judge_id'];

// Fetch all categories
$cats_res = $conn->query("SELECT * FROM categories ORDER BY created_at ASC");
$categories = [];
while ($c = $cats_res->fetch_assoc()) $categories[] = $c;

// Active category
$active_cat    = isset($_GET['cat']) ? (int)$_GET['cat'] : null;
$active_cat_name = '';
if ($active_cat) {
    foreach ($categories as $c) {
        if ($c['category_id'] == $active_cat) { $active_cat_name = $c['category_name']; break; }
    }
}

// Fetch criteria for selected category
$criteria_list = [];
if ($active_cat) {
    $stmt = $conn->prepare("SELECT criteria_id, criteria_name, percentage FROM criteria WHERE category_id = ? ORDER BY criteria_id");
    $stmt->bind_param('i', $active_cat);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) $criteria_list[] = $r;
    $stmt->close();
}

// Fetch contestants — only those assigned to the active category
$contestants = [];
if ($active_cat) {
    $stmt = $conn->prepare("SELECT contestant_id, full_name FROM contestants WHERE category_id = ? ORDER BY full_name");
    $stmt->bind_param('i', $active_cat);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) $contestants[] = $r;
    $stmt->close();
}

// Fetch already-saved scores for this judge + category
$saved_scores = [];
if ($active_cat && $judge_id) {
    $stmt = $conn->prepare("
        SELECT s.contestant_id, s.criteria_id, s.score
        FROM scores s
        JOIN criteria cr ON cr.criteria_id = s.criteria_id
        WHERE s.judge_id = ? AND cr.category_id = ?
    ");
    $stmt->bind_param('ii', $judge_id, $active_cat);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $saved_scores[$r['contestant_id']][$r['criteria_id']] = $r['score'];
    }
    $stmt->close();
}

$message = '';
if (isset($_GET['m']) && $_GET['m'] === 'success') {
    $message = "Scores submitted successfully!";
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Judge Scoring</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f0f2f5; font-family: 'Segoe UI', sans-serif; }

        /* Navbar */
        .navbar { background: #0f1f3d !important; height: 56px; }
        .navbar-brand { font-size: 15px; font-weight: 500; display: flex; align-items: center; gap: 10px; }
        .brand-icon { background: #3b6ef0; border-radius: 8px; width: 32px; height: 32px;
                      display: flex; align-items: center; justify-content: center; }
        .judge-avatar { width: 32px; height: 32px; border-radius: 50%; background: #3b4a6b;
                        color: #fff; display: flex; align-items: center; justify-content: center;
                        font-size: 12px; font-weight: 500; }
        .btn-logout { border: 1px solid rgba(255,255,255,0.3); background: transparent;
                      color: #fff; padding: 5px 14px; border-radius: 8px; font-size: 13px; }
        .btn-logout:hover { background: rgba(255,255,255,0.1); color: #fff; }

        /* Page */
        .page-wrap { max-width: 1200px; margin: 0 auto; padding: 32px 40px; }
        .greeting-label { font-size: 11px; letter-spacing: .08em; color: #5c7caa;
                          font-weight: 500; text-transform: uppercase; margin-bottom: 4px; }
        .greeting { font-size: 28px; font-weight: 400; color: #0f1f3d; margin-bottom: 28px; }
        .section-label { font-size: 11px; letter-spacing: .08em; color: #8a95a3;
                         text-transform: uppercase; font-weight: 500; margin-bottom: 12px; }

        /* Category Tabs */
        .cat-tabs {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 24px;
        }
        .cat-tab {
            padding: 9px 20px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            border: 1.5px solid #dce1ea;
            color: #4a5568;
            background: #fff;
            transition: all 0.18s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .cat-tab:hover { border-color: #3b6ef0; color: #3b6ef0; background: #f0f4ff; }
        .cat-tab.active {
            background: #0f1f3d;
            color: #fff;
            border-color: #0f1f3d;
        }
        .cat-tab .dot {
            width: 8px; height: 8px; border-radius: 50%;
            background: currentColor; opacity: 0.5;
        }

        /* No category selected */
        .pick-state {
            background: #fff;
            border-radius: 14px;
            border: 1px dashed #dce1ea;
            padding: 60px 40px;
            text-align: center;
            color: #8a95a3;
        }
        .pick-state svg { width: 52px; height: 52px; stroke: #dce1ea; margin-bottom: 16px; }
        .pick-state p { font-size: 15px; margin: 0; }

        /* Card */
        .score-card { background: #fff; border-radius: 14px;
                      border: 0.5px solid #e2e6ed; padding: 20px 24px; }

        /* Category badge in card */
        .cat-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #f0f4ff;
            color: #3b6ef0;
            border-radius: 8px;
            padding: 5px 12px;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 18px;
        }

        /* Table */
        .score-table { width: 100%; border-collapse: collapse; }
        .score-table th { font-size: 13px; font-weight: 500; color: #5c7caa;
                          padding: 10px 12px; border-bottom: 1px solid #eef0f4;
                          text-align: left; white-space: nowrap; }
        .score-table th small { display: block; color: #9ba8b8; font-size: 11px;
                                font-weight: 400; margin-top: 2px; }
        .score-table td { padding: 10px 12px; border-bottom: 0.5px solid #f0f2f5;
                          font-size: 14px; color: #1e2d45; vertical-align: middle; }
        .score-table tbody tr:last-child td { border-bottom: none; }
        .score-table tbody tr:hover td { background: #f8f9fb; }

        .score-input { width: 90px; border: 1px solid #dce1ea; border-radius: 8px;
                       padding: 6px 10px; font-size: 13px; color: #1e2d45; outline: none; }
        .score-input:focus { border-color: #3b6ef0;
                             box-shadow: 0 0 0 3px rgba(59,110,240,.08); }
        .score-input.prefilled { background: #f0f7ff; border-color: #b3ccf8; }

        /* Already scored badge */
        .scored-badge {
            display: inline-block;
            font-size: 11px;
            background: #e6f4ea;
            color: #2d6a4f;
            border-radius: 6px;
            padding: 2px 7px;
            margin-left: 6px;
            font-weight: 500;
        }

        /* Buttons */
        .btn-row { display: flex; gap: 12px; margin-top: 20px; }
        .btn-submit-scores { background: #0f1f3d; color: #fff; border: none;
                             border-radius: 10px; padding: 12px 28px; font-size: 14px;
                             font-weight: 500; flex: 1; cursor: pointer; }
        .btn-submit-scores:hover { background: #1a2f5a; }
        .btn-view-results { background: #fff; color: #3b6ef0;
                            border: 1.5px solid #3b6ef0; border-radius: 10px;
                            padding: 12px 28px; font-size: 14px; font-weight: 500;
                            flex: 1; text-align: center; text-decoration: none; }
        .btn-view-results:hover { background: #f0f4ff; color: #3b6ef0; }

        /* Alert */
        .alert-styled { background: #e6f4ea; border: 1px solid #b6dfc0; color: #2d6a4f;
                        border-radius: 10px; padding: 12px 16px; font-size: 14px;
                        text-align: center; margin-bottom: 20px; }

        /* Empty criteria */
        .no-criteria {
            text-align: center; padding: 40px; color: #8a95a3; font-size: 14px;
        }

        @media (max-width: 768px) {
            .page-wrap { padding: 20px 16px; }
            .score-input { width: 70px; }
            .btn-row { flex-direction: column; }
        }
    </style>
</head>
<body>

<nav class="navbar navbar-dark">
    <div class="container-fluid">
        <span class="navbar-brand">
            <div class="brand-icon">
                <svg width="16" height="16" fill="none" stroke="#fff" stroke-width="2"
                     viewBox="0 0 24 24"><path d="M5 8h14M5 12h14M5 16h14"/></svg>
            </div>
            Judge Panel
        </span>
        <div class="d-flex align-items-center gap-2">
            <span class="text-white" style="font-size:13px">
                <?= htmlspecialchars($_SESSION['judge_name']) ?>
            </span>
            <div class="judge-avatar">
                <?= strtoupper(substr($_SESSION['judge_name'], 0, 2)) ?>
            </div>
            <a href="../logout.php" class="btn-logout">Logout</a>
        </div>
    </div>
</nav>

<div class="page-wrap">
    <div class="greeting-label">Scoring Panel</div>
    <div class="greeting">Welcome back, <?= htmlspecialchars($_SESSION['judge_name']) ?></div>

    <?php if ($message): ?>
        <div class="alert-styled"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <!-- Category Tabs -->
    <div class="section-label">Select Category</div>
    <div class="cat-tabs">
        <?php if (empty($categories)): ?>
            <span style="font-size:14px; color:#8a95a3;">No categories available. Ask an admin to add categories.</span>
        <?php endif; ?>
        <?php foreach ($categories as $c): ?>
            <a href="?cat=<?= $c['category_id'] ?><?= isset($_GET['m']) ? '&m='.$_GET['m'] : '' ?>"
               class="cat-tab <?= $active_cat == $c['category_id'] ? 'active' : '' ?>">
                <span class="dot"></span>
                <?= htmlspecialchars($c['category_name']) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Score Entry -->
    <div class="section-label">Score Entry</div>

    <?php if (!$active_cat): ?>
        <!-- No category chosen yet -->
        <div class="pick-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                 stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="18" height="18" rx="2"/>
                <path d="M9 3v18M3 9h6M3 15h6"/>
            </svg>
            <p>Pick a category above to start scoring.</p>
        </div>

    <?php else: ?>
        <div class="score-card">
            <div class="cat-badge">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"
                     viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                </svg>
                <?= htmlspecialchars($active_cat_name) ?>
            </div>

            <?php if (empty($criteria_list)): ?>
                <div class="no-criteria">No criteria set for this category yet. Ask an admin to add criteria.</div>
            <?php elseif (empty($contestants)): ?>
                <div class="no-criteria">No contestants found.</div>
            <?php else: ?>
            <form method="post" action="submit_score.php?cat=<?= $active_cat ?>">
                <input type="hidden" name="judge_id" value="<?= htmlspecialchars($judge_id) ?>">
                <input type="hidden" name="category_id" value="<?= $active_cat ?>">

                <div style="overflow-x:auto">
                <table class="score-table">
                    <thead>
                        <tr>
                            <th>Contestant</th>
                            <?php foreach ($criteria_list as $c): ?>
                                <th>
                                    <?= htmlspecialchars($c['criteria_name']) ?>
                                    <small><?= number_format($c['percentage'], 2) ?>%</small>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($contestants as $cont):
                            // Check if this contestant is already fully scored in this category
                            $already_scored = isset($saved_scores[$cont['contestant_id']]) &&
                                count($saved_scores[$cont['contestant_id']]) === count($criteria_list);
                        ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($cont['full_name']) ?></strong>
                                    <?php if ($already_scored): ?>
                                        <span class="scored-badge">✓ Scored</span>
                                    <?php endif; ?>
                                </td>
                                <?php foreach ($criteria_list as $c):
                                    $existing = $saved_scores[$cont['contestant_id']][$c['criteria_id']] ?? '';
                                ?>
                                    <td>
                                        <input
                                            name="scores[<?= $cont['contestant_id'] ?>][<?= $c['criteria_id'] ?>]"
                                            type="number" step="0.01" min="0" max="100"
                                            class="score-input <?= $existing !== '' ? 'prefilled' : '' ?>"
                                            placeholder="0–100"
                                            value="<?= htmlspecialchars($existing) ?>"
                                            required>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>

                <div class="btn-row">
                    <button type="submit" class="btn-submit-scores">Submit scores for <?= htmlspecialchars($active_cat_name) ?></button>
                    <a href="../compute_results.php" class="btn-view-results">View final results →</a>
                </div>
            </form>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</div>

</body>
</html>