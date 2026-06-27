<?php
session_start();
// 1. Force this page to utilize our working secure database pipeline
require_once 'db.php';

$error_msg = isset($_SESSION['error']) ? $_SESSION['error'] : "";
unset($_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Njuguna Electronics - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #121212; color: white; height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-card { background-color: #1e1e1e; border: 1px solid #333; border-radius: 12px; padding: 40px; width: 100%; max-width: 420px; }
        .form-control { background-color: #2d2d2d; border: 1px solid #444; color: white; }
        .btn-primary { background-color: #0d6efd; border: none; font-weight: 600; }
    </style>
</head>
<body>
<div class="login-card">
    <div class="text-center mb-4">
        <h2 style="color: #0d6efd; font-weight:700;">NJUGUNA ELECTRONICS</h2>
        <p class="text-muted small">System Authorization Gate</p>
    </div>

    <?php if(!empty($error_msg)): ?>
        <div class="alert alert-danger py-2 text-center small"><?php echo $error_msg; ?></div>
    <?php endif; ?>

    <form action="login_action.php" method="POST">
        <div class="mb-3">
            <label class="form-label small text-muted">Username</label>
            <input type="text" class="form-control" name="username" required autocomplete="off">
        </div>
        <div class="mb-4">
            <label class="form-label small text-muted">Password</label>
            <input type="password" class="form-control" name="password" required>
        </div>
        <div class="d-grid">
            <button type="submit" class="btn btn-primary py-2">Secure Authorization Login</button>
        </div>
    </form>
</div>
</body>
</html>
