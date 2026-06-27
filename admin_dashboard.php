<?php
session_start();

// 1. Hook into our working cloud database connection pipeline
require_once 'db.php';

// 2. Enforce strict role validation based on your system login setup
if (!isset($_SESSION['username']) || strtolower($_SESSION['role']) != 'admin') {
    header("Location: login.php");
    exit();
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

try {
    // Executive Metrics
    $totalJobs = $pdo->query("SELECT COUNT(*) FROM job_cards")->fetchColumn();
    $activeCount = $pdo->query("SELECT COUNT(*) FROM job_cards WHERE status='Active' OR status='In Progress'")->fetchColumn();
    $completedCount = $pdo->query("SELECT COUNT(*) FROM job_cards WHERE status='Completed'")->fetchColumn();

    // --- FEATURE 4: BUSINESS INTELLIGENCE PREDICTIVE INVENTORY AUDIT ---
    $lowStockAlerts = [];
    $invQuery = $pdo->query("SELECT * FROM inventory");
    while ($item = $invQuery->fetch()) {
        $itemLower = array_change_key_case($item, CASE_LOWER);
        $qty = isset($itemLower['quantity']) ? intval($itemLower['quantity']) : 0;
        $name = isset($itemLower['part_name']) ? $itemLower['part_name'] : 'Component';
        
        // Predictive Engine Check: Trigger warning flag if quantity falls beneath safety barrier (3 units)
        if ($qty <= 3) {
            $lowStockAlerts[] = [
                'name' => $name,
                'qty' => $qty,
                'status' => ($qty === 0) ? 'CRITICAL CRISIS (0 Left)' : 'Predictive Risk (Low Stock)'
            ];
        }
    }

    // --- FAIL-SAFE ADAPTIVE COLUMN DETECTION FOR LEADERBOARD ---
    $testStmt = $pdo->query("SELECT * FROM job_cards LIMIT 1");
    $rowSample = $testStmt->fetch();
    $techKey = "";
    if ($rowSample) {
        if (array_key_exists('technician_assigned', $rowSample)) $techKey = 'technician_assigned';
        elseif (array_key_exists('technician', $rowSample)) $techKey = 'technician';
        elseif (array_key_exists('assigned_to', $rowSample)) $techKey = 'assigned_to';
    }

    if (!empty($techKey)) {
        $techLogs = $pdo->query("SELECT $techKey as technician_assigned, COUNT(*) as total_done FROM job_cards WHERE status='Completed' GROUP BY $techKey ORDER BY total_done DESC");
    } else {
        $techLogs = $pdo->query("SELECT 'Staff' as technician_assigned, COUNT(*) as total_done FROM job_cards WHERE status='Completed' LIMIT 0");
    }
} catch (\PDOException $e) {
    // Fail gracefully if tables like inventory haven't been created/seeded yet
    $totalJobs = $totalJobs ?? 0;
    $activeCount = $activeCount ?? 0;
    $completedCount = $completedCount ?? 0;
    $lowStockAlerts = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Business Owner Dashboard - Njuguna Electronics</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .navbar-custom { background-color: #0d6efd; color: white; padding: 15px; border-radius: 8px; }
        .metric-card { border: none; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .table-container { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
    </style>
</head>
<body>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center navbar-custom mb-4 shadow-sm">
        <h5 class="mb-0 fw-bold">Business Owner Management Dashboard</h5>
        <div>
            <a href="add_technician.php" class="btn btn-light btn-sm fw-semibold text-dark px-3 me-2">Add New Technician</a>
            <a href="manage_stock.php" class="btn btn-light btn-sm fw-semibold text-dark px-3 me-2">Manage Stock Inventory</a>
            <a href="login.php" class="btn btn-light btn-sm fw-semibold text-primary px-3">Logout</a>
        </div>
    </div>

    <?php if(!empty($lowStockAlerts)): ?>
        <div class="alert alert-danger shadow-sm py-3 text-start mb-4">
            <h6 class="fw-bold mb-2"><i class="bi bi-graph-down-arrow"></i> Predictive Business Intelligence: Procurement Alerts</h6>
            <ul class="mb-0 small ps-3">
                <?php foreach($lowStockAlerts as $alert): ?>
                    <li>Hardware Component <b><?php echo htmlspecialchars($alert['name']); ?></b> is resting at <b><?php echo $alert['qty']; ?> units</b>. Action: Restock immediately to avoid customer pipeline stalling.</li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="row mb-4 g-3">
        <div class="col-md-4">
            <div class="card metric-card bg-info text-white text-center p-4">
                <h5>Total Job Cards Opened</h5>
                <h1 class="display-5 fw-bold mb-0"><?php echo $totalJobs; ?></h1>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card metric-card bg-warning text-dark text-center p-4">
                <h5>Active Under Repair</h5>
                <h1 class="display-5 fw-bold mb-0"><?php echo $activeCount; ?></h1>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card metric-card bg-success text-white text-center p-4">
                <h5>Completed & Verified</h5>
                <h1 class="display-5 fw-bold mb-0"><?php echo $completedCount; ?></h1>
            </div>
        </div>
    </div>

    <div class="row text-start">
        <div class="col-md-4 mb-4">
            <div class="table-container h-100">
                <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">Tech Performance Ranking</h6>
                <ul class="list-group list-group-flush small">
                    <?php
                    $rank = 1;
                    if (isset($techLogs) && $techLogs) {
                        while ($tech = $techLogs->fetch()) {
                            $name = !empty($tech['technician_assigned']) ? htmlspecialchars($tech['technician_assigned']) : 'Unassigned';
                            echo "<li class='list-group-item d-flex justify-content-between align-items-center px-0'>";
                            echo "<div><span class='badge bg-dark me-2'>#{$rank}</span> <b>{$name}</b></div>";
                            echo "<span class='badge bg-success rounded-pill'>{$tech['total_done']} Solved</span>";
                            echo "</li>";
                            $rank++;
                        }
                    }
                    if ($rank == 1) { echo "<p class='text-muted small my-2'>No active metrics found.</p>"; }
                    ?>
                </ul>
            </div>
        </div>

        <div class="col-md-8">
            <div class="table-container">
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-3 border-bottom pb-3">
                    <h5 class="fw-bold text-dark mb-2 mb-sm-0">Complete System Audit Trail</h5>
                    <form action="admin_dashboard.php" method="GET" class="d-flex gap-2">
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="Search customer or device..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                        <?php if(!empty($search)): ?>
                            <a href="admin_dashboard.php" class="btn btn-secondary btn-sm">Clear</a>
                        <?php endif; ?>
                    </form>
                </div>
                
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-dark">
                        <tr>
                            <th>Job ID</th>
                            <th>Customer Name</th>
                            <th>Device Model</th>
                            <th>Allocated Part ID</th>
                            <th>Handled By</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            if (!empty($search)) {
                                $stmt = $pdo->prepare("SELECT * FROM job_cards WHERE customer_name LIKE ? OR device_model LIKE ? ORDER BY created_at DESC");
                                $stmt->execute(["%$search%", "%$search%"]);
                            } else {
                                $stmt = $pdo->query("SELECT * FROM job_cards ORDER BY created_at DESC");
                            }
                            
                            while ($row = $stmt->fetch()) {
                                $rowLower = array_change_key_case($row, CASE_LOWER);
                                $displayId = $rowLower['job_id'];
                                $displayPart = !empty($rowLower['allocated_part_id']) ? "#" . $rowLower['allocated_part_id'] : 'None';
                                $techDisplay = !empty($techKey) && isset($rowLower[$techKey]) ? $rowLower[$techKey] : 'Staff';
                                
                                $statusText = $rowLower['status'] ?? 'In Progress';
                                $badgeClass = 'bg-warning text-dark';
                                if (strtolower($statusText) == 'completed') $badgeClass = 'bg-success';
                                if (strtolower($statusText) == 'pending') $badgeClass = 'bg-secondary';
                                
                                echo "<tr>";
                                echo "<td><b>#{$displayId}</b></td>";
                                echo "<td>" . htmlspecialchars($rowLower['customer_name'] ?? 'Walk-in') . "</td>";
                                echo "<td><span class='fw-semibold text-primary'>" . htmlspecialchars($rowLower['device_model'] ?? 'Unknown') . "</span></td>";
                                echo "<td>" . htmlspecialchars($displayPart) . "</td>";
                                echo "<td><span class='badge bg-light text-dark border'>{$techDisplay}</span></td>";
                                echo "<td class='text-center'><span class='badge {$badgeClass}'>" . ucfirst($statusText) . "</span></td>";
                                echo "</tr>";
                            }
                        } catch (\PDOException $e) {
                            echo "<tr><td colspan='6' class='text-center text-muted small py-3'>No logs available or table missing initialization.</td></tr>";
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
