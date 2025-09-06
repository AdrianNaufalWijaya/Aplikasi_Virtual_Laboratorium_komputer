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

$sql_pending = "SELECT COUNT(e.enrollment_id) as total
                FROM enrollment e
                JOIN course c ON e.course_id = c.course_id
                WHERE e.status = 'pending' AND c.id_dosen = ?";
$stmt_pending = $pdo->prepare($sql_pending);
$stmt_pending->execute([$user_id]);
$pending_count = $stmt_pending->fetch(PDO::FETCH_ASSOC)['total'];

// Ambil daftar mata kuliah yang diampu oleh dosen ini
$sql = "SELECT course_id, nama_matkul, kode_matkul, semester FROM course WHERE id_dosen = ? AND status = 'active' ORDER BY nama_matkul ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Mata Kuliah</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="dosen.css">
    <style>
        .actions-cell {
            display: flex;
            align-items: center;
            gap: 8px; /* Memberi jarak antar tombol */
        }
    </style>
</head>
<body>
    <button class="mobile-menu-btn"><i class="fas fa-bars"></i></button>
    <?php include 'sidebar_dosen.html'; ?>
    <div class="main-content">
        <header class="header">
            <h1>Manajemen Mata Kuliah</h1>
            <p style="color: #6c757d;">Pilih mata kuliah untuk mengelola materi, tugas, dan peserta.</p>
        </header>

        <div class="section">
            <h2 class="section-title">Mata Kuliah Anda</h2>
            <div class="course-list-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Kode MK</th>
                            <th>Nama Mata Kuliah</th>
                            <th>Semester</th>
                            <th style="width: 320px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($courses)): ?>
                            <tr><td colspan="4" style="text-align: center;">Anda belum mengampu mata kuliah aktif.</td></tr>
                        <?php else: ?>
                            <?php foreach ($courses as $course): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($course['kode_matkul']); ?></td>
                                <td><?php echo htmlspecialchars($course['nama_matkul']); ?></td>
                                <td><?php echo htmlspecialchars($course['semester']); ?></td>
                                <td class="actions-cell">
                                    <a href="manajemen_materi.php?course_id=<?php echo $course['course_id']; ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-folder-open"></i> Kelola Materi
                                    </a>
                                    <a href="manajemen_tugas.php" class="btn btn-secondary btn-sm">
                                        <i class="fas fa-tasks"></i> Kelola Tugas
                                    </a>
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
        document.getElementById('nav-matkul').classList.add('active');
    </script>
</body>
</html>