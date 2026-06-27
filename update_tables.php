<?php
require_once 'db.php';

try {
    echo "<h3>Updating Cloud Database Table Definitions...</h3>";

    // 1. Check if the column exists first using a pure MySQL query
    $checkColumn = $pdo->query("SHOW COLUMNS FROM job_cards LIKE 'allocated_part_id'")->fetch();

    if (!$checkColumn) {
        // If it doesn't exist, safe to inject it
        $pdo->exec("ALTER TABLE job_cards ADD COLUMN allocated_part_id INT(11) NULL AFTER problem_description;");
        echo "✅ Column 'allocated_part_id' successfully synced to table 'job_cards'.<br>";
    } else {
        echo "ℹ️ Column 'allocated_part_id' already exists in 'job_cards'. Skipping.<br>";
    }

    // 2. Build the structural 'inventory' table layout
    $pdo->exec("CREATE TABLE IF NOT EXISTS inventory (
        part_id INT(11) AUTO_INCREMENT PRIMARY KEY,
        part_name VARCHAR(100) NOT NULL,
        serial_number VARCHAR(100) NOT NULL,
        quantity INT(11) DEFAULT 0,
        cost_price DECIMAL(10,2) DEFAULT 0.00,
        selling_price DECIMAL(10,2) DEFAULT 0.00
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "✅ Structure layout for table 'inventory' verified successfully.<br>";

    echo "<br>🎉 <b>Database structure successfully repaired!</b>";

} catch (\PDOException $e) {
    die("<br>❌ Database Alteration Failed: " . htmlspecialchars($e->getMessage()));
}
?>
