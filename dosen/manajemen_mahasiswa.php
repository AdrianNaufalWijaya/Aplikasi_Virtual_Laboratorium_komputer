<?php
session_start();
require_once '../koneksi.php';

// Cek jika user sudah login dan rolenya adalah dosen
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'dosen') {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$database = new Database();
$pdo = $database->getConnection();

// --- LOGIKA UNTUK APPROVE/REJECT ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enrollment_id'])) {
    $enrollment_id = $_POST['enrollment_id'];
    $action = $_POST['action'];
    $new_status = ($action === 'approve') ? 'approved' : 'rejected';
    
    $sql = "UPDATE enrollment SET status = ?, approved_by = ?, approved_at = NOW() WHERE enrollment_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$new_status, $user_id, $enrollment_id]);

    // Redirect untuk menghindari resubmit form
    header('Location: manajemen_mahasiswa.php');
    exit();
}

// --- FUNGSI UNTUK MENGAMBIL DATA PENDAFTARAN PENDING ---
$sql = "SELECT e.enrollment_id, u.full_name, c.nama_matkul, e.enrolled_at
        FROM enrollment e
        JOIN users u ON e.user_id = u.user_id
        JOIN course c ON e.course_id = c.course_id
        WHERE c.id_dosen = ? AND e.status = 'pending'
        ORDER BY e.enrolled_at ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$pending_enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Manajemen Mahasiswa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="dosen.css">
</head>
<body>
    <button class="mobile-menu-btn"><i class="fas fa-bars"></i></button>
    <?php include 'sidebar_dosen.html'; ?>

    <div class="main-content">
        <header class="header">
            <h1>Manajemen Mahasiswa</h1>
            <p style="color: #6c757d;">Setujui atau tolak mahasiswa yang mendaftar ke mata kuliah Anda.</p>
        </header>

        <div class="section">
            <h2 class="section-title">Permintaan Pendaftaran Baru</h2>
            <div style="overflow-x: auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nama Mahasiswa</th>
                            <th>Mata Kuliah</th>
                            <th>Tanggal Daftar</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pending_enrollments)): ?>
                            <tr>
                                <td colspan="4" style="text-align: center;">Tidak ada permintaan pendaftaran baru.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pending_enrollments as $enroll): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($enroll['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($enroll['nama_matkul']); ?></td>
                                <td><?php echo date('d M Y, H:i', strtotime($enroll['enrolled_at'])); ?></td>
                                <td class="action-buttons">
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="enrollment_id" value="<?php echo $enroll['enrollment_id']; ?>">
                                        <button type="submit" name="action" value="approve" class="btn btn-approve" title="Setujui"><i class="fas fa-check"></i> Setujui</button>
                                        <button type="submit" name="action" value="reject" class="btn btn-reject" title="Tolak" onclick="return confirm('Anda yakin ingin menolak mahasiswa ini?')"><i class="fas fa-times"></i> Tolak</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="dosen.js"></script>

    <script>
        // Menandai menu aktif di sidebar
        document.getElementById('nav-mahasiswa').classList.add('active');
    </script>
</body>
</html>