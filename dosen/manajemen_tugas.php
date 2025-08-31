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

// --- LOGIKA UNTUK MENGUBAH STATUS ATAU MENGHAPUS TUGAS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // Aksi untuk mengubah status (published/draft)
    if ($_POST['action'] === 'change_status') {
        $assignment_id = $_POST['assignment_id'];
        $new_status = $_POST['new_status'];
        $sql_update = "UPDATE assignment SET status = ? WHERE assignment_id = ?";
        $stmt_update = $pdo->prepare($sql_update);
        $stmt_update->execute([$new_status, $assignment_id]);
    }

    // Aksi untuk menghapus tugas
    if ($_POST['action'] === 'delete_assignment') {
        $assignment_id = $_POST['assignment_id'];
        $sql_delete = "DELETE FROM assignment WHERE assignment_id = ?";
        $stmt_delete = $pdo->prepare($sql_delete);
        $stmt_delete->execute([$assignment_id]);
    }

    // Redirect untuk menghindari re-submit form
    header('Location: manajemen_tugas.php');
    exit();
}

// Ambil daftar tugas yang dibuat oleh dosen ini
$sql = "SELECT a.*, c.nama_matkul 
        FROM assignment a
        JOIN course c ON a.id_matkul = c.course_id
        WHERE c.id_dosen = ?
        ORDER BY a.due_date DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Manajemen Tugas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="dosen.css">
</head>
<body>
    <button class="mobile-menu-btn"><i class="fas fa-bars"></i></button>
    <?php include 'sidebar_dosen.html'; ?>
    <div class="main-content">
        <header class="header">
            <h1>Manajemen Tugas</h1>
            <p style="color: #6c757d;">Kelola dan monitor tugas yang diberikan kepada mahasiswa.</p>
        </header>

        <div class="section">
            <div class="section-header">
                <h2 class="section-title">Daftar Tugas</h2>
                <a href="buat_tugas.php" class="btn btn-primary" style="text-decoration: none;">
                    <i class="fas fa-plus"></i> Buat Tugas Baru
                </a>
            </div>
            
            <div class="tugas-grid">
                <?php if (empty($assignments)): ?>
                    <p>Anda belum membuat tugas apapun.</p>
                <?php else: ?>
                    <?php foreach ($assignments as $tugas): ?>
                    <div class="tugas-card">
                        <div class="tugas-card-header">
                            <span class="matkul-label"><?php echo htmlspecialchars($tugas['nama_matkul']); ?></span>
                            <?php if ($tugas['status'] == 'published'): ?>
                                <span class="status-badge status-aktif">Published</span>
                            <?php else: ?>
                                <span class="status-badge status-selesai">Draft</span>
                            <?php endif; ?>
                        </div>
                        <div class="tugas-card-body">
                            <h3 class="tugas-title"><?php echo htmlspecialchars($tugas['title']); ?></h3>
                            <p class="tugas-desc"><?php echo htmlspecialchars(substr($tugas['description'], 0, 100)) . '...'; ?></p>
                        </div>
                        <div class="tugas-card-footer">
                            <div class="deadline">
                                <i class="fas fa-calendar-alt"></i>
                                <span>Deadline: <?php echo date('d M Y, H:i', strtotime($tugas['due_date'])); ?></span>
                            </div>

                            <div class="tugas-actions">
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="change_status">
                                    <input type="hidden" name="assignment_id" value="<?php echo $tugas['assignment_id']; ?>">
                                    <?php if ($tugas['status'] == 'draft'): ?>
                                        <input type="hidden" name="new_status" value="published">
                                        <button type="submit" class="btn-icon btn-publish" title="Terbitkan Tugas"><i class="fas fa-upload"></i></button>
                                    <?php else: ?>
                                        <input type="hidden" name="new_status" value="draft">
                                        <button type="submit" class="btn-icon btn-draft" title="Jadikan Draf"><i class="fas fa-edit"></i></button>
                                    <?php endif; ?>
                                </form>

                                <a href="edit_tugas.php?id=<?php echo $tugas['assignment_id']; ?>" class="btn-icon btn-edit" title="Edit Tugas"><i class="fas fa-pencil-alt"></i></a>
                                
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Anda yakin ingin menghapus tugas ini?');">
                                    <input type="hidden" name="action" value="delete_assignment">
                                    <input type="hidden" name="assignment_id" value="<?php echo $tugas['assignment_id']; ?>">
                                    <button type="submit" class="btn-icon btn-delete" title="Hapus Tugas"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="dosen.js"></script>

    <script>
        document.getElementById('nav-tugas').classList.add('active');
    </script>
</body>
</html>