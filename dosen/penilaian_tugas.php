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

// Ambil semua submission dari tugas yang dimiliki dosen ini
$sql = "SELECT s.submission_id, s.tanggal_dikumpulkan, s.status as submission_status, s.score,
               u.full_name as student_name,
               a.title as assignment_title,
               c.nama_matkul
        FROM submission s
        JOIN users u ON s.id_mahasiswa = u.user_id
        JOIN assignment a ON s.id_tugas = a.assignment_id
        JOIN course c ON a.id_matkul = c.course_id
        WHERE c.id_dosen = ?
        ORDER BY s.tanggal_dikumpulkan DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Penilaian Tugas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="dosen.css">
</head>
<body>
    <?php include 'sidebar_dosen.html'; ?>

    <div class="main-content">
        <header class="header">
            <h1>Penilaian Tugas</h1>
            <p style="color: #6c757d;">Lihat dan nilai tugas yang telah dikumpulkan oleh mahasiswa.</p>
        </header>

        <div class="section">
            <h2 class="section-title">Tugas Terkumpul</h2>
             <div style="overflow-x: auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Mahasiswa</th>
                            <th>Tugas</th>
                            <th>Mata Kuliah</th>
                            <th>Waktu Pengumpulan</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($submissions)): ?>
                            <tr><td colspan="6" style="text-align: center;">Belum ada tugas yang dikumpulkan.</td></tr>
                        <?php else: ?>
                            <?php foreach ($submissions as $sub): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($sub['student_name']); ?></td>
                                    <td><?php echo htmlspecialchars($sub['assignment_title']); ?></td>
                                    <td><?php echo htmlspecialchars($sub['nama_matkul']); ?></td>
                                    <td><?php echo date('d M Y, H:i', strtotime($sub['tanggal_dikumpulkan'])); ?></td>
                                    <td>
                                        <?php if ($sub['submission_status'] == 'submitted'): ?>
                                            <span class="status-badge status-belum-dinilai">Belum Dinilai</span>
                                        <?php elseif($sub['submission_status'] == 'graded'): ?>
                                            <span class="status-badge status-dinilai">Sudah Dinilai (<?php echo $sub['score']; ?>)</span>
                                        <?php else: ?>
                                            <span class="status-badge status-terlambat">Terlambat</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="nilai_tugas.php?id=<?php echo $sub['submission_id']; ?>" class="btn btn-primary btn-sm">Nilai</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script>
        document.getElementById('nav-penilaian').classList.add('active');
        document.getElementById('nav-dashboard').classList.remove('active');
    </script>
</body>
</html>