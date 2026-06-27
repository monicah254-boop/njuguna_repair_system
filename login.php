<?php
require_once 'db.php';
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
    ];
    
    // Require SSL initialization if connecting to cloud database clusters
    if (getenv('DB_HOST')) {
        $options[PDO::MYSQL_ATTR_SSL_CA] = true;
    }
    
    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, $options);

} catch (\PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// --- Rest of your original login.php interface or session initialization logic below ---
