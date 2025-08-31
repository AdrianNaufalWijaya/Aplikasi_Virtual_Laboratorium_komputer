<?php
session_start();

// Include database connection untuk logging
require_once 'koneksi.php';

// Log logout activity jika user sedang login
if (isset($_SESSION['user_id'])) {
    try {
        $database = new Database();
        $pdo = $database->getConnection();
        
        if ($pdo) {
            // Log logout activity
            $log_stmt = $pdo->prepare("
                INSERT INTO activity_log (user_id, action, entity_type, entity_id, ip_address, user_agent) 
                VALUES (?, 'logout', 'user', ?, ?, ?)
            ");
            $log_stmt->execute([
                $_SESSION['user_id'], 
                $_SESSION['user_id'], 
                $_SERVER['REMOTE_ADDR'] ?? 'unknown', 
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
        }
    } catch (Exception $e) {
        // Log error but don't stop logout process
        error_log("Logout logging error: " . $e->getMessage());
    }
}

// Hapus semua session
session_unset();
session_destroy();

// Hapus session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

    
header('Location: login.php?logout=success');
exit();
?>