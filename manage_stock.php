<?php
session_start();
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

// Handle adding new stock item through the admin dashboard form input pipeline
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_stock'])) {
    $p_name = trim($_POST['part_name']);
    $serial = trim($_POST['serial_number']);
    $qty    = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;
    $c_price = floatval($_POST['cost_price']);
    $s_price = floatval($_POST['selling_price']);

    if (!empty($p_name) && !empty($serial)) {
        // Find if the table uses 'quantity' or fallback tracking
        $testStmt = $pdo->query("SELECT * FROM inventory LIMIT 1");
        $rowSample = $testStmt->fetch();
        
        if ($rowSample && array_key_exists('quantity', $rowSample)) {
            $stmt = $pdo->prepare("INSERT INTO inventory (part_name, serial_number, quantity, cost_price, selling_price) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$p_name, $serial, $qty, $c_price, $s_price]);
        } else {
            // Dynamic insertion ignoring quantity field if your template handles balances externally
            $stmt = $pdo->prepare("INSERT INTO inventory (part_name, serial_number, cost_price, selling_price) VALUES (?, ?, ?, ?)");
            $stmt->execute([$p_name, $serial, $c_price, $s_price]);
        }
        $msg = "<div class='alert alert-success py-2 small'>New serialized item logged to inventory table successfully!</div>";
    } else {
        $msg = "<div class='alert alert-danger py-2 small'>Please fill in both the Part Name and Serial Number fields.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Manager - Njuguna Electronics</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .card { border: none; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .table-container { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
    </style>
</head>
<body>
<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 bg-dark text-white p-3 rounded shadow-sm">
        <h5 class="mb-0 fw-bold">Inventory & Spare Parts Stock Manager</h5>
        <div>
            <a href="admin_dashboard.php" class="btn btn-outline-light btn-sm me-2 fw-semibold px-3">Back to Dashboard</a>
            <a href="login.php" class="btn btn-danger btn-sm px-3">Logout</a>
        </div>
    </div>

    <?php if(!empty($msg)) echo $msg; ?>

    <div class="row text-start">
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white fw-bold py-3">Add New Stock Item</div>
                <div class="card-body p-4">
                    <form action="manage_stock.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label small text-muted fw-semibold">Part Description</label>
                            <input type="text" class="form-control" name="part_name" required placeholder="e.g. iPhone 13 Pro Screen">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small text-muted fw-semibold">Serial Number / SKU</label>
                            <input type="text" class="form-control" name="serial_number" required placeholder="e.g. SCR-IP13-01" autocomplete="off">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small text-muted fw-semibold">Initial Stock Level Quantity</label>
                            <input type="number" class="form-control" name="quantity" value="10" min="0">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small text-muted fw-semibold">Cost Price (Ksh)</label>
                            <input type="number" step="0.01" class="form-control" name="cost_price" required placeholder="0.00">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small text-muted fw-semibold">Selling Price (Ksh)</label>
                            <input type="number" step="0.01" class="form-control" name="selling_price" required placeholder="0.00">
                        </div>
                        <button type="submit" name="add_stock" class="btn btn-primary w-100 py-2 fw-semibold">Add to Inventory</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="table-container">
                <h6 class="fw-bold text-secondary border-bottom pb-2 mb-3">Current Stock Levels & Component Matrix</h6>
                <table class="table table-hover align-middle small">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Part Description</th>
                            <th>Serial / SKU</th>
                            <th>Stock Qty</th>
                            <th>Cost</th>
                            <th>Selling Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $pdo->query("SELECT * FROM inventory ORDER BY part_name ASC");
                        while ($row = $stmt->fetch()) {
                            // Dynamic safe mapping fallbacks to prevent errors across different schema configurations
                            $qty = isset($row['quantity']) ? $row['quantity'] : 1;
                            $serialCode = isset($row['serial_number']) ? $row['serial_number'] : 'N/A';
                            
                            echo "<tr>";
                            echo "<td><b>#{$row['part_id']}</b></td>";
                            echo "<td><span class='fw-semibold'>" . htmlspecialchars($row['part_name']) . "</span></td>";
                            echo "<td><code class='text-primary fw-bold'>{$serialCode}</code></td>";
                            echo "<td><b>{$qty}</b> units</td>";
                            echo "<td class='text-muted'>Ksh " . number_format($row['cost_price'], 2) . "</td>";
                            echo "<td><span class='fw-bold text-success'>Ksh " . number_format($row['selling_price'], 2) . "</span></td>";
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