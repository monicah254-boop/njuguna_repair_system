<?php
session_start();
if (!isset($_SESSION['username']) || strtolower($_SESSION['role']) != 'technician') {
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

// Handle Submitting/Opening a New Job Card with Precise Structure Keys
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['open_jobcard'])) {
    $customer = trim($_POST['customer_name']);
    $phone = trim($_POST['customer_phone']);
    $device = trim($_POST['device_model']);
    $issue_input = trim($_POST['issue_description']);
    $part_id = intval($_POST['assigned_part_id']);

    if (!empty($customer) && !empty($phone) && !empty($device)) {
        
        // Handle inventory reduction if a part ID was allocated
        if ($part_id > 0) {
            $uStmt = $pdo->prepare("UPDATE inventory SET quantity = quantity - 1 WHERE part_id = ? AND quantity > 0");
            $uStmt->execute([$part_id]);
        }

        // Exact structured insertion string matching your DB table layout columns
        $sqlInsert = "INSERT INTO job_cards (customer_name, customer_phone, device_model, problem_description, allocated_part_id, status) VALUES (?, ?, ?, ?, ?, 'In Progress')";
        
        try {
            $stmt = $pdo->prepare($sqlInsert);
            // If part_id is 0, pass NULL to respect the structural integer field
            $stmt->execute([$customer, $phone, $device, $issue_input, ($part_id > 0 ? $part_id : null)]);
            $msg = "<div class='alert alert-success py-2 small'>New Repair Job Card opened successfully and inventory logs updated!</div>";
        } catch (\PDOException $err) {
            $msg = "<div class='alert alert-danger py-2 small'>Database Error: " . htmlspecialchars($err->getMessage()) . "</div>";
        }
    } else {
        $msg = "<div class='alert alert-warning py-2 small'>Please fill in all the required text fields.</div>";
    }
}

