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

$error_message = '';
$success_message = '';

// Ambil daftar mata kuliah yang diampu oleh dosen ini
$sql_courses = "SELECT course_id, nama_matkul FROM course WHERE id_dosen = ?";
$stmt_courses = $pdo->prepare($sql_courses);
$stmt_courses->execute([$user_id]);
$courses = $stmt_courses->fetchAll(PDO::FETCH_ASSOC);

// Logika untuk menangani form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_id = $_POST['course_id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $due_date = $_POST['due_date'];
    $status = $_POST['status']; // <-- VARIABEL BARU DIAMBIL DARI FORM

    //jika checkbox dicentang, nilai 1. jika tidak, nilai 0
    $allow_edit = isset($_POST['allow_edit']) ? 1 : 0;

    if (empty($course_id) || empty($title) || empty($due_date) || empty($status)) {
        $error_message = 'Semua field wajib diisi.';
    } else {
        try {

            $sql_insert = "INSERT INTO assignment (id_matkul, title, description, due_date, status, allow_edit) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt_insert = $pdo->prepare($sql_insert);
            // Execute dengan menambahkan $allow_edit
             if ($stmt_insert->execute([$course_id, $title, $description, $due_date, $status, $allow_edit])) {
            
                 $success_message = 'Tugas berhasil disimpan! Anda akan diarahkan kembali.';
                header("refresh:2;url=manajemen_tugas.php");
            } else {
                $error_message = 'Gagal menyimpan tugas ke database.';
            }
        } catch (PDOException $e) {
            $error_message = 'Terjadi error pada database: ' . $e->getMessage();
        }
    }
}

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
    <title>Buat Tugas Baru</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="dosen.css">
    <style>
        .form-control { width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 5px; font-size: 14px; margin-top: 5px; }
        .form-group { margin-bottom: 20px; }
        .form-label { font-weight: 500; }
        .btn-container { text-align: right; }
    </style>
</head>
<body>
    <button class="mobile-menu-btn"><i class="fas fa-bars"></i></button>
    <?php include 'sidebar_dosen.html'; ?>

    <div class="main-content">
        <header class="header">
            <h1>Buat Tugas Baru</h1>
            <p style="color: #6c757d;">Isi detail tugas yang akan diberikan kepada mahasiswa.</p>
        </header>

        <div class="section">
            <h2 class="section-title">Formulir Tugas</h2>

            <?php if ($success_message): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            <?php if ($error_message): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <?php if (!$success_message): ?>
            <form action="buat_tugas.php" method="POST">
                <div class="form-group">
                    <label for="course_id" class="form-label">Mata Kuliah</label>
                    <select name="course_id" id="course_id" class="form-control" required>
                        <option value="">-- Pilih Mata Kuliah --</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?php echo $course['course_id']; ?>"><?php echo htmlspecialchars($course['nama_matkul']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="title" class="form-label">Judul Tugas</label>
                    <input type="text" name="title" id="title" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="description" class="form-label">Deskripsi</label>
                    <textarea name="description" id="description" rows="5" class="form-control"></textarea>
                </div>

                <div class="form-group">
                    <label for="due_date" class="form-label">Tanggal & Waktu Deadline</label>
                    <input type="datetime-local" name="due_date" id="due_date" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="status" class="form-label">Status Tugas</label>
                    <select name="status" id="status" class="form-control" required>
                        <option value="draft">Simpan sebagai Draf (Tidak terlihat oleh mahasiswa)</option>
                        <option value="published">Langsung Terbitkan (Terlihat oleh mahasiswa)</option>
                    </select>
                </div>
                <div class="btn-container">
                    <a href="manajemen_tugas.php" class="btn btn-secondary" style="text-decoration:none;">Batal</a>
                    <button type="submit" class="btn btn-primary">Simpan Tugas</button>
                </div>

                <div class="form-group">
                    <div style="display: flex; align-items: center; gap: 10px; margin-top: 10px;">
                        <input type="checkbox" name="allow_edit" id="allow_edit" value="1" style="width: 18px; height: 18px;">
                        <label for="allow_edit" style="font-weight: normal; margin-bottom: 0;">
                            Izinkan mahasiswa untuk mengedit tugas yang sudah dikumpulkan?
                        </label>
                    </div>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <script src="dosen.js"></script>
    <script>
        document.getElementById('nav-tugas').classList.add('active');
        document.getElementById('nav-dashboard').classList.remove('active');
    </script>
</body>
</html>