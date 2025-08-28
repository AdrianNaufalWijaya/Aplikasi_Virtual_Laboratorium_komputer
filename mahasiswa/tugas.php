<?php
session_start();
require_once '../koneksi.php';

// Membuat koneksi database
$database = new Database();
$pdo = $database->getConnection();

// Memeriksa apakah pengguna sudah login dan merupakan mahasiswa
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mahasiswa') {
    header('Location: ../login.php');
    exit();
}

// Menangani proses logout
if (isset($_GET['logout'])) {
    // Mencatat aktivitas logout
    try {
        $log_stmt = $pdo->prepare("INSERT INTO activity_log (user_id, action, entity_type, entity_id, ip_address, user_agent) VALUES (?, 'logout', 'user', ?, ?, ?)");
        $log_stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]);
    } catch (Exception $e) {
        // Abaikan jika pencatatan gagal
    }
    
    session_destroy();
    header('Location: login.php?logout=success');
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$full_name = $_SESSION['full_name'];

// Menangani unggahan file tugas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_assignment') {
    $assignment_id = intval($_POST['assignment_id']);
    $upload_dir = '../uploads/submissions/';

    // Membuat direktori unggahan jika belum ada
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    if (isset($_FILES['assignment_file']) && $_FILES['assignment_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['assignment_file'];
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['pdf', 'doc', 'docx', 'zip', 'rar', 'jpg', 'txt'];
        
        if (in_array($file_extension, $allowed_extensions)) {
            // Membuat nama file yang unik untuk menghindari konflik    
            $file_name = time() . '_' . $user_id . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', basename($file['name']));
            $file_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($file['tmp_name'], $file_path)) {
                // Memeriksa apakah data pengumpulan sudah ada
                $check_sql = "SELECT submission_id FROM submission WHERE id_tugas = ? AND id_mahasiswa = ?";
                $check_stmt = $pdo->prepare($check_sql);
                $check_stmt->execute([$assignment_id, $user_id]);
                
                if ($check_stmt->rowCount() > 0) {
                    // Jika sudah ada, perbarui data (UPDATE)
                    $update_sql = "UPDATE submission SET file_path = ?, tanggal_dikumpulkan = NOW(), status = 'submitted' WHERE id_tugas = ? AND id_mahasiswa = ?";
                    $update_stmt = $pdo->prepare($update_sql);
                    if ($update_stmt->execute([$file_path, $assignment_id, $user_id])) {
                        $success_message = "Jawaban tugas berhasil diperbarui!";
                    } else {
                        $error_message = "Gagal memperbarui jawaban tugas.";
                    }
                } else {
                    // Jika belum ada, buat data baru (INSERT)
                    $insert_sql = "INSERT INTO submission (id_tugas, id_mahasiswa, file_path, tanggal_dikumpulkan, status) VALUES (?, ?, ?, NOW(), 'submitted')";
                    $insert_stmt = $pdo->prepare($insert_sql);
                    if ($insert_stmt->execute([$assignment_id, $user_id, $file_path])) {
                        $success_message = "Tugas berhasil dikumpulkan!";
                    } else {
                        $error_message = "Gagal mengumpulkan tugas.";
                    }
                }
            } else {
                $error_message = "Gagal memindahkan file yang diunggah.";
            }
        } else {
            $error_message = "Tipe file tidak diizinkan. Gunakan: " . implode(', ', $allowed_extensions);
        }
    } else {
        $error_message = "Tidak ada file yang dipilih atau terjadi kesalahan saat mengunggah.";
    }
}

