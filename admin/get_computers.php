<?php
// get_computers.php
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
    
    $lab_id = isset($_GET['lab_id']) ? (int)$_GET['lab_id'] : 0;
    
    if ($lab_id <= 0) {
        throw new Exception("Lab ID tidak valid!");
    }
    
    // Get computers for the specified lab
    $query = "SELECT 
                vc.computer_id,
                vc.lab_id,
                vc.computer_name,
                vc.ip_address,
                vc.mac_address,
                vc.cpu_cores,
                vc.ram_size,
                vc.storage_size,
                vc.status,
                vc.created_at,
                l.lab_name
              FROM virtual_computer vc
              LEFT JOIN laboratory l ON vc.lab_id = l.lab_id
              WHERE vc.lab_id = :lab_id
              ORDER BY vc.computer_name";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':lab_id', $lab_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $computers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get lab info
    $lab_query = "SELECT lab_name FROM laboratory WHERE lab_id = :lab_id";
    $lab_stmt = $conn->prepare($lab_query);
    $lab_stmt->bindParam(':lab_id', $lab_id, PDO::PARAM_INT);
    $lab_stmt->execute();
    $lab_info = $lab_stmt->fetch(PDO::FETCH_ASSOC);
    
    $response = [
        'success' => true,
        'lab_info' => $lab_info,
        'computers' => $computers,
        'total' => count($computers)
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'computers' => [],
        'total' => 0
    ]);
}
?>