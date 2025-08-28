<?php
// Selalu mulai session di awal untuk mengaksesnya
session_start();

// Include koneksi database untuk mencatat aktivitas
require_once '../koneksi.php';

// Cek apakah ada user yang sedang login di session ini
if (isset($_SESSION['user_id'])) {
    try {
        $database = new Database();
        $pdo = $database->getConnection();
        
        if ($pdo) {
            // Catat aktivitas logout ke dalam database
            $log_stmt = $pdo->prepare("
                INSERT INTO activity_log (user_id, action, entity_type, ip_address, user_agent) 
                VALUES (?, 'logout', 'admin', ?, ?)
            ");
            $log_stmt->execute([
                $_SESSION['user_id'], 
                $_SERVER['REMOTE_ADDR'] ?? 'unknown', 
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
        }
    } catch (Exception $e) {
        // Jika pencatatan gagal, jangan hentikan proses logout.
        // Cukup catat error di log server untuk developer.
        error_log("Admin logout logging error: " . $e->getMessage());
    }
}

// 1. Hapus semua variabel session
session_unset();

// 2. Hancurkan session
session_destroy();

// 3. Hapus cookie session dari browser (opsional, tapi praktik yang baik)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Arahkan pengguna kembali ke halaman login admin
header('Location: login_admin.php?logout=success');
exit(); // Pastikan tidak ada kode lain yang berjalan setelah redirect
?>