<?php
// Public landing page
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Pageant System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-8 text-center">
      <h1 class="mb-3">Pageant Validation System</h1>
      <p class="lead">Admin and Judge portals manage contestants, criteria, scoring and compute results.</p>
      <div class="d-grid gap-2 d-sm-flex justify-content-sm-center">
        <a href="admin/login.php" class="btn btn-primary btn-lg">Admin Login</a>
        <a href="judge/login.php" class="btn btn-outline-success btn-lg">Judge Login</a>
        <a href="compute_results.php" class="btn btn-outline-secondary btn-lg">View / Compute Results</a>
      </div>
    </div>
  </div>
</div>
</body>
</html>