// Fungsi untuk mengambil semua tugas dari mata kuliah yang diikuti
function getAllAssignments($pdo, $user_id) {
    $sql = "
         SELECT 
            a.assignment_id, a.title, a.description, a.due_date, a.max_score, a.attachment_path,
            a.status as assignment_status, a.allow_edit,
            c.nama_matkul, c.kode_matkul, c.course_id,
            s.submission_id, s.file_path, s.tanggal_dikumpulkan, s.score, s.feedback,
            s.status as submission_status, u.full_name as dosen_name
        FROM assignment a
        INNER JOIN course c ON a.id_matkul = c.course_id
        INNER JOIN enrollment e ON c.course_id = e.course_id
        INNER JOIN users u ON c.id_dosen = u.user_id
        LEFT JOIN submission s ON a.assignment_id = s.id_tugas AND s.id_mahasiswa = ?
        WHERE e.user_id = ? AND e.status = 'approved' AND a.status = 'published'
        ORDER BY a.due_date DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id, $user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fungsi untuk mengelompokkan tugas berdasarkan status
function getAssignmentsByStatus($assignments) {
    $now = new DateTime();
    $categories = ['upcoming' => [], 'submitted' => [], 'graded' => [], 'overdue' => []];
    
    foreach ($assignments as $assignment) {
        $due_date = new DateTime($assignment['due_date']);
        if ($assignment['submission_status'] === 'graded') {
            $categories['graded'][] = $assignment;
        } elseif ($assignment['submission_id']) {
            $categories['submitted'][] = $assignment;
        } elseif ($due_date < $now) {
            $categories['overdue'][] = $assignment;
        } else {
            $categories['upcoming'][] = $assignment;
        }
    }
    return $categories;
}

// Fungsi untuk memformat tenggat waktu
function formatDeadline($deadline) {
    $now = new DateTime();
    $due = new DateTime($deadline);
    $diff = $now->diff($due);
    
    if ($due < $now) {
        return "Terlambat " . ($diff->days > 0 ? $diff->days . " hari" : "beberapa jam");
    }
    
    $totalHours = ($diff->days * 24) + $diff->h;
    if ($totalHours <= 48) {
        return $totalHours . " jam lagi";
    } else {
        return $diff->days . " hari lagi";
    }
}

// Fungsi untuk mendapatkan lencana status tugas
function getStatusBadge($assignment) {
    $now = new DateTime();
    $due_date = new DateTime($assignment['due_date']);
    
    if ($assignment['submission_status'] === 'graded') {
        return '<span class="status-badge graded"><i class="fas fa-check-circle"></i> Dinilai</span>';
    } elseif ($assignment['submission_id']) {
        return '<span class="status-badge submitted"><i class="fas fa-upload"></i> Dikumpulkan</span>';
    } elseif ($due_date < $now) {
        return '<span class="status-badge overdue"><i class="fas fa-exclamation-triangle"></i> Terlambat</span>';
    } else {
        return '<span class="status-badge pending-badge"><i class="fas fa-clock"></i> Belum Dikumpulkan</span>';
    }
}

// Mengambil dan memproses data
$allAssignments = getAllAssignments($pdo, $user_id);
$assignmentsByStatus = getAssignmentsByStatus($allAssignments);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tugas - Dashboard Mahasiswa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="mahasiswa.css">
    <style>
        .deadline-warning { color: #dc3545; font-weight: 500; }
        .score-display { padding: 6px 12px; font-size: 16px; margin: 0; color: #155724; background-color: #d4edda; border-radius: 8px; font-weight: 600; }
        .submission-info.graded { border-left-color: #007bff; }

        /* CSS untuk Modal (Jendela Pop-up) */
.modal {
    display: none; /* Sembunyi secara default */
    position: fixed; 
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.5); /* Latar belakang gelap transparan */
}

.modal-content {
    background-color: #fefefe;
    margin: 15% auto; /* Posisikan di tengah */
    padding: 25px;
    border: 1px solid #888;
    width: 80%;
    max-width: 500px;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 15px;
    border-bottom: 1px solid #e5e5e5;
}

.modal-header h2 {
    margin: 0;
    font-size: 1.2rem;
}

.close-btn {
    color: #aaa;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.close-btn:hover,
.close-btn:focus {
    color: black;
    text-decoration: none;
}

.modal-body {
    padding-top: 20px;
}

.modal-body .form-group {
    margin-bottom: 15px;
}
.modal-body input[type="file"] {
    width: 100%;
    padding: 10px;
    border: 1px dashed #ccc;
    border-radius: 5px;
}
    </style>
</head>
<body>
    <button class="mobile-menu-btn"><i class="fas fa-bars"></i></button>

    <?php include 'sidebar_mahasiswa.html'; ?>
    
    <div class="main-content">
        <div class="top-nav">
            <div class="nav-left">
                <h1 class="page-title">Kelola Tugas</h1>
                <div class="breadcrumb">
                    <a href="dashboard_mahasiswa.php">Home</a>
                    <i class="fas fa-chevron-right"></i>
                    <span>Tugas</span>
                </div>
            </div>
            <div class="nav-right">
                <div class="user-menu">
                    <div class="user-avatar"><?php echo strtoupper(substr($full_name, 0, 1)); ?></div>
                    <div class="dropdown-menu" id="userDropdown">
                        <div class="dropdown-header">
                            <div class="dropdown-user-info">
                                <div class="dropdown-user-name"><?php echo htmlspecialchars($full_name); ?></div>
                                <div class="dropdown-user-role">Mahasiswa</div>
                            </div>
                        </div>
                         <a href="profile.php" class="dropdown-item">
                            <i class="fas fa-user"></i>
                            <span>Profile</span>
                        </a>
                        <a href="../logout.php" class="dropdown-item logout" onclick="return confirm('Yakin ingin logout?')">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Log out</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="content-area">
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header"><div class="stat-icon"><i class="fas fa-tasks"></i></div></div>
                    <div class="stat-number"><?php echo count($assignmentsByStatus['upcoming']); ?></div>
                    <div class="stat-label">Tugas Aktif</div>
                </div>
                <div class="stat-card">
                    <div class="stat-header"><div class="stat-icon"><i class="fas fa-upload"></i></div></div>
                    <div class="stat-number"><?php echo count($assignmentsByStatus['submitted']); ?></div>
                    <div class="stat-label">Dikumpulkan</div>
                </div>
                <div class="stat-card">
                     <div class="stat-header"><div class="stat-icon"><i class="fas fa-check-circle"></i></div></div>
                    <div class="stat-number"><?php echo count($assignmentsByStatus['graded']); ?></div>
                    <div class="stat-label">Sudah Dinilai</div>
                </div>
                <div class="stat-card">
                    <div class="stat-header"><div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div></div>
                    <div class="stat-number"><?php echo count($assignmentsByStatus['overdue']); ?></div>
                    <div class="stat-label">Terlambat</div>
                </div>
            </div>

            <div class="tabs-container">
                <div class="tabs">
                    <button class="tab active" onclick="switchTab(event, 'upcoming')"><i class="fas fa-clock"></i> Aktif (<?php echo count($assignmentsByStatus['upcoming']); ?>)</button>
                    <button class="tab" onclick="switchTab(event, 'submitted')"><i class="fas fa-upload"></i> Dikumpulkan (<?php echo count($assignmentsByStatus['submitted']); ?>)</button>
                    <button class="tab" onclick="switchTab(event, 'graded')"><i class="fas fa-star"></i> Dinilai (<?php echo count($assignmentsByStatus['graded']); ?>)</button>
                    <button class="tab" onclick="switchTab(event, 'overdue')"><i class="fas fa-exclamation-triangle"></i> Terlambat (<?php echo count($assignmentsByStatus['overdue']); ?>)</button>
                </div>

                <div id="upcoming" class="tab-content active">
                    <?php if (empty($assignmentsByStatus['upcoming'])): ?>
                        <div class="empty-state"><i class="fas fa-clipboard-check"></i><h3>Tidak Ada Tugas Aktif</h3><p>Semua tugas sudah dikumpulkan atau belum ada tugas baru.</p></div>
                    <?php else: ?>
                        <div class="assignment-grid">
                        <?php foreach ($assignmentsByStatus['upcoming'] as $assignment): ?>
                            <div class="assignment-item">
                                <div class="assignment-header">
                                    <div class="assignment-info">
                                        <h3 class="assignment-title"><?php echo htmlspecialchars($assignment['title']); ?></h3>
                                        <div class="assignment-course"><i class="fas fa-book"></i><?php echo htmlspecialchars($assignment['nama_matkul'] . ' - ' . $assignment['kode_matkul']); ?></div>
                                        <div class="info-item"><i class="fas fa-chalkboard-teacher"></i><?php echo htmlspecialchars($assignment['dosen_name']); ?></div>
                                    </div>
                                    <?php echo getStatusBadge($assignment); ?>
                                </div>
                                <?php if ($assignment['description']): ?><div class="assignment-description"><?php echo nl2br(htmlspecialchars($assignment['description'])); ?></div><?php endif; ?>
                                <div class="assignment-meta mt-15">
                                    <div class="meta-item"><i class="fas fa-calendar-alt"></i>Deadline: <?php echo date('d M Y H:i', strtotime($assignment['due_date'])); ?></div>
                                    <div class="meta-item <?php echo (strtotime($assignment['due_date']) - time() <= 86400*2) ? 'deadline-warning' : ''; ?>"><i class="fas fa-hourglass-half"></i><?php echo formatDeadline($assignment['due_date']); ?></div>
                                    <div class="meta-item"><i class="fas fa-trophy"></i>Nilai Maksimal: <?php echo $assignment['max_score']; ?></div>
                                </div>
                                <div class="assignment-actions mt-15">
                                    <a href="kumpul_tugas.php?id=<?php echo $assignment['assignment_id']; ?>" class="btn btn-primary"><i class="fas fa-upload"></i> Kumpulkan</a>
                                    <?php if ($assignment['attachment_path']): ?><a href="<?php echo htmlspecialchars($assignment['attachment_path']); ?>" class="btn btn-secondary" target="_blank"><i class="fas fa-download"></i> Unduh Soal</a><?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div id="submitted" class="tab-content">
                    <?php if (empty($assignmentsByStatus['submitted'])): ?>
                        <div class="empty-state"><i class="fas fa-folder-open"></i><h3>Belum Ada Tugas</h3><p>Tugas yang sudah Anda kumpulkan akan muncul di sini.</p></div>
                    <?php else: ?>
                        <div class="assignment-grid">
                        <?php foreach ($assignmentsByStatus['submitted'] as $assignment): ?>
                             <div class="assignment-item">
                                <div class="assignment-header">
                                    <div class="assignment-info">
                                        <h3 class="assignment-title"><?php echo htmlspecialchars($assignment['title']); ?></h3>
                                        <div class="assignment-course"><i class="fas fa-book"></i><?php echo htmlspecialchars($assignment['nama_matkul']); ?></div>
                                    </div>
                                    <?php echo getStatusBadge($assignment); ?>
                                </div>
                                <div class="submission-info mt-15">
                                     <div class="d-flex justify-content-between align-items-center">
                                        <h4 class="mb-0" style="font-size: 14px; font-weight: 600;"><i class="fas fa-hourglass-half"></i> Menunggu Penilaian</h4>
                                        <small class="text-muted">Dikumpulkan: <?php echo date('d M Y H:i', strtotime($assignment['tanggal_dikumpulkan'])); ?></small>
                                     </div>
                                </div>
                                <div class="assignment-actions mt-15">
                                    <?php if ($assignment['allow_edit'] == 1): ?>
                                        <button class="btn btn-warning" onclick="openUploadModal(<?php echo $assignment['assignment_id']; ?>, '<?php echo htmlspecialchars(addslashes($assignment['title'])); ?>')"><i class="fas fa-edit"></i> Perbarui</button>
                                    <?php endif; ?>
                                    
                                    <a href="<?php echo htmlspecialchars($assignment['file_path']); ?>" class="btn btn-info" target="_blank"><i class="fas fa-eye"></i> Lihat File</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div id="graded" class="tab-content">
                     <?php if (empty($assignmentsByStatus['graded'])): ?>
                        <div class="empty-state"><i class="fas fa-marker"></i><h3>Belum Ada Nilai</h3><p>Tugas yang sudah dinilai oleh dosen akan muncul di sini.</p></div>
                    <?php else: ?>
                        <div class="assignment-grid">
                        <?php foreach ($assignmentsByStatus['graded'] as $assignment): ?>
                            <div class="assignment-item">
                                <div class="assignment-header">
                                    <div class="assignment-info">
                                        <h3 class="assignment-title"><?php echo htmlspecialchars($assignment['title']); ?></h3>
                                        <div class="assignment-course"><i class="fas fa-book"></i><?php echo htmlspecialchars($assignment['nama_matkul']); ?></div>
                                    </div>
                                    <div class="score-display">
                                        <?php echo $assignment['score']; ?> / <?php echo $assignment['max_score']; ?>
                                    </div>
                                </div>
                                <div class="submission-info graded mt-15">
                                    <h4 class="mb-10" style="font-size: 14px; font-weight: 600;"><i class="fas fa-comment-dots"></i> Feedback Dosen</h4>
                                    <p style="font-size: 14px; color: #333;"><?php echo $assignment['feedback'] ? nl2br(htmlspecialchars($assignment['feedback'])) : '<i>Tidak ada feedback.</i>'; ?></p>
                                </div>
                                <div class="assignment-actions mt-15">
                                    <a href="<?php echo htmlspecialchars($assignment['file_path']); ?>" class="btn btn-info" target="_blank"><i class="fas fa-eye"></i> Lihat File</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div id="overdue" class="tab-content">
                    <?php if (empty($assignmentsByStatus['overdue'])): ?>
                        <div class="empty-state"><i class="fas fa-check-double"></i><h3>Tidak Ada Tugas Terlambat</h3><p>Kerja bagus! Semua tugas dikumpulkan tepat waktu.</p></div>
                    <?php else: ?>
                         <div class="assignment-grid">
                        <?php foreach ($assignmentsByStatus['overdue'] as $assignment): ?>
                            <div class="assignment-item" style="border-left-color: #dc3545;">
                                <div class="assignment-header">
                                    <div class="assignment-info">
                                        <h3 class="assignment-title"><?php echo htmlspecialchars($assignment['title']); ?></h3>
                                        <div class="assignment-course"><i class="fas fa-book"></i><?php echo htmlspecialchars($assignment['nama_matkul']); ?></div>
                                    </div>
                                    <?php echo getStatusBadge($assignment); ?>
                                </div>
                                <div class="assignment-meta mt-15">
                                    <div class="meta-item deadline-warning"><i class="fas fa-calendar-times"></i>Deadline: <?php echo date('d M Y H:i', strtotime($assignment['due_date'])); ?></div>
                                </div>
                                <div class="assignment-actions mt-15">
                                    <div class="assignment-actions mt-15">
                                        <button class="btn btn-warning" onclick="openUploadModal(<?php echo $assignment['assignment_id']; ?>, '<?php echo htmlspecialchars(addslashes($assignment['title'])); ?>')"><i class="fas fa-upload"></i> Kumpulkan Terlambat</button>
                                    </div>  
                                </div>
                            </div>
                        <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div id="uploadModal" class="modal">
        </div>

    <script src="sidebar.js"></script>

    <script>
    // Fungsi untuk menampilkan modal upload
    function openUploadModal(assignmentId, assignmentTitle) {
        const modal = document.getElementById('uploadModal');
        
        // Membuat konten HTML untuk modal secara dinamis
        modal.innerHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Perbarui Tugas: ${assignmentTitle}</h2>
                    <span class="close-btn" onclick="closeModal()">&times;</span>
                </div>
                <div class="modal-body">
                    <form action="tugas.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="submit_assignment">
                        <input type="hidden" name="assignment_id" value="${assignmentId}">
                        <div class="form-group">
                            <label>Pilih File Baru (PDF, DOCX, ZIP, jpg, dll)</label>
                            <input type="file" name="assignment_file" required>
                        </div>
                        <div style="text-align: right; margin-top: 20px;">
                            <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
                            <button type="submit" class="btn btn-primary">Unggah & Perbarui</button>
                        </div>
                    </form>
                </div>
            </div>
        `;
        
        modal.style.display = 'block'; // Tampilkan modal
    }

    // Fungsi untuk menutup modal
    function closeModal() {
        const modal = document.getElementById('uploadModal');
        modal.style.display = 'none';
    }

    // Menutup modal jika user mengklik di luar area konten
    window.onclick = function(event) {
        const modal = document.getElementById('uploadModal');
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }

     function switchTab(event, tabName) {
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.getElementById(tabName).classList.add('active');
            event.currentTarget.classList.add('active');
        }
        
</script>
</body>
</html>