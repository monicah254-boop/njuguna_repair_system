<?php
require 'db.php';

$search_id = '';
$job = null;
$error_message = '';

// Handle Tracking Search Request
if (isset($_GET['job_id'])) {
    $search_id = trim($_GET['job_id']);
    
    // Clean out the clean prefix "#JC-" if the customer typed it in
    $clean_id = str_ireplace('#JC-', '', $search_id);
    $clean_id = ltrim($clean_id, '0'); // Strip leading zeros

    if (!empty($clean_id) && is_numeric($clean_id)) {
        try {
            $stmt = $pdo->prepare('SELECT * FROM job_cards WHERE job_id = ?');
            $stmt->execute([$clean_id]);
            $job = $stmt->fetch();

            if (!$job) {
                $error_message = "No active repair tracking ticket found for ID: " . htmlspecialchars($search_id);
            }
        } catch (\PDOException $e) {
            $error_message = "System Link Error. Please try again later.";
        }
    } else {
        $error_message = "Please enter a valid numeric Ticket tracking link ID.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Njuguna Electronics - Customer Repair Tracking Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-dark d-flex align-items-center justify-content-center" style="min-height: 100vh;">

    <div class="container" style="max-width: 600px;">
        <div class="text-center mb-4">
            <h2 class="text-white fw-bold">Njuguna Electronics</h2>
            <p class="text-muted">Real-Time Repair & Maintenance Tracking Desk</p>
        </div>

        <div class="card shadow border-0 p-4 rounded-4 bg-light">
            <div class="card-body">
                <h5 class="fw-bold mb-3 text-center text-dark">Track Your Device Repair Status</h5>
                
                <form action="track_job.php" method="GET" class="mb-4">
                    <div class="input-group input-group-lg shadow-sm">
                        <input type="text" name="job_id" class="form-control text-center bg-white" placeholder="Enter Job ID (e.g. #JC-0001 or 1)" value="<?php echo htmlspecialchars($search_id); ?>" required autocomplete="off">
                        <button type="submit" class="btn btn-primary px-4 fw-bold">Search</button>
                    </div>
                </form>

                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger p-3 text-center border-0 rounded-3 mb-0 shadow-sm">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <?php if ($job): ?>
                    <hr class="my-4 text-muted">
                    <div class="p-3 bg-white rounded-3 shadow-sm border border-light">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-uppercase text-muted small fw-bold">Ticket Link</span>
                            <span class="badge bg-secondary p-2">#JC-<?php echo str_pad($job['job_id'], 4, '0', STR_PAD_LEFT); ?></span>
                        </div>
                        
                        <div class="mb-3">
                            <label class="text-muted small d-block">Device Model</label>
                            <span class="fw-bold fs-5 text-dark"><?php echo htmlspecialchars($job['device_model']); ?></span>
                        </div>

                        <div class="mb-3">
                            <label class="text-muted small d-block">Reported Problem</label>
                            <span class="text-dark bg-light p-2 rounded d-block border-start border-primary border-3 mt-1"><?php echo htmlspecialchars($job['problem_description']); ?></span>
                        </div>

                        <div class="mt-4 pt-2 text-center">
                            <label class="text-muted small d-block mb-2 text-uppercase fw-bold tracking-wider">Current Live Status</label>
                            <?php 
                                $status = $job['status'];
                                $badge_class = 'bg-danger';
                                $message = 'Your device is awaiting primary technician diagnostics.';
                                
                                if ($status === 'In Progress') {
                                    $badge_class = 'bg-warning text-dark';
                                    $message = 'The component parts are allocated. Repair is actively underway.';
                                } elseif ($status === 'Completed') {
                                    $badge_class = 'bg-info text-dark';
                                    $message = 'Repair complete! Quality bench verification tests passed.';
                                } elseif ($status === 'Delivered') {
                                    $badge_class = 'bg-success';
                                    $message = 'Device has been fully collected by owner. Handover finalized!';
                                }
                            ?>
                            <span class="badge <?php echo $badge_class; ?> fs-4 py-2 px-4 shadow-sm mb-3">
                                <?php echo htmlspecialchars($status); ?>
                            </span>
                            <p class="text-muted small mb-0 px-2 mt-1"><?php echo $message; ?></p>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </div>
        
        <div class="text-center mt-4">
            <a href="login.php" class="text-muted text-decoration-none small">Employer Portal Access →</a>
        </div>
    </div>

</body>
</html>