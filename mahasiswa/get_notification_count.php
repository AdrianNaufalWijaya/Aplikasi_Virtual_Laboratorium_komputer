<?php
session_start();
require_once '../koneksi.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['count' => 0]);
    exit();
}

$user_id = $_SESSION['user_id'];
$database = new Database();
$pdo = $database->getConnection();

try {
    $sql = "SELECT COUNT(*) as unread_count FROM notification WHERE user_id = ? AND is_read = 0";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode(['count' => (int)$result['unread_count']]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['count' => 0]);
}
?>