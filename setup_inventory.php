<?php
require_once 'db.php';
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS inventory (
        part_id INT(11) AUTO_INCREMENT PRIMARY KEY,
        part_name VARCHAR(100) NOT NULL,
        serial_number VARCHAR(100) NOT NULL,
        quantity INT(11) DEFAULT 0,
        cost_price DECIMAL(10,2) DEFAULT 0.00,
        selling_price DECIMAL(10,2) DEFAULT 0.00
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "✅ Cloud table 'inventory' built successfully!";
} catch(\PDOException $e) {
    echo "❌ Failed: " . $e->getMessage();
}