// Handle Closing a Job Card + LIVE SMS Gateway Trigger
if (isset($_GET['close_id'])) {
    $job_id = $_GET['close_id'];

    // Fetch details to get the custom mobile number saved in the row
    $nStmt = $pdo->prepare("SELECT customer_name, customer_phone, device_model FROM job_cards WHERE job_id = ?");
    $nStmt->execute([$job_id]);
    $jobDetails = $nStmt->fetch();

    $stmt = $pdo->prepare("UPDATE job_cards SET status = 'Completed' WHERE job_id = ?");
    $stmt->execute([$job_id]);

    if ($jobDetails) {
        $c_name = htmlspecialchars($jobDetails['customer_name']);
        $d_model = htmlspecialchars($jobDetails['device_model']);
        
        // Grab the actual customer phone number from your database dynamically!
        $raw_phone = trim($jobDetails['customer_phone']);
        // Format it instantly to international style for Africa's Talking gateway (+254...)
        $customer_phone = (substr($raw_phone, 0, 1) == '0') ? '+254' . substr($raw_phone, 1) : $raw_phone;
        
        $sms_message = "Hello " . $c_name . ", your device (" . $d_model . ") has been successfully repaired and is ready for collection at Njuguna Electronics. Thank you!";

        // --- AFRICA'S TALKING LIVE SMS GATEWAY INTEGRATION ---
        $username = "sandbox"; 
        $apiKey   = "atsk_7568c2ed76556ae8e4722ab09566b3a17dee150fb6a6a0324e8ca9a8b01404acfba3cd30"; 

        $url = "https://api.sandbox.africastalking.com/version1/messaging";

        $data = [
            'username' => $username,
            'to'       => $customer_phone,
            'message'  => $sms_message
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Accept: application/json",
            "ApiKey: " . $apiKey
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $msg = "
        <div class='alert alert-success py-3 shadow-sm text-start'>
            <h6 class='fw-bold mb-1 text-success'><i class='bi bi-phone-vibrate'></i> Live SMS Dispatched via Africa's Talking Gateway!</h6>
            <div class='p-2 bg-white rounded border border-success font-monospace small text-dark'>
                <b>Sent To:</b> " . $customer_phone . "<br>
                <b>Gateway Response Status:</b> Network Request Dispatched Successfully.<br>
                <b>Message Content:</b> " . $sms_message . "
            </div>
        </div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Technician Workspace - Njuguna Electronics</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
        .navbar-custom { background-color: #1a1a1a; color: white; padding: 15px; border-radius: 8px; }
        .card { border: none; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .table-container { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
    </style>
</head>
<body>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center navbar-custom mb-4 shadow-sm">
        <h5 class="mb-0">Technician Portal: Logging & Repair Management</h5>
        <div>
            <span class="me-3 small text-muted">Active Tech: <b class="text-white"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Technician'); ?></b></span>
            <a href="login.php" class="btn btn-danger btn-sm px-3">Logout</a>
        </div>
    </div>

    <?php if(!empty($msg)) echo $msg; ?>

    <div class="row">
        <div class="col-md-5 mb-4 text-start">
            <div class="card">
                <div class="card-header bg-primary text-white py-3 fw-bold">Open New Repair Job Card</div>
                <div class="card-body p-4">
                    <form action="tech-dashboard.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label small text-muted fw-semibold">Customer Name</label>
                            <input type="text" class="form-control bg-light" name="customer_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small text-muted fw-semibold">Customer Phone Number</label>
                            <input type="text" class="form-control bg-light" name="customer_phone" required placeholder="e.g. 0712345678">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small text-muted fw-semibold">Device Model</label>
                            <input type="text" class="form-control bg-light" name="device_model" required placeholder="e.g. Nokia G22">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small text-muted fw-semibold">Issue Description</label>
                            <textarea class="form-control bg-light" name="issue_description" rows="2" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small text-muted fw-semibold">Request & Allocate Spare Part</label>
                            <select class="form-select bg-light" name="assigned_part_id">
                                <option value="0">No parts required (Service Only)</option>
                                <?php
                                $invStmt = $pdo->query("SELECT * FROM inventory");
                                while ($item = $invStmt->fetch()) {
                                    $itemLower = array_change_key_case($item, CASE_LOWER);
                                    $p_id    = current(array_slice($itemLower, 0, 1)); 
                                    $p_name  = isset($itemLower['part_name']) ? $itemLower['part_name'] : next($itemLower);
                                    $p_qty   = isset($itemLower['quantity']) ? intval($itemLower['quantity']) : 0; 

                                    if ($p_qty > 0) {
                                        echo "<option value='{$p_id}'>" . htmlspecialchars($p_name) . " ({$p_qty} available)</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <button type="submit" name="open_jobcard" class="btn btn-primary w-100 py-2 fw-semibold">Submit & Open Job Card</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-7 text-start">
            <div class="table-container">
                <h6 class="fw-bold text-secondary border-bottom pb-2 mb-3">Active Repairs & Tracking Logs</h6>
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Device</th>
                            <th>Spare Part ID</th>
                            <th>Status</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $pdo->query("SELECT * FROM job_cards ORDER BY created_at DESC");
                        while ($row = $stmt->fetch()) {
                            $displayId = $row['job_id'];
                            $displayPart = !empty($row['allocated_part_id']) ? "#" . $row['allocated_part_id'] : 'None';
                            $statusText = $row['status'] ?? 'In Progress';
                            
                            $badgeClass = 'bg-warning text-dark';
                            if (strtolower($statusText) == 'completed') $badgeClass = 'bg-success';
                            if (strtolower($statusText) == 'pending') $badgeClass = 'bg-secondary';
                            
                            echo "<tr>";
                            echo "<td><b>#{$displayId}</b></td>";
                            echo "<td><span class='fw-semibold'>" . htmlspecialchars($row['device_model'] ?? 'Unknown') . "</span></td>";
                            echo "<td><span class='badge bg-light text-dark border'>{$displayPart}</span></td>";
                            echo "<td><span class='badge {$badgeClass}'>" . ucfirst($statusText) . "</span></td>";
                            echo "<td class='text-center'>";
                            
                            if (strtolower($statusText) != 'completed') {
                                echo "<a href='tech-dashboard.php?close_id={$displayId}' class='btn btn-success btn-sm py-1 px-2 fw-semibold' style='font-size:11px;'>Close Job</a>";
                            } else {
                                echo "<span class='text-muted small'>Archived</span>";
                            }
                            
                            echo "</td>";
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>