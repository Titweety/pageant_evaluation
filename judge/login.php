<?php
require_once __DIR__ . '/../db.php';
session_start();
if (isset($_SESSION['judge_id'])) { header('Location: dashboard.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $stmt = $conn->prepare("SELECT judge_id, password, full_name FROM judges WHERE username = ? LIMIT 1");
    $stmt->bind_param('s', $username);
    $stmt->execute(); $stmt->store_result();
    if ($stmt->num_rows === 1) {
        $stmt->bind_result($judge_id, $hash, $full_name);
        $stmt->fetch();
        if (password_verify($password, $hash)) {
            session_regenerate_id(true);
            $_SESSION['judge_id'] = $judge_id;
            $_SESSION['judge_name'] = $full_name;
            header('Location: dashboard.php'); exit;
        } else $error = 'Invalid credentials.';
    } else $error = 'Invalid credentials.';
    $stmt->close();
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Judge Login</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="bg-dark text-white d-flex align-items-center" style="height:100vh;"><div class="container"><div class="row justify-content-center"><div class="col-md-5"><div class="card"><div class="card-body"><h4>Judge Login</h4><?php if (!empty($error)) echo "<div class='alert alert-danger'>".htmlspecialchars($error)."</div>"; ?><form method="post"><input name="username" class="form-control mb-2" placeholder="Username" required><input type="password" name="password" class="form-control mb-3" placeholder="Password" required><button class="btn btn-success w-100">Login</button></form></div></div><p class="text-center mt-2"><a href="../index.php" class="text-white">Back to Home</a></p></div></div></div></body></html>
