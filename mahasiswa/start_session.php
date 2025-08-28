<?php
session_start();
require_once '../koneksi.php';
date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Akses ditolak.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$computer_id = isset($_GET['computer_id']) ? intval($_GET['computer_id']) : 0;

$database = new Database();
$pdo = $database->getConnection();

// Ambil detail reservasi, termasuk software yang dibutuhkan
$sql_check = "SELECT r.reservation_id, r.software_needed 
              FROM reservation r 
              WHERE r.user_id = ? AND r.computer_id = ? AND r.status = 'confirmed'
              AND NOW() BETWEEN CONCAT(r.reservation_date, ' ', r.start_time) AND CONCAT(r.reservation_date, ' ', r.end_time)";
$stmt_check = $pdo->prepare($sql_check);
$stmt_check->execute([$user_id, $computer_id]);
$reservation_details = $stmt_check->fetch(PDO::FETCH_ASSOC);

if (!$reservation_details) {
    http_response_code(403);
    echo json_encode(['error' => 'Anda tidak memiliki reservasi aktif untuk komputer ini.']);
    exit();
}

// Ambil detail koneksi RDP
$sql_vm = "SELECT rdp_host, rdp_username, rdp_password FROM virtual_computer WHERE computer_id = ?";
$stmt_vm = $pdo->prepare($sql_vm);
$stmt_vm->execute([$computer_id]);
$vm_details = $stmt_vm->fetch(PDO::FETCH_ASSOC);

if (!$vm_details || empty($vm_details['rdp_host'])) {
    http_response_code(404);
    echo json_encode(['error' => 'Detail koneksi untuk komputer virtual ini belum diatur.']);
    exit();
}

// *** LOGIKA BARU UNTUK MEMBUKA APLIKASI SPESIFIK ***

$software_key = $reservation_details['software_needed'];
$startup_program = '';

// Mapping dari pilihan dropdown ke path aplikasi di dalam VM Windows
switch ($software_key) {
    case 'vscode':
        // Path bisa berbeda tergantung lokasi instalasi di VM Anda
        $startup_program = 'C:\Users\vlab_user\AppData\Local\Programs\Microsoft VS Code\Code.exe';
        break;
    case 'heidisql':
        $startup_program = 'C:\Program Files\HeidiSQL\heidisql.exe';
        break;
    case 'android_studio':
        $startup_program = 'C:\Program Files\Android\Android Studio\bin\studio64.exe';
        break;
    // Jika tidak ada pilihan, akan membuka desktop biasa (default)
}

$guacamole_base_url = 'http://localhost:8080/guacamole/';
$connection_id = base64_encode("c/RDP_Connection_" . $computer_id); 

$connection_params = [
    'hostname' => $vm_details['rdp_host'],
    'port' => '3389',
    'username' => $vm_details['rdp_username'],
    'password' => $vm_details['rdp_password'],
    'ignore-cert' => 'true'
];

// Tambahkan parameter 'guac.program' jika ada aplikasi yang dipilih
if (!empty($startup_program)) {
    // Parameter ini memberitahu Guacamole untuk menjalankan program ini, bukan explorer.exe (desktop)
    $connection_params['guac.program'] = $startup_program;
}

$session_url = $guacamole_base_url . '#/client/' . urlencode($connection_id) . '?' . http_build_query($connection_params);

header('Content-Type: application/json');
echo json_encode(['success' => true, 'session_url' => $session_url]);
?>