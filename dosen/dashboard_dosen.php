<?php
session_start();
require_once '../koneksi.php';

// Cek jika user sudah login dan rolenya adalah dosen
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'dosen') {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];

$database = new Database();
$pdo = $database->getConnection();

// --- FUNGSI UNTUK MENGAMBIL DATA DASHBOARD ---

// Menghitung jumlah reservasi yang masih pending
function getPendingReservationsCount($pdo) {
    $sql = "SELECT COUNT(*) FROM reservation WHERE status = 'pending'";
    $stmt = $pdo->query($sql);
    return $stmt->fetchColumn();
}

// Menghitung jumlah lab yang aktif
function getActiveLabsCount($pdo) {
    $sql = "SELECT COUNT(*) FROM laboratory";
    $stmt = $pdo->query($sql);
    return $stmt->fetchColumn();
}

// Menghitung jumlah mahasiswa yang terdaftar
function getTotalStudentsCount($pdo) {
    $sql = "SELECT COUNT(*) FROM users WHERE role = 'mahasiswa'";
    $stmt = $pdo->query($sql);
    return $stmt->fetchColumn();
}

// Menghitung mata kuliah yang diampu oleh dosen ini
function getMyCoursesCount($pdo, $dosen_id) {
    $sql = "SELECT COUNT(*) FROM course WHERE id_dosen = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$dosen_id]);
    return $stmt->fetchColumn();
}

// Ambil data
$pending_count = getPendingReservationsCount($pdo);
$lab_count = getActiveLabsCount($pdo);
$student_count = getTotalStudentsCount($pdo);
$my_courses_count = getMyCoursesCount($pdo, $user_id);

$sql_pending = "SELECT COUNT(e.enrollment_id) as total
                FROM enrollment e
                JOIN course c ON e.course_id = c.course_id
                WHERE e.status = 'pending' AND c.id_dosen = ?";
$stmt_pending = $pdo->prepare($sql_pending);
$stmt_pending->execute([$user_id]);
$pending_count = $stmt_pending->fetch(PDO::FETCH_ASSOC)['total'];

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Dosen</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="dosen.css">
</head>
<body>
    <?php include 'sidebar_dosen.html'; ?>

    <div class="main-content">
        <header class="header">
            <div> <h1>Selamat Datang, <?php echo htmlspecialchars($full_name); ?>!</h1>
                <p style="color: #6c757d;">Berikut adalah ringkasan aktivitas di Virtual Laboratory.</p>
            </div>
            
            <div class="header-right">
                <div class="notifications" id="notification-bell">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge-count" id="notification-count" style="display: none;">0</span>
                </div>
                <div class="notification-panel" id="notification-panel">
                    </div>
            </div>
        </header>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-card-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-card-info">
                    <div class="number"><?php echo $pending_count; ?></div>
                    <div class="label">Reservasi Pending</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon">
                    <i class="fas fa-flask"></i>
                </div>
                <div class="stat-card-info">
                    <div class="number"><?php echo $lab_count; ?></div>
                    <div class="label">Total Laboratorium</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon">
                    <i class="fas fa-book-reader"></i>
                </div>
                <div class="stat-card-info">
                    <div class="number"><?php echo $my_courses_count; ?></div>
                    <div class="label">Mata Kuliah Diampu</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-card-info">
                    <div class="number"><?php echo $student_count; ?></div>
                    <div class="label">Total Mahasiswa</div>
                </div>
            </div>
        </div>

        <div class="section">
            <h2 class="section-title">Aksi Cepat</h2>
            <div class="action-grid">
                <a href="manajemen_reservasi.php" class="action-card">
                    <div class="action-card-icon"><i class="fas fa-calendar-check"></i></div>
                    <div class="action-card-title">Kelola Reservasi</div>
                </a>
                <a href="manajemen_tugas.php" class="action-card">
                    <div class="action-card-icon"><i class="fas fa-tasks"></i></div>
                    <div class="action-card-title">Kelola Tugas</div>
                </a>
                <a href="manajemen_matakuliah.php" class="action-card">
                    <div class="action-card-icon"><i class="fas fa-book"></i></div>
                    <div class="action-card-title">Kelola Mata Kuliah</div>
                </a>
                 <a href="daftar_mahasiswa.php" class="action-card">
                    <div class="action-card-icon"><i class="fas fa-user-graduate"></i></div>
                    <div class="action-card-title">Lihat Daftar Mahasiswa</div>
                </a>
            </div>
        </div>
    </div>
    
     <script>
        document.getElementById('nav-dashboard').classList.add('active');

        // --- SCRIPT NOTIFIKASI ---
        const bell = document.getElementById('notification-bell');
        const panel = document.getElementById('notification-panel');
        const countBadge = document.getElementById('notification-count');

        // Fungsi untuk mengambil dan menampilkan notifikasi
        function fetchNotifications() {
            fetch('get_notifications.php')
                .then(response => response.json())
                .then(data => {
                    // Update badge count
                    if (data.unread_count > 0) {
                        countBadge.textContent = data.unread_count;
                        countBadge.style.display = 'flex';
                    } else {
                        countBadge.style.display = 'none';
                    }

                    // Buat panel notifikasi
                    let panelHTML = '<div class="notification-header"><h4>Notifikasi</h4></div><div class="notification-list">';
                    if (data.notifications.length > 0) {
                        data.notifications.forEach(notif => {
                            panelHTML += `
                                <div class="notification-item ${notif.unread ? 'unread' : ''}">
                                    <div class="notification-icon ${notif.type}">
                                        <i class="fas fa-info-circle"></i>
                                    </div>
                                    <div class="notification-content">
                                        <h5>${notif.title}</h5>
                                        <p>${notif.message}</p>
                                        <span class="notification-time">${notif.time}</span>
                                    </div>
                                </div>
                            `;
                        });
                    } else {
                        panelHTML += '<p style="text-align:center; padding: 20px;">Tidak ada notifikasi.</p>';
                    }
                    panelHTML += '</div>';
                    panel.innerHTML = panelHTML;
                })
                .catch(error => console.error('Gagal mengambil notifikasi:', error));
        }

        // Event listener untuk klik lonceng
        bell.addEventListener('click', (e) => {
            e.stopPropagation();
            panel.classList.toggle('show');

            // Jika panel terbuka dan ada notifikasi belum dibaca, tandai sudah dibaca
            if (panel.classList.contains('show') && parseInt(countBadge.textContent) > 0) {
                fetch('mark_notifications_read.php', { method: 'POST' })
                .then(() => {
                    // Hilangkan badge setelah 1 detik
                    setTimeout(() => {
                        countBadge.style.display = 'none';
                        countBadge.textContent = '0';
                    }, 1000);
                });
            }
        });

        // Tutup panel jika klik di luar
        window.addEventListener('click', () => {
            if (panel.classList.contains('show')) {
                panel.classList.remove('show');
            }
        });
        
        // Panggil fungsi saat halaman dimuat
        fetchNotifications();
        // Cek notifikasi baru setiap 1 menit
        setInterval(fetchNotifications, 60000);
    </script>
</body>
</html>