<?php
require_once __DIR__ . '/../db.php';
session_start();

// Auto-create default admin if none exist
$res = $conn->query("SELECT COUNT(*) as cnt FROM admins");
$row = $res->fetch_assoc();
if ($row['cnt'] == 0) {
    $default_user = 'admin';
    $default_pass = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO admins (username, password, role) VALUES (?, ?, 'Coordinator')");
    $stmt->bind_param('ss', $default_user, $default_pass);
    $stmt->execute();
    $stmt->close();
    $created_msg = "Default admin created: admin / admin123";
}

if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php'); exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT admin_id, password FROM admins WHERE username = ? LIMIT 1");
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows === 1) {
        $stmt->bind_result($admin_id, $hash);
        $stmt->fetch();
        if (password_verify($password, $hash)) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = $admin_id;
            $_SESSION['admin_user'] = $username;
            header('Location: dashboard.php'); exit;
        } else {
            $error = 'Invalid credentials.';
        }
    } else {
        $error = 'Invalid credentials.';
    }
    $stmt->close();
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-dark text-white d-flex align-items-center" style="height:100vh;">
<div class="container">
  <div class="row justify-content-center">
    <div class="col-md-5">
      <div class="card shadow">
        <div class="card-body">
          <h4 class="card-title mb-3">Admin Login</h4>
          <?php if (!empty($created_msg)): ?><div class="alert alert-info"><?=htmlspecialchars($created_msg)?></div><?php endif; ?>
          <?php if (!empty($error)): ?><div class="alert alert-danger"><?=htmlspecialchars($error)?></div><?php endif; ?>
          <form method="post">
            <div class="mb-2"><input class="form-control" name="username" placeholder="Username" required></div>
            <div class="mb-3"><input type="password" class="form-control" name="password" placeholder="Password" required></div>
            <div class="d-grid"><button class="btn btn-primary">Login</button></div>
          </form>
        </div>
      </div>
      <p class="text-center text-muted mt-2"><a href="../index.php" class="text-white">Back to Home</a></p>
    </div>
  </div>
</div>
</body>
</html>
