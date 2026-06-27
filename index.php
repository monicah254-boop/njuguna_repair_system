<?php
// 1. Import central cloud database handler
require_once 'db.php';

$jobs = []; $search_phone = ""; $error = ""; $success_msg = "";

// 2. Handle Customer Upsell / Extra Part Change Requests
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_request'])) {
    $job_id = intval($_POST['target_job_id']);
    $additional_request = trim($_POST['additional_request']);
    $search_phone = trim($_POST['customer_phone_fallback']);
    
    if ($job_id > 0 && !empty($additional_request)) {
        try {
            $stmtFetch = $pdo->prepare("SELECT problem_description FROM job_cards WHERE job_id = ?");
            $stmtFetch->execute([$job_id]);
            $current_issue = $stmtFetch->fetchColumn();
            
            $updated_issue = $current_issue . " | [CUSTOMER UPDATE]: " . $additional_request;
            
            $stmtUpdate = $pdo->prepare("UPDATE job_cards SET problem_description = ? WHERE job_id = ?");
            $stmtUpdate->execute([$updated_issue, $job_id]);
            
            $success_msg = "Your request has been sent straight to the technician's bench dashboard!";
        } catch (\PDOException $e) {
            $error = "Failed to update request: " . $e->getMessage();
        }
    }
}

// 3. Handle Tracking Lookup via Phone Number
if (isset($_GET['customer_phone'])) {
    $search_phone = trim($_GET['customer_phone']);
    if (!empty($search_phone)) {
        try {
            $stmt = $pdo->prepare("SELECT job_id, customer_name, device_model, problem_description, status, created_at FROM job_cards WHERE customer_phone = ?");
            $stmt->execute([$search_phone]);
            $jobs = $stmt->fetchAll();
            
            if (empty($jobs)) {
                $error = "No active repair records found for phone number: " . htmlspecialchars($search_phone);
            }
        } catch (\PDOException $e) {
            $error = "Search error: " . $e->getMessage();
        }
    } else {
        $error = "Please enter your registered phone number.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Njuguna Electronics - Smart Repair Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
        .hero-section { background: linear-gradient(135deg, #0d6efd 0%, #0043a8 100%); color: white; padding: 40px 20px; border-radius: 0 0 24px 24px; }
        .card { border: none; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
    </style>
</head>
<body>

<div class="hero-section text-center shadow-sm mb-4">
    <div class="container" style="max-width: 650px;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold mb-0"><i class="bi bi-cpu"></i> Njuguna Electronics</h4>
            <a href="login.php" class="btn btn-light btn-sm fw-semibold text-primary px-3"><i class="bi bi-shield-lock"></i> Staff Login</a>
        </div>
        <h2 class="fw-bold mt-4">Track Your Device Repair</h2>
        <p class="opacity-75 small">Enter your phone number below to view workshop progress or request component upgrades in real time.</p>
    </div>
</div>

<div class="container" style="max-width: 650px;">
    <div class="card p-4 text-start mb-4">
        <form action="index.php" method="GET">
            <label class="form-label fw-semibold text-secondary small">Registered Customer Phone Number</label>
            <div class="input-group">
                <span class="input-group-text bg-light text-muted"><i class="bi bi-telephone"></i></span>
                <input type="text" name="customer_phone" class="form-control form-control-lg" placeholder="e.g. 0712345678" value="<?php echo htmlspecialchars($search_phone); ?>">
                <button type="submit" class="btn btn-primary px-4 fw-bold">Track Status</button>
            </div>
        </form>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger mt-3 py-2 small"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success_msg)): ?>
        <div class="alert alert-success mt-3 py-2 small"><i class="bi bi-check-circle-fill"></i> <?php echo $success_msg; ?></div>
    <?php endif; ?>

    <?php if (!empty($jobs)): ?>
        <?php foreach ($jobs as $job): ?>
            <?php 
                $status = strtolower($job['status'] ?? 'in progress');
                $step = 1;
                if ($status == 'in progress' || $status == 'active') { $step = 2; }
                if ($status == 'completed') { $step = 3; }
            ?>
            <div class="card p-4 text-start mb-4 shadow-sm">
                <div class="d-flex justify-content-between align-items-center border-bottom pb-2">
                    <span class="badge bg-secondary">Job #<?php echo $job['job_id']; ?></span>
                    <span class="text-muted small"><?php echo htmlspecialchars($job['device_model']); ?></span>
                </div>
                
                <p class="small text-muted mb-4"><b>Current Diagnosis:</b> <?php echo htmlspecialchars($job['problem_description']); ?></p>
                
                <div class="position-relative mb-5 mt-4 mx-2">
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo ($step == 1 ? '0%' : ($step == 2 ? '50%' : '100%')); ?>"></div>
                    </div>
                    
                    <div class="position-absolute top-50 translate-middle text-center" style="left: 0%;">
                        <span class="badge rounded-circle p-2 <?php echo $step >= 1 ? 'bg-success' : 'bg-secondary'; ?>"></span>
                        <div class="small fw-semibold mt-1" style="font-size:11px;">Received</div>
                    </div>
                    
                    <div class="position-absolute top-50 translate-middle text-center" style="left: 50%;">
                        <span class="badge rounded-circle p-2 <?php echo $step >= 2 ? 'bg-success' : 'bg-secondary'; ?>"></span>
                        <div class="small fw-semibold mt-1" style="font-size:11px;">In Repair</div>
                    </div>
                    
                    <div class="position-absolute top-50 translate-middle text-center" style="left: 100%;">
                        <span class="badge rounded-circle p-2 <?php echo $step == 3 ? 'bg-success' : 'bg-secondary'; ?>"></span>
                        <div class="small fw-semibold mt-1" style="font-size:11px;">Ready for Pickup</div>
                    </div>
                </div>

                <?php if ($status != 'completed'): ?>
                    <div class="bg-light p-3 rounded-3 border border-warning-subtle mt-2">
                        <h6 class="fw-bold text-dark small mb-1"><i class="bi bi-plus-circle"></i> Request Additional Part Replacement</h6>
                        <p class="text-muted mb-2" style="font-size: 12px;">Need something else changed while your phone is disassembled?</p>
                        
                        <form action="index.php" method="POST">
                            <input type="hidden" name="target_job_id" value="<?php echo $job['job_id']; ?>">
                            <input type="hidden" name="customer_phone_fallback" value="<?php echo htmlspecialchars($search_phone); ?>">
                            
                            <div class="input-group">
                                <input type="text" name="additional_request" class="form-control form-control-sm" placeholder="e.g. Please replace battery too...">
                                <button type="submit" name="submit_request" class="btn btn-success btn-sm fw-bold">Update Request</button>
                            </div>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="alert alert-success py-2 small mb-0 mt-2"><i class="bi bi-bag-check-fill"></i> Repair Complete! Please visit our workshop.</div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

</body>
</html>
