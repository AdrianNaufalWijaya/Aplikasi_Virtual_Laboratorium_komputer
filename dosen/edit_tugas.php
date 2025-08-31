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

// Ambil ID tugas dari URL
$assignment_id = $_GET['id'] ?? 0;
if (!$assignment_id) {
    header('Location: manajemen_tugas.php');
    exit();
}

// Logika untuk UPDATE data saat form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_id = $_POST['course_id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $due_date = $_POST['due_date'];
    $status = $_POST['status'];
    $posted_assignment_id = $_POST['assignment_id'];

    // Validasi sederhana
    if (empty($course_id) || empty($title) || empty($due_date) || empty($status)) {
        $error_message = 'Semua field wajib diisi.';
    } else {
        try {
            $sql_update = "UPDATE assignment SET id_matkul = ?, title = ?, description = ?, due_date = ?, status = ? WHERE assignment_id = ?";
            $stmt_update = $pdo->prepare($sql_update);
            
            if ($stmt_update->execute([$course_id, $title, $description, $due_date, $status, $posted_assignment_id])) {
                $success_message = 'Tugas berhasil diperbarui! Anda akan diarahkan kembali.';
                header("refresh:2;url=manajemen_tugas.php");
            } else {
                $error_message = 'Gagal memperbarui tugas.';
            }
        } catch (PDOException $e) {
            $error_message = 'Terjadi error pada database: ' . $e->getMessage();
        }
    }
}


// Ambil data tugas yang akan diedit dari database
$sql_tugas = "SELECT * FROM assignment WHERE assignment_id = ?";
$stmt_tugas = $pdo->prepare($sql_tugas);
$stmt_tugas->execute([$assignment_id]);
$tugas = $stmt_tugas->fetch(PDO::FETCH_ASSOC);

// Jika tugas tidak ditemukan, kembalikan ke halaman manajemen
if (!$tugas) {
    header('Location: manajemen_tugas.php');
    exit();
}

// Ambil daftar mata kuliah dosen untuk dropdown
$sql_courses = "SELECT course_id, nama_matkul FROM course WHERE id_dosen = ?";
$stmt_courses = $pdo->prepare($sql_courses);
$stmt_courses->execute([$user_id]);
$courses = $stmt_courses->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Tugas</title>
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
            <h1>Edit Tugas</h1>
            <p style="color: #6c757d;">Perbarui detail tugas di bawah ini.</p>
        </header>

        <div class="section">
            <h2 class="section-title">Formulir Edit Tugas</h2>

            <?php if ($success_message): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            <?php if ($error_message): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <?php if (!$success_message): ?>
            <form action="edit_tugas.php?id=<?php echo $assignment_id; ?>" method="POST">
                <input type="hidden" name="assignment_id" value="<?php echo $tugas['assignment_id']; ?>">
                
                <div class="form-group">
                    <label for="course_id" class="form-label">Mata Kuliah</label>
                    <select name="course_id" id="course_id" class="form-control" required>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?php echo $course['course_id']; ?>" <?php echo ($course['course_id'] == $tugas['id_matkul']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($course['nama_matkul']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="title" class="form-label">Judul Tugas</label>
                    <input type="text" name="title" id="title" class="form-control" value="<?php echo htmlspecialchars($tugas['title']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="description" class="form-label">Deskripsi</label>
                    <textarea name="description" id="description" rows="5" class="form-control"><?php echo htmlspecialchars($tugas['description']); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="due_date" class="form-label">Tanggal & Waktu Deadline</label>
                    <input type="datetime-local" name="due_date" id="due_date" class="form-control" value="<?php echo date('Y-m-d\TH:i', strtotime($tugas['due_date'])); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="status" class="form-label">Status Tugas</label>
                    <select name="status" id="status" class="form-control" required>
                        <option value="draft" <?php echo ($tugas['status'] == 'draft') ? 'selected' : ''; ?>>Simpan sebagai Draf</option>
                        <option value="published" <?php echo ($tugas['status'] == 'published') ? 'selected' : ''; ?>>Terbitkan</option>
                    </select>
                </div>

                <div class="btn-container">
                    <a href="manajemen_tugas.php" class="btn btn-secondary" style="text-decoration:none;">Batal</a>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <script src="dosen.js"></script>

</body>
</html>