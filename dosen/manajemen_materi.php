<?php
session_start();
require_once '../koneksi.php';

// Cek jika user sudah login dan rolenya adalah dosen
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'dosen') {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;

if ($course_id === 0) {
    header('Location: manajemen_matkul.php');
    exit();
}

$database = new Database();
$pdo = $database->getConnection();

// --- Logika untuk Tambah Materi ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_material') {
    $title = $_POST['title'];
    $description = $_POST['description'];

    if (!empty($title) && isset($_FILES['material_file']) && $_FILES['material_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/materials/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $file = $_FILES['material_file'];
        $file_name = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', basename($file['name']));
        $file_path = $upload_dir . $file_name;
        $file_type = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (move_uploaded_file($file['tmp_name'], $file_path)) {
            $sql = "INSERT INTO course_materials (course_id, title, description, file_path, file_type) VALUES (?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$course_id, $title, $description, $file_path, $file_type]);
        }
    }
    header("Location: manajemen_materi.php?course_id=$course_id");
    exit();
}

// --- Logika untuk Hapus Materi ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_material') {
    $material_id = $_POST['material_id'];
    
    // Ambil path file untuk dihapus dari server
    $sql_path = "SELECT file_path FROM course_materials WHERE material_id = ?";
    $stmt_path = $pdo->prepare($sql_path);
    $stmt_path->execute([$material_id]);
    $result = $stmt_path->fetch(PDO::FETCH_ASSOC);

    if ($result && file_exists($result['file_path'])) {
        unlink($result['file_path']); // Hapus file fisik
    }

    // Hapus record dari database
    $sql = "DELETE FROM course_materials WHERE material_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$material_id]);

    header("Location: manajemen_materi.php?course_id=$course_id");
    exit();
}

// Ambil detail mata kuliah
$sql_course = "SELECT nama_matkul FROM course WHERE course_id = ? AND id_dosen = ?";
$stmt_course = $pdo->prepare($sql_course);
$stmt_course->execute([$course_id, $user_id]);
$course = $stmt_course->fetch(PDO::FETCH_ASSOC);

if (!$course) { // Security check: pastikan dosen hanya bisa akses matkulnya sendiri
    header('Location: dashboard_dosen.php');
    exit();
}

$sql_pending = "SELECT COUNT(e.enrollment_id) as total
                FROM enrollment e
                JOIN course c ON e.course_id = c.course_id
                WHERE e.status = 'pending' AND c.id_dosen = ?";
$stmt_pending = $pdo->prepare($sql_pending);
$stmt_pending->execute([$user_id]);
$pending_count = $stmt_pending->fetch(PDO::FETCH_ASSOC)['total'];

// Ambil daftar materi yang ada
$sql_materials = "SELECT * FROM course_materials WHERE course_id = ? ORDER BY uploaded_at DESC";
$stmt_materials = $pdo->prepare($sql_materials);
$stmt_materials->execute([$course_id]);
$materials = $stmt_materials->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Materi</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="dosen.css">
</head>
<body>
    <button class="mobile-menu-btn"><i class="fas fa-bars"></i></button>
    <?php include 'sidebar_dosen.html'; ?>

    <div class="main-content">
        <header class="header">
            <h1>Manajemen Materi: <?php echo htmlspecialchars($course['nama_matkul']); ?></h1>
            <p style="color: #6c757d;">Tambah, edit, atau hapus materi untuk mata kuliah ini.</p>
        </header>

        <a href="manajemen_matakuliah.php" class="btn btn-secondary" style="margin-bottom: 20px; text-decoration:none;"><i class="fas fa-arrow-left"></i> Kembali</a>

        <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 20px;">
            <div class="section">
                <h2 class="section-title">Tambah Materi Baru</h2>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add_material">
                    <div class="form-group">
                        <label for="title" class="form-label">Judul Materi</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="description" class="form-label">Deskripsi Singkat</label>
                        <textarea name="description" rows="3" class="form-control"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="material_file" class="form-label">File Materi</label>
                        <input type="file" name="material_file" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%;"><i class="fas fa-plus"></i> Tambah</button>
                </form>
            </div>

            <div class="section">
                <h2 class="section-title">Daftar Materi</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Judul</th>
                            <th>Deskripsi</th>
                            <th>File</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($materials)): ?>
                            <tr><td colspan="4" style="text-align: center;">Belum ada materi yang diunggah.</td></tr>
                        <?php else: ?>
                            <?php foreach ($materials as $material): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($material['title']); ?></td>
                                <td><?php echo htmlspecialchars($material['description']); ?></td>
                                <td>
                                    <a href="../<?php echo htmlspecialchars($material['file_path']); ?>" target="_blank">
                                        <?php echo htmlspecialchars(basename($material['file_path'])); ?>
                                    </a>
                                </td>
                                <td>
                                    <form method="POST" onsubmit="return confirm('Yakin ingin menghapus materi ini?');" style="display:inline;">
                                        <input type="hidden" name="action" value="delete_material">
                                        <input type="hidden" name="material_id" value="<?php echo $material['material_id']; ?>">
                                        <button type="submit" class="btn btn-reject btn-sm" title="Hapus"><i class="fas fa-trash"></i></button>
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
    
</body>
</html>