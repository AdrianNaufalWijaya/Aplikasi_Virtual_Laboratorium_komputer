<?php
session_start();
require_once '../koneksi.php';
date_default_timezone_set('Asia/Jakarta');

// Pastikan dosen sudah login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'dosen') {
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'User not authenticated']);
    exit();
}

$user_id = $_SESSION['user_id'];
$database = new Database();
$pdo = $database->getConnection();

// Helper function untuk format waktu
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    if ($time < 60) return 'baru saja';
    if ($time < 3600) return floor($time / 60) . ' menit yang lalu';
    if ($time < 86400) return floor($time / 3600) . ' jam yang lalu';
    return date('d M Y', strtotime($datetime));
}

try {
    // Ambil 10 notifikasi terbaru
    $sql = "SELECT notification_id, title, message, type, is_read, created_at 
            FROM notification 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT 10";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Hitung notifikasi yang belum dibaca
    $sql_count = "SELECT COUNT(*) as unread_count FROM notification WHERE user_id = ? AND is_read = 0";
    $stmt_count = $pdo->prepare($sql_count);
    $stmt_count->execute([$user_id]);
    $unread_count = $stmt_count->fetch(PDO::FETCH_ASSOC)['unread_count'];

    // Format data untuk JSON
    $formatted_notifications = [];
    foreach ($notifications as $notif) {
        $formatted_notifications[] = [
            'id' => $notif['notification_id'],
            'title' => htmlspecialchars($notif['title']),
            'message' => htmlspecialchars($notif['message']),
            'type' => $notif['type'],
            'time' => timeAgo($notif['created_at']),
            'unread' => !$notif['is_read']
        ];
    }

    // Kirim response
    header('Content-Type: application/json');
    echo json_encode([
        'notifications' => $formatted_notifications,
        'unread_count' => (int)$unread_count
    ]);

} catch (PDOException $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Database query failed: ' . $e->getMessage()]);
}
?>