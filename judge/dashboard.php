<?php
require_once __DIR__ . '/../db.php';
session_start();
if (!isset($_SESSION['judge_id'])) { header('Location: login.php'); exit; }

$judge_id = $_SESSION['judge_id'];
$contestants = $conn->query("SELECT contestant_id, full_name FROM contestants ORDER BY full_name");
$criteria = $conn->query("SELECT criteria_id, criteria_name, percentage FROM criteria ORDER BY criteria_id");
$criteria_list = [];
while ($r = $criteria->fetch_assoc()) { $criteria_list[] = $r; }
$criteria = null;

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
        .page-wrap { max-width: 1100px; margin: 0 auto; padding: 32px 40px; }
        .greeting-label { font-size: 11px; letter-spacing: .08em; color: #5c7caa;
                          font-weight: 500; text-transform: uppercase; margin-bottom: 4px; }
        .greeting { font-size: 28px; font-weight: 400; color: #0f1f3d; margin-bottom: 28px; }
        .section-label { font-size: 11px; letter-spacing: .08em; color: #8a95a3;
                         text-transform: uppercase; font-weight: 500; margin-bottom: 12px; }

        /* Card */
        .score-card { background: #fff; border-radius: 14px;
                      border: 0.5px solid #e2e6ed; padding: 20px 24px; }

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

    <div class="section-label">Score Entry</div>
    <div class="score-card">
        <form method="post" action="submit_score.php">
            <input type="hidden" name="judge_id" value="<?= htmlspecialchars($judge_id) ?>">
            <table class="score-table">
                <thead>
                    <tr>
                        <th>Contestant</th>
                        <?php foreach ($criteria_list as $c): ?>
                            <th>
                                <?= htmlspecialchars($c['criteria_name']) ?>
                                <small><?= htmlspecialchars($c['percentage']) ?>%</small>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($cont = $contestants->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($cont['full_name']) ?></strong></td>
                            <?php foreach ($criteria_list as $c): ?>
                                <td>
                                    <input
                                        name="scores[<?= $cont['contestant_id'] ?>][<?= $c['criteria_id'] ?>]"
                                        type="number" step="0.01" min="0" max="100"
                                        class="score-input" placeholder="0–100" required>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <div class="btn-row">
                <button type="submit" class="btn-submit-scores">Submit all scores</button>
                <a href="../compute_results.php" class="btn-view-results">View final results →</a>
            </div>
        </form>
    </div>
</div>

</body>
</html>