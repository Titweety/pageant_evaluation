<?php
require_once 'db.php';

// compute totals and store into results table
// clear previous results
$conn->query("TRUNCATE TABLE results");

$sql = "INSERT INTO results (contestant_id, total_score)
SELECT s.contestant_id, ROUND(SUM(s.score * (c.percentage / 100)) / COUNT(DISTINCT s.judge_id), 2) AS total
FROM scores s
JOIN criteria c ON s.criteria_id = c.criteria_id
GROUP BY s.contestant_id
ORDER BY total DESC";

$conn->query($sql);

// assign ranks
$conn->query("SET @r := 0");
$conn->query("UPDATE results JOIN (SELECT result_id, @r := @r + 1 AS newr FROM results ORDER BY total_score DESC) t ON results.result_id = t.result_id SET results.rank = t.newr");

header('Location: results.php');
exit;
?>