<?php
session_start();
// STRICT SECURITY GATE: Only allow logged-in Admins to access this page
if (!isset($_SESSION['username']) || strtolower($_SESSION['role']) != 'admin') {
    header("Location: login.php");
    exit();
}

$host = 'localhost'; $db = 'njuguna_repair_dp_v2'; $user = 'root'; $pass = '';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (\PDOException $e) { die("Database connection failed: " . $e->getMessage()); }

$msg = "";

// Handle Form Submission securely
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register_tech'])) {
    $new_user = trim($_POST['new_username']);
    $new_pass = trim($_POST['new_password']);

    if (!empty($new_user) && !empty($new_pass)) {
        // Check if the username already exists to avoid duplication anomalies
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $checkStmt->execute([$new_user]);
        if ($checkStmt->fetchColumn() > 0) {
            $msg = "<div class='alert alert-danger py-2 small'>Error: Username '{$new_user}' is already taken!</div>";
        } else {
            // Insert the account with 'technician' hardcoded as the structural role parameter
            // Note: If your system uses hashing, you can use password_hash($new_pass, PASSWORD_DEFAULT) instead
            $insertStmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'technician')");
            $insertStmt->execute([$new_user, $new_pass]);
            
            $msg = "<div class='alert alert-success py-2 small'>Success: Technician account '<b>{$new_user}</b>' created successfully!</div>";
        }
    } else {
        $msg = "<div class='alert alert-warning py-2 small'>Please fill in all the required input fields.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Technician - Admin Control</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light" style="font-family: 'Segoe UI', sans-serif;">

<div class="container my-5" style="max-width: 500px;">
    <a href="admin_dashboard.php" class="btn btn-secondary btn-sm mb-3">← Back to Dashboard</a>
    
    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-header bg-dark text-white text-center py-3">
            <h5 class="mb-0 fw-bold">Register New Staff Technician</h5>
            <small class="text-muted text-light-50">Authorized Business Owner Panel Only</small>
        </div>
        <div class="card-body p-4 text-start">
            
            <?php if(!empty($msg)) echo $msg; ?>

            <form action="add_technician.php" method="POST">
                <div class="mb-3">
                    <label class="form-label small text-muted fw-semibold">Technician Username (Login ID)</label>
                    <input type="text" class="form-control" name="new_username" required placeholder="e.g. technician2">
                </div>
                <div class="mb-3">
                    <label class="form-label small text-muted fw-semibold">Temporary Secure Password</label>
                    <input type="password" class="form-control" name="new_password" required placeholder="••••••••">
                </div>
                <div class="mb-2">
                    <label class="form-label small text-muted fw-semibold">Account Workspace Assignment Role</label>
                    <input type="text" class="form-control bg-light text-muted small" value="technician" readonly>
                    <span class="text-muted d-block mt-1 style" style="font-size: 11px;">The role parameter is hardcoded to 'technician' automatically for standard system routing security.</span>
                </div>
                <hr class="my-4">
                <button type="submit" name="register_tech" class="btn btn-success w-100 py-2 fw-semibold">Create Technician Account</button>
            </form>
        </div>
    </div>
</div>

</body>
</html>