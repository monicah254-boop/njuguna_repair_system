<?php
session_start();

$host = 'localhost';
$db   = 'njuguna_repair_dp_v2';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=$charset", $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (\PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        // Query user directly matching both username and raw password string
        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ? AND password = ?');
        $stmt->execute([$username, $password]);
        $user = $stmt->fetch();

        if ($user) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            $role = trim($user['role']);
            if (strcasecmp($role, 'Admin') == 0) {
                header("Location: admin_dashboard.php");
                exit();
            } else if (strcasecmp($role, 'Technician') == 0) {
                header("Location: tech-dashboard.php");
                exit();
            } else {
                $_SESSION['error'] = "Role configuration mismatch.";
                header("Location: login.php");
                exit();
            }
        } else {
            $_SESSION['error'] = "Invalid system credentials. Please try again.";
            header("Location: login.php");
            exit();
        }
    } else {
        $_SESSION['error'] = "Please fill in all fields.";
        header("Location: login.php");
        exit();
    }
}
?>