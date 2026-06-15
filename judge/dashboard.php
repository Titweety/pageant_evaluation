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

// Check for success message in the query string
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
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-success">
    <div class="container-fluid">
        <span class="navbar-brand">Judge Panel</span>
        <div class="d-flex">
            <span class="text-white me-3">Hello, <?=htmlspecialchars($_SESSION['judge_name'])?></span>
            <a href="../logout.php" class="btn btn-outline-light btn-sm">Logout</a>
        </div>
    </div>
</nav>
<div class="container py-4">
    <!-- Display success message if available -->
    <?php if ($message): ?>
        <div class="alert alert-success text-center">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <form method="post" action="submit_score.php">
        <input type="hidden" name="judge_id" value="<?=htmlspecialchars($judge_id)?>">
        <table class="table table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>Contestant</th>
                    <?php foreach($criteria_list as $c): ?>
                        <th><?=htmlspecialchars($c['criteria_name'])?><br><small><?=htmlspecialchars($c['percentage'])?>%</small></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php while ($cont = $contestants->fetch_assoc()): ?>
                    <tr>
                        <td><?=htmlspecialchars($cont['full_name'])?></td>
                        <?php foreach ($criteria_list as $c): ?>
                            <td>
                                <input name="scores[<?=$cont['contestant_id']?>][<?=$c['criteria_id']?>]" 
                                       type="number" step="0.01" min="0" max="100" 
                                       class="form-control" required>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <div class="d-grid">
            <button class="btn btn-success">Submit All Scores</button>
        </div>
        <div class="d-grid">
            <a href="../compute_results.php" class="btn btn-primary">View Final Results</a>
        </div>
    </form>
</div>
</body>
</html>