<?php
session_start();
require 'db.php';

// Security Check: Ensure user is logged in
if (!isset($_SESSION['role'])) {
    header('Location: login.php?error=Please log in first');
    exit;
}

$message = '';
$message_class = '';

// Handle Job-Card Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_job'])) {
    $customer_name       = trim($_POST['customer_name']);
    $customer_phone      = trim($_POST['customer_phone']);
    $device_model        = trim($_POST['device_model']);
    $problem_description = trim($_POST['problem_description']);
    $status              = $_POST['status'];

    if (!empty($customer_name) && !empty($customer_phone) && !empty($device_model) && !empty($problem_description)) {
        try {
            $insert = $pdo->prepare('INSERT INTO job_cards (customer_name, customer_phone, device_model, problem_description, status) VALUES (?, ?, ?, ?, ?)');
            $insert->execute([$customer_name, $customer_phone, $device_model, $problem_description, $status]);
            
            $message = "Digital Job-Card created and registered successfully!";
            $message_class = "alert-success";
        } catch (\PDOException $e) {
            $message = "Database Error: " . $e->getMessage();
            $message_class = "alert-danger";
        }
    } else {
        $message = "Please complete all fields before submitting.";
        $message_class = "alert-warning";
    }
}

// Fetch all active and past jobs from the ledger
$stmt = $pdo->query('SELECT * FROM job_cards ORDER BY created_at DESC');
$jobs = $stmt->fetchAll();
$total_jobs = count($jobs);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Njuguna Electronics - Job-Card Engine</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <!-- Navigation Header -->
    <nav class="navbar navbar-dark bg-dark">
        <div class="container-fluid">
            <span class="navbar-brand mb-0 h1">Digital Job-Card Intake Engine</span>
            <a href="admin_dashboard.php" class="btn btn-sm btn-outline-light">Back to Admin Panel</a>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <!-- Job Intake Registration Form -->
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-primary text-white fw-bold">Intake New Broken Device</div>
                    <div class="card-body">
                        
                        <?php if (!empty($message)): ?>
                            <div class="alert <?php echo $message_class; ?> p-2 text-center" style="font-size: 14px;">
                                <?php echo htmlspecialchars($message); ?>
                            </div>
                        <?php endif; ?>

                        <form action="create_jobcard.php" method="POST">
                            <input type="hidden" name="register_job" value="1">
                            <div class="mb-3">
                                <label class="form-label">Customer Name</label>
                                <input type="text" name="customer_name" class="form-control" placeholder="e.g. John Doe" required autocomplete="off">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Customer Phone Number</label>
                                <input type="text" name="customer_phone" class="form-control" placeholder="e.g. 0712345678" required autocomplete="off">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Device Model / Brand</label>
                                <input type="text" name="device_model" class="form-control" placeholder="e.g. iPhone 13 Pro Max" required autocomplete="off">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Fault / Problem Description</label>
                                <textarea name="problem_description" class="form-control" rows="3" placeholder="e.g. Cracked screen, needs display replacement" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Initial Status Allocation</label>
                                <select name="status" class="form-select">
                                    <option value="Pending">Pending (Awaiting Diagnosis)</option>
                                    <option value="In Progress">In Progress (Under Repair)</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Register & Print Job-Card</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Live Job Tracking Ledger -->
            <div class="col-md-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-secondary text-white fw-bold">Active Repair Tracking Registry</div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped mb-0 align-middle">
                                <thead class="table-dark">
                                    <tr>
                                        <th class="ps-3">Job ID</th>
                                        <th>Customer Details</th>
                                        <th>Device Model</th>
                                        <th>Reported Problem</th>
                                        <th>Status</th>
                                        <th class="pe-3">Registered At</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($total_jobs === 0): ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">No devices currently booked in the tracking registry.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($jobs as $job): ?>
                                            <tr>
                                                <td class="ps-3"><strong>#JC-<?php echo str_pad($job['job_id'], 4, '0', STR_PAD_LEFT); ?></strong></td>
                                                <td>
                                                    <span class="fw-bold"><?php echo htmlspecialchars($job['customer_name']); ?></span><br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($job['customer_phone']); ?></small>
                                                </td>
                                                <td><?php echo htmlspecialchars($job['device_model']); ?></td>
                                                <td><small class="text-wrap d-inline-block" style="max-width: 200px;"><?php echo htmlspecialchars($job['problem_description']); ?></small></td>
                                                <td>
                                                    <?php 
                                                        $badge_class = 'bg-danger';
                                                        if ($job['status'] === 'In Progress') $badge_class = 'bg-warning text-dark';
                                                        if ($job['status'] === 'Completed') $badge_class = 'bg-info text-dark';
                                                        if ($job['status'] === 'Delivered') $badge_class = 'bg-success';
                                                    ?>
                                                    <span class="badge <?php echo $badge_class; ?>">
                                                        <?php echo htmlspecialchars($job['status']); ?>
                                                    </span>
                                                </td>
                                                <td class="pe-3 text-muted" style="font-size: 13px;"><?php echo htmlspecialchars($job['created_at']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>