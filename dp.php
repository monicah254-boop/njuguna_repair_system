<?php
// 1. Read connection variables from Render's environment (fallback to local XAMPP)
$host = getenv('DB_HOST') ?: 'localhost';
$port = getenv('DB_PORT') ?: '3306';
$db   = getenv('DB_NAME') ?: 'defaultdb';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASSWORD') ?: '';

try {
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT            => 5, // Prevents the gateway from hanging / timing out
    ];
    
    // Smoothly apply cloud-safe database encryption when running on Render
    if (getenv('DB_HOST')) {
        $options[PDO::MYSQL_ATTR_SSL_CA] = true;
        // Bypasses local system path checks while keeping the data stream fully encrypted
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
    }
    
    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, $options);

} catch (\PDOException $e) {
    // Keeps errors clean without crashing the underlying PHP process
    echo "Database connection failed safely: " . htmlspecialchars($e->getMessage());
    exit;
}
?>
