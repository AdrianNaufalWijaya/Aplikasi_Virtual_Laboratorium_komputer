<?php
session_start();
require_once '../koneksi.php';

// Set content type untuk JSON
header('Content-Type: application/json');

// Create database connection
$database = new Database();
$pdo = $database->getConnection();

// Check if user is logged in and is mahasiswa
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mahasiswa') {
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized access'
    ]);
    exit();
}

$user_id = $_SESSION['user_id'];
$session_id = isset($_GET['session_id']) ? intval($_GET['session_id']) : 0;

if (!$session_id) {
    echo json_encode([
        'success' => false,
        'error' => 'Session ID tidak valid'
    ]);
    exit();
}

try {
    $pdo->beginTransaction();
    
    // Dapatkan info sesi sebelum berakhir
    $sql = "SELECT computer_id FROM session WHERE session_id = ? AND user_id = ? AND status = 'active'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$session_id, $user_id]);
    $session_info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$session_info) {
        $pdo->rollBack();
        echo json_encode([
            'success' => false,
            'error' => 'Sesi tidak ditemukan atau sudah berakhir'
        ]);
        exit();
    }
    
    // Update session status and end time
    $update_session = "UPDATE session SET status = 'completed', end_time = NOW() WHERE session_id = ?";
    $stmt = $pdo->prepare($update_session);
    $stmt->execute([$session_id]);
    
    // Update computer status back to available
    $update_computer = "UPDATE virtual_computer SET status = 'available', current_user_id = NULL WHERE computer_id = ?";
    $stmt = $pdo->prepare($update_computer);
    $stmt->execute([$session_info['computer_id']]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Sesi berhasil diakhiri'
    ]);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>