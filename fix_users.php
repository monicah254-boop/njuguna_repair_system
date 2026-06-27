<?php
require_once 'db.php';

try {
    echo "<h3>Updating System Authorization Records...</h3>";

    // 1. Clear out any old tester accounts to prevent clutter
    $pdo->exec("TRUNCATE TABLE users");
    echo "✅ Old user table cleared.<br>";

    // 2. Prepare the secure insert statement
    $insert = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");

    // 3. Insert your Master Admin Account (Username: njuguna | Password: 123)
    $insert->execute(['njuguna', '123', 'Admin']);
    echo "✅ Admin account 'njuguna' with password '123' created successfully.<br>";

    // 4. Insert your Technician Account (Username: technician1 | Password: 123)
    $insert->execute(['technician1', '123', 'Technician']);
    echo "✅ Technician account 'technician1' with password '123' created successfully.<br>";

    echo "<br>🎉 <b>Credentials updated! You can now log in.</b>";

} catch (\PDOException $e) {
    die("<br>❌ Update Failed: " . htmlspecialchars($e->getMessage()));
}
?>
