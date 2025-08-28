<?php
session_start();
require_once '../koneksi.php';
date_default_timezone_set('Asia/Jakarta');

// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
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
    // Ambil 10 notifikasi terbaru yang belum dibaca
    $sql = "SELECT notification_id, title, message, type, is_read, created_at 
            FROM notification 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT 10";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format data sebelum dikirim sebagai JSON
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

    // Set header ke JSON dan kirim data
    header('Content-Type: application/json');
    echo json_encode($formatted_notifications);

} catch (PDOException $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Database query failed: ' . $e->getMessage()]);
}
?>