<?php
session_start();
require_once '../koneksi.php';

// Create database connection
$database = new Database();
$pdo = $database->getConnection();

// Check if user is logged in and is mahasiswa
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mahasiswa') {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];
$lab_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$lab_id) {
    header('Location: lab_virtual.php');
    exit();
}

// Fungsi untuk mengecek reservasi aktif mahasiswa yang sudah disetujui
function getApprovedActiveReservation($pdo, $user_id, $lab_id) {
    $sql = "
        SELECT reservation_id, computer_id 
        FROM reservation 
        WHERE user_id = ? 
        AND lab_id = ? 
        AND status = 'confirmed' 
        AND NOW() BETWEEN CONCAT(reservation_date, ' ', start_time) AND CONCAT(reservation_date, ' ', end_time)
        LIMIT 1
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id, $lab_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fungsi untuk mengambil detail lab
function getLabDetails($pdo, $lab_id) {
    $sql = "SELECT lab_name, description, location FROM laboratory WHERE lab_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$lab_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fungsi untuk mengambil daftar komputer di lab
function getComputersInLab($pdo, $lab_id) {
    $sql = "SELECT * FROM virtual_computer WHERE lab_id = ? ORDER BY computer_name ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$lab_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Helper untuk mendapatkan badge status
function getComputerStatusBadge($status) {
    switch (strtolower($status)) {
        case 'available':
            return '<span class="status-badge enrolled-badge"><i class="fas fa-check-circle"></i> Tersedia</span>';
        case 'occupied':
            return '<span class="status-badge pending-badge"><i class="fas fa-user-clock"></i> Digunakan</span>';
        case 'maintenance':
            return '<span class="status-badge rejected-badge"><i class="fas fa-tools"></i> Perbaikan</span>';
        default:
            return '<span class="status-badge">' . ucfirst($status) . '</span>';
    }
}

// Ambil data
$labDetails = getLabDetails($pdo, $lab_id);
$computers = getComputersInLab($pdo, $lab_id);
$activeReservation = getApprovedActiveReservation($pdo, $user_id, $lab_id);

if (!$labDetails) {
    header('Location: lab_virtual.php?error=notfound');
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Lab: <?php echo htmlspecialchars($labDetails['lab_name']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="mahasiswa.css">
    
</head>
<body>
    <button class="mobile-menu-btn"><i class="fas fa-bars"></i></button>
    <?php include 'sidebar_mahasiswa.html'; ?>

    <div class="main-content">
        <div class="top-nav">
             </div>

        <div class="content-area">
            <div class="section">
                <div class="d-flex justify-content-between align-items-center mb-20">
                    <div>
                        <h2 class="section-title mb-0">
                            Komputer Virtual di <?php echo htmlspecialchars($labDetails['lab_name']); ?>
                        </h2>
                        <p class="mt-8" style="color: #6c757d;">Pilih komputer yang tersedia untuk membuat reservasi.</p>
                    </div>
                </div>
                
                <?php if (empty($computers)): ?>
                    <div class="empty-state">
                        <i class="fas fa-plug"></i>
                        <h3>Tidak Ada Komputer</h3>
                        <p>Belum ada komputer virtual yang terdaftar di laboratorium ini.</p>
                    </div>
                <?php else: ?>
                    <div class="computer-grid">
                        <?php foreach ($computers as $computer): ?>
                            <div class="computer-card">
                                <div class="computer-card-header">
                                    <div class="computer-icon">
                                        <i class="fas fa-desktop"></i>
                                    </div>
                                    <div>
                                        <h4 class="computer-name"><?php echo htmlspecialchars($computer['computer_name']); ?></h4>
                                        <?php echo getComputerStatusBadge($computer['status']); ?>
                                    </div>
                                </div>

                                <div class="computer-specs">
                                    <div class="spec-item"><i class="fas fa-network-wired"></i> IP: <?php echo htmlspecialchars($computer['ip_address']); ?></div>
                                    <div class="spec-item"><i class="fas fa-microchip"></i> CPU: <?php echo $computer['cpu_cores']; ?> Cores</div>
                                    <div class="spec-item"><i class="fas fa-memory"></i> RAM: <?php echo $computer['ram_size']; ?> GB</div>
                                    <div class="spec-item"><i class="fas fa-hdd"></i> Storage: <?php echo $computer['storage_size']; ?> GB</div>
                                </div>
                                
                                <?php
                                    if ($activeReservation && $activeReservation['computer_id'] == $computer['computer_id']) {
                                        // Jika ada reservasi aktif untuk komputer INI, tampilkan tombol "Mulai Sesi"
                                        echo '<button class="btn btn-success" onclick="startSession(' . $computer['computer_id'] . ')"><i class="fas fa-play"></i> Mulai Sesi Anda</button>';

                                    } elseif ($computer['status'] === 'available' && !$activeReservation) {
                                        // Jika komputer tersedia DAN mahasiswa TIDAK punya reservasi aktif sama sekali, tampilkan tombol reservasi
                                        echo '<a href="reservasi.php?lab_id=' . $lab_id . '&computer_id=' . $computer['computer_id'] . '" class="btn btn-primary"><i class="fas fa-calendar-plus"></i> Reservasi PC Ini</a>';

                                    } else {
                                        // Jika komputer tidak tersedia ATAU mahasiswa sudah punya reservasi aktif (tapi untuk PC lain), tombol tidak aktif
                                        echo '<button class="btn btn-secondary" disabled><i class="fas fa-lock"></i> Tidak Tersedia</button>';
                                    }
                                ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script src="mahasiswa.js"></script>
    <script>
        function startSession(computerId) {
            alert('Mengalihkan ke sesi untuk komputer ID: ' + computerId);
        }
    </script>
</body>
</html>