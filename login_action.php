<?php
session_start();

// 1. Import our central cloud database handler (Replaces the local connection block)
require_once 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        try {
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
        } catch (\PDOException $e) {
            $_SESSION['error'] = "Database lookup failed: " . $e->getMessage();
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
