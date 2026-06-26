<?php
require_once 'db.php';
// 1. Read connection variables from Render's environment (fallback to local XAMPP)
$host = getenv('DB_HOST') ?: 'localhost';
$port = getenv('DB_PORT') ?: '3306';
$db   = getenv('DB_NAME') ?: 'defaultdb';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASSWORD') ?: '';

try {
    // 2. Set up connection options (Aiven requires SSL when running in the cloud)
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];
    
    if (getenv('DB_HOST')) {
        $options[PDO::MYSQL_ATTR_SSL_CA] = true;
    }
    
    // 3. Establish the secure database link
    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, $options);

} catch (\PDOException $e) {
    // 4. Output the exact technical error details if the handshake fails
    die("Database connection failed: " . $e->getMessage());
}

// --- Rest of your original index.php code begins here ---

// Handle Customer Upsell / Extra Part Change Requests
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_request'])) {
    $job_id = intval($_POST['target_job_id']);
    $additional_request = trim($_POST['additional_request']);
    $search_phone = trim($_POST['customer_phone_fallback']);
    
    if ($job_id > 0 && !empty($additional_request)) {
        // Your prepare statements and logic continue below smoothly...
