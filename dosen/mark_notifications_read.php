<?php
session_start();
require_once '../koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'dosen') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$database = new Database();
$pdo = $database->getConnection();

try {
    $sql = "UPDATE notification SET is_read = 1, read_at = NOW() WHERE user_id = ? AND is_read = 0";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);

    header('Content-Type: application/json');
    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database update failed.']);
}
?>