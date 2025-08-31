<?php
session_start();
require_once '../koneksi.php';
date_default_timezone_set('Asia/Jakarta');

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

// --- FUNGSI-FUNGSI DATABASE ---

// FUNGSI UNTUK MENGAMBIL RESERVASI AKTIF
function getApprovedActiveReservations($pdo, $user_id) {
    $sql = "
        SELECT 
            r.reservation_id, r.computer_id,
            l.lab_id, l.lab_name, l.lab_type,
            vc.computer_name
        FROM reservation r
        JOIN laboratory l ON r.lab_id = l.lab_id
        JOIN virtual_computer vc ON r.computer_id = vc.computer_id
        WHERE r.user_id = ? 
        AND r.status = 'confirmed' 
        AND NOW() BETWEEN CONCAT(r.reservation_date, ' ', r.start_time) AND CONCAT(r.reservation_date, ' ', r.end_time)
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// FUNGSI UNTUK MENGAMBIL SEMUA LAB (YANG SEBELUMNYA KOSONG)
function getAllLaboratories($pdo) {
    $sql = "
        SELECT 
            l.lab_id,
            l.lab_name,
            l.capacity,
            l.lab_type,
            l.description,
            l.location,
            COUNT(vc.computer_id) as computer_count,
            COUNT(CASE WHEN vc.status = 'available' THEN 1 END) as available_computers
        FROM laboratory l
        LEFT JOIN virtual_computer vc ON l.lab_id = vc.lab_id
        GROUP BY l.lab_id
        ORDER BY l.lab_name ASC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Helper function untuk ikon
function getLabIcon($labType) {
    switch (strtolower($labType)) {
        case 'programming': return 'fa-code';
        case 'database': return 'fa-database';
        case 'networking': return 'fa-network-wired';
        case 'multimedia': return 'fa-photo-film';
        default: return 'fa-flask';
    }
}


// Ambil data
$laboratories = getAllLaboratories($pdo);
$active_reservations = getApprovedActiveReservations($pdo, $user_id);

// Kelompokkan lab by type untuk filter
$labsByType = [];
foreach ($laboratories as $lab) {
    $labsByType[ucfirst($lab['lab_type'])][] = $lab;
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laboratorium Virtual</title>
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
            <div class="tabs-container">
                <div class="tabs">
                    <button class="tab active" onclick="filterLabs(this, 'active_reservations')">
                        <i class="fas fa-play-circle"></i> Reservasi Aktif (<?php echo count($active_reservations); ?>)
                    </button>
                    <button class="tab" onclick="filterLabs(this, 'all')">
                        <i class="fas fa-border-all"></i> Semua Lab</button>
                    <?php foreach ($labsByType as $type => $labs): ?>
                        <button class="tab" onclick="filterLabs(this, '<?php echo strtolower($type); ?>')">
                            <i class="fas <?php echo getLabIcon(strtolower($type)); ?>"></i> <?php echo $type; ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <div id="active_reservations" class="tab-content active">
                <?php if (empty($active_reservations)): ?>
                    <div class="empty-state">
                        <i class="fas fa-calendar-times"></i>
                        <h3>Tidak Ada Reservasi Aktif</h3>
                        <p>Reservasi yang sudah disetujui dan sesuai dengan jadwal saat ini akan muncul di sini.</p>
                    </div>
                <?php else: ?>
                    <div class="course-grid">
                        <?php foreach ($active_reservations as $res): ?>
                            <div class="course-card">
                                <div class="course-header">
                                    <div class="course-icon"><i class="fas <?php echo getLabIcon($res['lab_type']); ?>"></i></div>
                                    <div class="course-info">
                                        <h3 class="course-title"><?php echo htmlspecialchars($res['lab_name']); ?></h3>
                                        <div class="course-code">Anda memiliki sesi aktif</div>
                                    </div>
                                </div>
                                <div class="course-details">
                                    <div class="info-item">
                                        <i class="fas fa-desktop"></i>
                                        Komputer: <strong><?php echo htmlspecialchars($res['computer_name']); ?></strong>
                                    </div>
                                </div>
                                <div class="course-actions mt-15">
                                    <button class="btn btn-success" onclick="startSession(<?php echo $res['computer_id']; ?>)">
                                        <i class="fas fa-play"></i> Mulai Sesi
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div id="all-labs" class="tab-content" style="display: none;">
                <div class="course-grid">
                    <?php foreach ($laboratories as $lab): ?>
                        <div class="course-card lab-card" data-lab-type="<?php echo strtolower($lab['lab_type']); ?>">
                            <div class="course-header">
                                <div class="course-icon"><i class="fas <?php echo getLabIcon($lab['lab_type']); ?>"></i></div>
                                <div class="course-info">
                                    <h3 class="course-title"><?php echo htmlspecialchars($lab['lab_name']); ?></h3>
                                    <div class="course-code"><?php echo ucfirst($lab['lab_type']); ?></div>
                                </div>
                            </div>
                            <p class="mb-15" style="color: #6c757d; font-size: 13px;"><?php echo htmlspecialchars($lab['description']); ?></p>
                            <div class="course-details">
                                <div class="info-item"><i class="fas fa-map-marker-alt"></i><?php echo htmlspecialchars($lab['location']); ?></div>
                                <div class="info-item"><i class="fas fa-users"></i>Kapasitas: <?php echo $lab['capacity']; ?> Orang</div>
                                <div class="info-item"><i class="fas fa-desktop"></i><?php echo $lab['available_computers']; ?> / <?php echo $lab['computer_count']; ?> Komputer Tersedia</div>
                            </div>
                            <div class="course-actions mt-15">
                                <a href="lab_detail.php?id=<?php echo $lab['lab_id']; ?>" class="btn btn-primary"><i class="fas fa-arrow-right"></i> Lihat Komputer</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="mahasiswa.js"></script>

    <script>
    // Pastikan tab "Reservasi Aktif" menjadi default saat halaman dimuat
    document.addEventListener('DOMContentLoaded', function() {
    // Panggil fungsi dengan elemen 
    filterLabs(document.querySelector('.tabs .tab'), 'active_reservations');
    });

    function filterLabs(buttonElement, filter) {
        // Sembunyikan semua konten tab utama
        document.getElementById('active_reservations').style.display = 'none';
        document.getElementById('all-labs').style.display = 'none';
        
        // Hapus kelas 'active' dari semua tombol tab
        document.querySelectorAll('.tabs .tab').forEach(tab => tab.classList.remove('active'));
        // Tambahkan 'active' ke tombol yang diklik
        buttonElement.classList.add('active');

        if (filter === 'active_reservations') {
            document.getElementById('active_reservations').style.display = 'block';
        } else {
            document.getElementById('all-labs').style.display = 'block';
            const labCards = document.querySelectorAll('.lab-card');
            labCards.forEach(card => {
                if (filter === 'all' || card.dataset.labType === filter) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }
    }

    function startSession(computerId) {
        const button = event.target;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mempersiapkan...';
        button.disabled = true;

        fetch(`start_session.php?computer_id=${computerId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.session_url) {
                    window.open(data.session_url, '_blank');
                    button.innerHTML = '<i class="fas fa-check"></i> Sesi Dimulai';
                } else {
                    alert('Gagal memulai sesi: ' + (data.error || 'Terjadi kesalahan.'));
                    button.disabled = false;
                    button.innerHTML = '<i class="fas fa-play"></i> Mulai Sesi';
                }
            })
            .catch(error => {
                alert('Gagal terhubung ke server: ' + error);
                button.disabled = false;
                button.innerHTML = '<i class="fas fa-play"></i> Mulai Sesi';
            });
    }
</script>
</body>
</html>