<?php
session_start();
require_once '../koneksi.php';

// Hanya dosen yang bisa akses
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'dosen') {
    http_response_code(403);
    echo json_encode(['error' => 'Akses ditolak']);
    exit();
}

$database = new Database();
$pdo = $database->getConnection();

// Ambil parameter dari request AJAX
$lab_id = $_GET['lab_id'] ?? 0;
$reservation_date = $_GET['date'] ?? '';
$start_time = $_GET['start'] ?? '';
$end_time = $_GET['end'] ?? '';

if (!$lab_id || !$reservation_date || !$start_time || !$end_time) {
    http_response_code(400);
    echo json_encode(['error' => 'Parameter tidak lengkap']);
    exit();
}

try {
    // 1. Cari semua computer_id yang sudah direservasi pada waktu yang tumpang tindih
    $sql_booked = "
        SELECT DISTINCT computer_id 
        FROM reservation 
        WHERE lab_id = ? 
        AND reservation_date = ?
        AND status = 'confirmed'
        AND computer_id IS NOT NULL
        AND (? < end_time) AND (? > start_time)
    ";
    
    $stmt_booked = $pdo->prepare($sql_booked);
    $stmt_booked->execute([$lab_id, $reservation_date, $start_time, $end_time]);
    $booked_computer_ids = $stmt_booked->fetchAll(PDO::FETCH_COLUMN, 0);

    // 2. Ambil semua komputer di lab tersebut
    $sql_all_computers = "SELECT computer_id, computer_name FROM virtual_computer WHERE lab_id = ?";
    $stmt_all = $pdo->prepare($sql_all_computers);
    $stmt_all->execute([$lab_id]);
    $all_computers = $stmt_all->fetchAll(PDO::FETCH_ASSOC);

    // 3. Filter di PHP untuk mendapatkan komputer yang tersedia
    $available_computers = [];
    foreach ($all_computers as $computer) {
        // Jika ID komputer tidak ada di dalam daftar yang sudah dibooking, maka komputer itu tersedia
        if (!in_array($computer['computer_id'], $booked_computer_ids)) {
            $available_computers[] = $computer;
        }
    }

    header('Content-Type: application/json');
    echo json_encode($available_computers);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>