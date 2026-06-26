<?php
// 1. Import our working central database file
require_once 'db.php';

// 2. Handle Customer Upsell / Extra Part Change Requests
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_request'])) {
    $job_id = intval($_POST['target_job_id']);
    $additional_request = trim($_POST['additional_request']);
    $search_phone = trim($_POST['customer_phone_fallback']);
    
    if ($job_id > 0 && !empty($additional_request)) {
        try {
            $stmt = $pdo->prepare("SELECT problem_description FROM job_cards WHERE id = :job_id");
            $stmt->execute([':job_id' => $job_id]);
            $current_desc = $stmt->fetchColumn();
            
            if ($current_desc !== false) {
                $new_desc = $current_desc . "\n[Upsell Request]: " . $additional_request;
                $update = $pdo->prepare("UPDATE job_cards SET problem_description = :new_desc WHERE id = :job_id");
                $update->execute([':new_desc' => $new_desc, ':job_id' => $job_id]);
                
                header("Location: index.php?status=success");
                exit;
            } else {
                header("Location: index.php?status=not_found");
                exit;
            }
        } catch (\PDOException $e) {
            die("Query execution error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Njuguna Electronics Repair System - Upsell Interface</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #121212; color: #ffffff; padding: 40px; }
        .card { background-color: #1e1e1e; border: 1px solid #333; padding: 25px; border-radius: 8px; max-width: 500px; margin: 0 auto; }
        h2 { color: #4caf50; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; color: #bbb; }
        input, textarea { width: 100%; padding: 10px; background-color: #2b2b2b; border: 1px solid #444; color: #fff; border-radius: 4px; box-sizing: border-box; }
        button { background-color: #4caf50; color: white; border: none; padding: 12px 20px; border-radius: 4px; cursor: pointer; width: 100%; font-size: 16px; }
        button:hover { background-color: #45a049; }
        .alert { padding: 10px; margin-bottom: 15px; border-radius: 4px; text-align: center; }
        .success { background-color: #1b5e20; color: #c8e6c9; }
        .error { background-color: #b71c1c; color: #ffcdd2; }
    </style>
</head>
<body>

<div class="card">
    <h2>Customer Request Dashboard</h2>
    <p>Log special technician instructions or part adjustments here.</p>
    
    <?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
        <div class="alert success">Additional request updated successfully!</div>
    <?php elseif (isset($_GET['status']) && $_GET['status'] == 'not_found'): ?>
        <div class="alert error">Job card target ID not found.</div>
    <?php endif; ?>

    <form action="index.php" method="POST">
        <div class="form-group">
            <label for="target_job_id">Target Job Card ID:</label>
            <input type="number" name="target_job_id" id="target_job_id" required placeholder="e.g. 104">
        </div>
        
        <div class="form-group">
            <label for="additional_request">Upsell / Additional Requirements:</label>
            <textarea name="additional_request" id="additional_request" rows="4" required placeholder="Describe parts or requests..."></textarea>
        </div>
        
        <div class="form-group">
            <label for="customer_phone_fallback">Customer Phone (Fallback Verification):</label>
            <input type="text" name="customer_phone_fallback" id="customer_phone_fallback" placeholder="Optional reference phone">
        </div>
        
        <button type="submit" name="submit_request">Submit Additional Request</button>
    </form>
</div>

</body>
</html>
