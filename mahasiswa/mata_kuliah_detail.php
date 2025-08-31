<?php
session_start();
require_once '../koneksi.php';

// Create database connection
$database = new Database();
$pdo = $database->getConnection();

// Check if user is logged in and is mahasiswa
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mahasiswa') {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];
$course_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$course_id) {
    header('Location: matakuliah.php');
    exit();
}

// Function to get course details (already real)
function getCourseDetails($pdo, $course_id, $user_id) {
    $sql = "
        SELECT c.course_id, c.kode_matkul, c.nama_matkul, c.semester, u.full_name as dosen_name
        FROM course c
        INNER JOIN users u ON c.id_dosen = u.user_id
        INNER JOIN enrollment e ON c.course_id = e.course_id
        WHERE c.course_id = ? AND e.user_id = ? AND e.status = 'approved'
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$course_id, $user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// NEW: Function to get real course materials from the database
function getCourseMaterials($pdo, $course_id) {
    $sql = "SELECT * FROM course_materials WHERE course_id = ? ORDER BY uploaded_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$course_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// NEW: Function to get real announcements from the database
function getAnnouncements($pdo, $course_id) {
    $sql = "SELECT * FROM announcements WHERE course_id = ? ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$course_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Helper functions
function getFileIcon($type) {
    switch(strtolower($type)) {
        case 'pdf': return 'fa-file-pdf';
        case 'pptx': return 'fa-file-powerpoint';
        case 'docx': return 'fa-file-word';
        case 'xlsx': return 'fa-file-excel';
        case 'zip': return 'fa-file-archive';
        default: return 'fa-file';
    }
}

function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    if ($time < 60) return 'Baru saja';
    if ($time < 3600) return floor($time/60) . ' menit lalu';
    if ($time < 86400) return floor($time/3600) . ' jam lalu';
    return date('d M Y', strtotime($datetime));
}

// Fetch all data
$course = getCourseDetails($pdo, $course_id, $user_id);
if (!$course) {
    // Redirect if user is not enrolled or course doesn't exist
    header('Location: matakuliah.php?error=not_enrolled');
    exit();
}

$materials = getCourseMaterials($pdo, $course_id);
$announcements = getAnnouncements($pdo, $course_id);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($course['nama_matkul']); ?> - Detail</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="mahasiswa.css">
</head>
<body>
    <button class="mobile-menu-btn"><i class="fas fa-bars"></i></button>
   <?php include 'sidebar_mahasiswa.html' ?>

    <div class="main-content">
        <div class="top-nav">
            <div class="nav-left">
                <h1 class="page-title"><?php echo htmlspecialchars($course['nama_matkul']); ?></h1>
                <div class="breadcrumb">
                    <a href="dashboard_mahasiswa.php">Home</a>
                    <i class="fas fa-chevron-right"></i>
                    <a href="matakuliah.php">Mata Kuliah</a>
                    <i class="fas fa-chevron-right"></i>
                    <span>Detail</span>
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
                        <a href="../logout.phpropdown-item logout" onclick="return confirm('Yakin ingin logout?')">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Log out</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="content-area">
             <div class="content-header">
                <div class="header-content">
                    <div class="welcome-text">
                        <h1><?php echo htmlspecialchars($course['nama_matkul']); ?></h1>
                        <p><?php echo htmlspecialchars($course['kode_matkul']); ?> - <?php echo htmlspecialchars($course['semester']); ?></p>
                         <div class="d-flex gap-15" style="margin-top: 15px;">
                            <div class="info-item" style="color: white;"><i class="fas fa-chalkboard-teacher"></i> <?php echo htmlspecialchars($course['dosen_name']); ?></div>
                        </div>
                    </div>
                    <a href="lab_virtual.php?course=<?php echo $course_id; ?>" class="btn btn-primary">
                        <i class="fas fa-desktop"></i> Akses Lab Virtual
                    </a>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
                <div>
                    <div class="section">
                        <h2 class="section-title"><i class="fas fa-folder-open"></i> Materi Pembelajaran</h2>
                        <?php if (empty($materials)): ?>
                            <div class="empty-state"><i class="fas fa-file-alt"></i><h3>Belum Ada Materi</h3><p>Materi untuk mata kuliah ini akan segera diunggah oleh dosen.</p></div>
                        <?php else: ?>
                            <div class="activity-list">
                            <?php foreach ($materials as $material): ?>
                                <div class="activity-item">
                                    <div class="activity-icon" style="font-size: 18px;"><i class="fas <?php echo getFileIcon($material['file_type']); ?>"></i></div>
                                    <div class="activity-content" style="flex: 1;">
                                        <div class="activity-title"><?php echo htmlspecialchars($material['title']); ?></div>
                                        <div class="activity-description"><?php echo htmlspecialchars($material['description']); ?></div>
                                    </div>
                                    <a href="<?php echo htmlspecialchars($material['file_path']); ?>" class="btn btn-primary" style="padding: 8px 12px; font-size: 12px;" download>
                                        <i class="fas fa-download"></i> Unduh
                                    </a>
                                </div>
                            <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div>
                    <div class="section">
                        <h2 class="section-title"><i class="fas fa-bullhorn"></i> Pengumuman</h2>
                        <?php if (empty($announcements)): ?>
                            <div class="empty-state"><i class="fas fa-bell-slash"></i><h4>Tidak Ada Pengumuman</h4><p>Belum ada pengumuman terbaru untuk mata kuliah ini.</p></div>
                        <?php else: ?>
                            <div class="activity-list">
                            <?php foreach ($announcements as $announcement): ?>
                                <div class="activity-item" style="<?php echo $announcement['is_important'] ? 'border-left-color: #dc3545;' : ''; ?>">
                                    <div class="activity-icon"><i class="fas fa-bullhorn"></i></div>
                                    <div class="activity-content">
                                        <div class="activity-title"><?php echo htmlspecialchars($announcement['title']); ?></div>
                                        <div class="activity-description"><?php echo htmlspecialchars($announcement['content']); ?></div>
                                        <div class="activity-time"><?php echo timeAgo($announcement['created_at']); ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="mahasiswa.js"></script>
</body>
</html>