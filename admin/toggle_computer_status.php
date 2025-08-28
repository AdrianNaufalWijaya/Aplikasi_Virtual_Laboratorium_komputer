<?php
// toggle_computer_status.php
header('Content-Type: application/json');

// Aktifkan error reporting untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once '../koneksi.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    if (!$conn) {
        throw new Exception("Koneksi database gagal!");
    }
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Method tidak diizinkan!");
    }
    
    $computer_id = isset($_POST['computer_id']) ? (int)$_POST['computer_id'] : 0;
    $new_status = isset($_POST['new_status']) ? $_POST['new_status'] : '';
    
    if ($computer_id <= 0) {
        throw new Exception("Computer ID tidak valid!");
    }
    
    if (!in_array($new_status, ['available', 'maintenance', 'occupied'])) {
        throw new Exception("Status tidak valid!");
    }
    
    // Check if computer exists and not occupied if trying to change to maintenance
    $check_query = "SELECT computer_id, status, computer_name FROM virtual_computer WHERE computer_id = :computer_id";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bindParam(':computer_id', $computer_id, PDO::PARAM_INT);
    $check_stmt->execute();
    
    $computer = $check_stmt->fetch(PDO::FETCH_ASSOC);
    if (!$computer) {
        throw new Exception("Komputer tidak ditemukan!");
    }
    
    // Don't allow changing status if currently occupied
    if ($computer['status'] === 'occupied') {
        throw new Exception("Tidak dapat mengubah status komputer yang sedang digunakan!");
    }
    
    // Update computer status
    $update_query = "UPDATE virtual_computer SET status = :status WHERE computer_id = :computer_id";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bindParam(':status', $new_status, PDO::PARAM_STR);
    $update_stmt->bindParam(':computer_id', $computer_id, PDO::PARAM_INT);
    
    if ($update_stmt->execute()) {
        $status_text = [
            'available' => 'Tersedia',
            'maintenance' => 'Maintenance',
            'occupied' => 'Terpakai'
        ];
        
        echo json_encode([
            'success' => true,
            'message' => "Status komputer '{$computer['computer_name']}' berhasil diubah menjadi {$status_text[$new_status]}!",
            'new_status' => $new_status,
            'computer_id' => $computer_id
        ]);
    } else {
        throw new Exception("Gagal mengubah status komputer!");
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>