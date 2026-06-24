<?php
require_once __DIR__ . '/../db.php';
session_start();
if (!isset($_SESSION['judge_id'])) { header('Location: login.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judge_id = (int)$_POST['judge_id'];
    foreach ($_POST['scores'] as $contestant_id => $criteria_scores) {
        foreach ($criteria_scores as $criteria_id => $score) {
            $score = floatval($score);
            // upsert via INSERT ... ON DUPLICATE KEY UPDATE requires unique index, which we have
            $stmt = $conn->prepare("INSERT INTO scores (judge_id, contestant_id, criteria_id, score) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE score = VALUES(score), created_at = CURRENT_TIMESTAMP");
            $stmt->bind_param('iiid', $judge_id, $contestant_id, $criteria_id, $score);
            $stmt->execute();
            $stmt->close();
        }
    }
    header('Location: dashboard.php?cat=' . (int)$_POST['category_id'] . '&m=success');
exit;
}
?>