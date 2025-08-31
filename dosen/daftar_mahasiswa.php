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

// 1. Ambil dulu semua mata kuliah yang diampu oleh dosen ini
$sql_courses = "SELECT course_id, nama_matkul, kode_matkul FROM course WHERE id_dosen = ? AND status = 'active'";
$stmt_courses = $pdo->prepare($sql_courses);
$stmt_courses->execute([$user_id]);
$courses = $stmt_courses->fetchAll(PDO::FETCH_ASSOC);

// 2. Siapkan array untuk menampung data mahasiswa per mata kuliah
$courses_with_students = [];
foreach ($courses as $course) {
    // 3. Untuk setiap mata kuliah, ambil daftar mahasiswa yang sudah disetujui
    $sql_students = "SELECT u.full_name, u.email, e.enrolled_at
                     FROM users u
                     JOIN enrollment e ON u.user_id = e.user_id
                     WHERE e.course_id = ? AND e.status = 'approved'
                     ORDER BY u.full_name ASC";
    $stmt_students = $pdo->prepare($sql_students);
    $stmt_students->execute([$course['course_id']]);
    $students = $stmt_students->fetchAll(PDO::FETCH_ASSOC);
    
    // Masukkan data ke array utama
    $course['students'] = $students;
    $courses_with_students[] = $course;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Mahasiswa per Mata Kuliah</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="dosen.css">
</head>
<body>
    <button class="mobile-menu-btn"><i class="fas fa-bars"></i></button>
    <?php include 'sidebar_dosen.html'; ?>
    <div class="main-content">
        <header class="header">
            <h1>Daftar Mahasiswa</h1>
            <p style="color: #6c757d;">Berikut adalah daftar mahasiswa yang terdaftar di setiap mata kuliah yang Anda ampu.</p>
        </header>

        <?php if (empty($courses_with_students)): ?>
            <div class="section">
                <p style="text-align: center;">Anda tidak mengampu mata kuliah aktif saat ini.</p>
            </div>
        <?php else: ?>
            <?php foreach ($courses_with_students as $course): ?>
                <div class="section">
                    <h2 class="section-title"><?php echo htmlspecialchars($course['nama_matkul']); ?> (<?php echo htmlspecialchars($course['kode_matkul']); ?>)</h2>
                    
                    <?php if (empty($course['students'])): ?>
                        <p>Belum ada mahasiswa yang terdaftar di mata kuliah ini.</p>
                    <?php else: ?>
                        <div style="overflow-x: auto;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th style="width: 50px;">No.</th>
                                        <th>Nama Mahasiswa</th>
                                        <th>Email</th>
                                        <th>Tanggal Terdaftar</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($course['students'] as $index => $student): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($student['email']); ?></td>
                                        <td><?php echo date('d M Y', strtotime($student['enrolled_at'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script src="dosen.js"></script>

</body>
</html>