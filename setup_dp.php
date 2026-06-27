<?php
// 1. Link into your working secure cloud database connection
require_once 'db.php';

try {
    echo "<h3>Starting Njuguna Electronics Cloud Migration Pipeline...</h3>";

    // 2. Build the structural 'users' table if it doesn't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        user_id INT(11) AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role VARCHAR(20) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "✅ Structure for table 'users' configured successfully.<br>";

    // 3. Build the structural 'job_cards' table if it doesn't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS job_cards (
        job_id INT(11) AUTO_INCREMENT PRIMARY KEY,
        customer_name VARCHAR(100) NOT NULL,
        customer_phone VARCHAR(20) NOT NULL,
        device_model VARCHAR(100) NOT NULL,
        problem_description TEXT NOT NULL,
        status VARCHAR(30) DEFAULT 'Received',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "✅ Structure for table 'job_cards' configured successfully.<br>";

    // 4. Seed your default master administrator account so you can log straight in
    // Checks if the user 'admin' already exists first to prevent duplicates
    $check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $check->execute(['admin']);
    
    if ($check->fetchColumn() == 0) {
        $insert = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        // Matches your standard raw lookup structure: admin / admin123
        $insert->execute(['admin', 'admin123', 'Admin']);
        echo "👤 Default Master Administrator credentials seeded successfully.<br>";
    } else {
        echo "ℹ️ Administrator user record already exists. Skipping seeding.<br>";
    }

    echo "<br>🎉 <b>Migration Complete! All cloud tables are live and ready for production.</b>";

} catch (\PDOException $e) {
    die("<br>❌ Database Setup Failed: " . htmlspecialchars($e->getMessage()));
}
?>
