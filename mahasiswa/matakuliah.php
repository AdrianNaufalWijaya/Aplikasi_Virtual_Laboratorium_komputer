<?php
session_start();
require_once '../koneksi.php';

// Create database connection
$database = new Database();
$pdo = $database->getConnection();

// Check if user is logged in and is mahasiswa
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mahasiswa') {
    header('Location: login.php?logout=success');
    exit();
}

// Handle logout
if (isset($_GET['logout'])) {
    // Log activity
    try {
        $log_stmt = $pdo->prepare("
            INSERT INTO activity_log (user_id, action, entity_type, entity_id, ip_address, user_agent) 
            VALUES (?, 'logout', 'user', ?, ?, ?)
        ");
        $log_stmt->execute([
            $_SESSION['user_id'], 
            $_SESSION['user_id'], 
            $_SERVER['REMOTE_ADDR'], 
            $_SERVER['HTTP_USER_AGENT']
        ]);
    } catch (Exception $e) {
        // Silent fail for logging
    }
    
    // Destroy session
    session_destroy();
    header('Location: login.php?logout=success');
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$full_name = $_SESSION['full_name'];

// Handle enrollment action
if ($_POST && isset($_POST['action'])) {
    if ($_POST['action'] === 'request_enroll' && isset($_POST['course_id'])) {
        $course_id = $_POST['course_id'];
        
        // Check if already has pending/approved enrollment
        $checkSql = "SELECT * FROM enrollment WHERE user_id = ? AND course_id = ?";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([$user_id, $course_id]);
        
        if ($checkStmt->rowCount() == 0) {
            // Create enrollment request (pending approval)
            $enrollSql = "INSERT INTO enrollment (user_id, course_id, status, enrolled_at) VALUES (?, ?, 'pending', NOW())";
            $enrollStmt = $pdo->prepare($enrollSql);
            if ($enrollStmt->execute([$user_id, $course_id])) {
                $success_message = "Permintaan pendaftaran mata kuliah berhasil dikirim! Menunggu persetujuan dosen.";
            } else {
                $error_message = "Gagal mengirim permintaan pendaftaran!";
            }
        } else {
            $existing = $checkStmt->fetch();
            switch($existing['status']) {
                case 'pending':
                    $error_message = "Permintaan pendaftaran Anda masih menunggu persetujuan dosen.";
                    break;
                case 'approved':
                    $error_message = "Anda sudah terdaftar di mata kuliah ini!";
                    break;
                case 'rejected':
                    $error_message = "Permintaan pendaftaran Anda ditolak. Silakan hubungi dosen untuk informasi lebih lanjut.";
                    break;
            }
        }
    }
    
    if ($_POST['action'] === 'cancel_request' && isset($_POST['course_id'])) {
        $course_id = $_POST['course_id'];
        
        // Cancel pending enrollment request
        $cancelSql = "DELETE FROM enrollment WHERE user_id = ? AND course_id = ? AND status = 'pending'";
        $cancelStmt = $pdo->prepare($cancelSql);
        if ($cancelStmt->execute([$user_id, $course_id])) {
            $success_message = "Permintaan pendaftaran berhasil dibatalkan!";
        } else {
            $error_message = "Gagal membatalkan permintaan pendaftaran!";
        }
    }
}

// Function to get all available courses with enrollment status
function getAllCourses($pdo, $user_id) {
    $sql = "
        SELECT 
            c.course_id,
            c.kode_matkul,
            c.nama_matkul,
            c.semester,
            c.status,
            u.full_name as dosen_name,
            u.email as dosen_email,
            COUNT(e2.enrollment_id) as total_enrolled,
            e.status as enrollment_status,
            e.enrolled_at
        FROM course c
        INNER JOIN users u ON c.id_dosen = u.user_id
        LEFT JOIN enrollment e ON c.course_id = e.course_id AND e.user_id = ?
        LEFT JOIN enrollment e2 ON c.course_id = e2.course_id AND e2.status IN ('approved', 'pending')
        WHERE c.status = 'active'
        GROUP BY c.course_id, c.kode_matkul, c.nama_matkul, c.semester, c.status, u.full_name, u.email, e.status, e.enrolled_at
        ORDER BY c.nama_matkul ASC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get enrolled courses (approved only)
function getEnrolledCourses($pdo, $user_id) {
    $sql = "
        SELECT 
            c.course_id,
            c.kode_matkul,
            c.nama_matkul,
            c.semester,
            u.full_name as dosen_name,
            e.enrolled_at,
            e.status
        FROM course c
        INNER JOIN enrollment e ON c.course_id = e.course_id
        INNER JOIN users u ON c.id_dosen = u.user_id
        WHERE e.user_id = ? AND c.status = 'active' AND e.status = 'approved'
        ORDER BY e.enrolled_at DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get pending enrollments
function getPendingEnrollments($pdo, $user_id) {
    $sql = "
        SELECT 
            c.course_id,
            c.kode_matkul,
            c.nama_matkul,
            c.semester,
            u.full_name as dosen_name,
            e.enrolled_at,
            e.status
        FROM course c
        INNER JOIN enrollment e ON c.course_id = e.course_id
        INNER JOIN users u ON c.id_dosen = u.user_id
        WHERE e.user_id = ? AND c.status = 'active' AND e.status = 'pending'
        ORDER BY e.enrolled_at DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get course icon based on course name
function getCourseIcon($courseName) {
    $name = strtolower($courseName);
    if (strpos($name, 'web') !== false || strpos($name, 'website') !== false) {
        return 'fa-globe';
    } elseif (strpos($name, 'data') !== false || strpos($name, 'database') !== false) {
        return 'fa-database';
    } elseif (strpos($name, 'program') !== false) {
        return 'fa-code';
    } elseif (strpos($name, 'network') !== false || strpos($name, 'jaringan') !== false) {
        return 'fa-network-wired';
    } else {
        return 'fa-book';
    }
}

// Get data
$allCourses = getAllCourses($pdo, $user_id);
$enrolledCourses = getEnrolledCourses($pdo, $user_id);
$pendingEnrollments = getPendingEnrollments($pdo, $user_id);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mata Kuliah - Dashboard Mahasiswa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="mahasiswa.css">
</head>
<body>
    <button class="mobile-menu-btn"><i class="fas fa-bars"></i></button>
    <?php include 'sidebar_mahasiswa.html'; ?>

    <div class="main-content">
        <div class="top-nav">
            <div class="nav-left">
                <h1 class="page-title">Mata Kuliah</h1>
                <div class="breadcrumb">
                    <a href="dashboard_mahasiswa.php">Home</a>
                    <i class="fas fa-chevron-right"></i>
                    <span>Mata Kuliah</span>
                </div>
            </div>
            <div class="nav-right">
                <div class="user-menu">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($full_name, 0, 1)); ?>
                    </div>
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
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <div class="tabs-container">
                <div class="tabs">
                    <button class="tab active" onclick="switchTab(event, 'all-courses')">
                        <i class="fas fa-list"></i>
                        Semua Mata Kuliah
                    </button>
                    <button class="tab" onclick="switchTab(event, 'my-courses')">
                        <i class="fas fa-graduation-cap"></i>
                        Mata Kuliah Saya (<?php echo count($enrolledCourses); ?>)
                    </button>
                    <button class="tab" onclick="switchTab(event, 'pending-courses')">
                        <i class="fas fa-clock"></i>
                        Menunggu Persetujuan (<?php echo count($pendingEnrollments); ?>)
                    </button>
                </div>

                <div id="all-courses" class="tab-content active">
                    <?php if (empty($allCourses)): ?>
                        <div class="empty-state">
                            <i class="fas fa-book-open"></i>
                            <h3>Tidak Ada Mata Kuliah</h3>
                            <p>Belum ada mata kuliah yang tersedia saat ini.</p>
                        </div>
                    <?php else: ?>
                        <div class="course-grid">
                            <?php foreach ($allCourses as $course): ?>
                                <div class="course-card">
                                    <div class="course-header">
                                        <div class="course-icon">
                                            <i class="fas <?php echo getCourseIcon($course['nama_matkul']); ?>"></i>
                                        </div>
                                        <div class="course-info">
                                            <div class="course-title"><?php echo htmlspecialchars($course['nama_matkul']); ?></div>
                                            <div class="course-code"><?php echo htmlspecialchars($course['kode_matkul']); ?></div>
                                            <?php if ($course['enrollment_status'] === 'approved'): ?>
                                                <span class="status-badge enrolled-badge">
                                                    <i class="fas fa-check"></i> Terdaftar
                                                </span>
                                            <?php elseif ($course['enrollment_status'] === 'pending'): ?>
                                                <span class="status-badge pending-badge">
                                                    <i class="fas fa-clock"></i> Menunggu Persetujuan
                                                </span>
                                            <?php elseif ($course['enrollment_status'] === 'rejected'): ?>
                                                <span class="status-badge rejected-badge">
                                                    <i class="fas fa-times"></i> Ditolak
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="course-details">
                                        <div class="info-item">
                                            <i class="fas fa-chalkboard-teacher"></i>
                                            <?php echo htmlspecialchars($course['dosen_name']); ?>
                                        </div>
                                        <div class="info-item">
                                            <i class="fas fa-calendar-alt"></i>
                                            <?php echo htmlspecialchars($course['semester']); ?>
                                        </div>
                                        <div class="info-item">
                                            <i class="fas fa-users"></i>
                                            <span class="student-count"><?php echo $course['total_enrolled']; ?> mahasiswa terdaftar</span>
                                        </div>
                                    </div>
                                    
                                    <div class="course-actions">
                                        <?php if ($course['enrollment_status'] === 'approved'): ?>
                                            <a href="mata_kuliah_detail.php?id=<?php echo $course['course_id']; ?>" class="btn btn-primary">
                                                <i class="fas fa-door-open"></i> Masuk Kelas
                                            </a>
                                        <?php elseif ($course['enrollment_status'] === 'pending'): ?>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Yakin ingin membatalkan permintaan pendaftaran?')">
                                                <input type="hidden" name="action" value="cancel_request">
                                                <input type="hidden" name="course_id" value="<?php echo $course['course_id']; ?>">
                                                <button type="submit" class="btn btn-warning">
                                                    <i class="fas fa-times"></i> Batalkan
                                                </button>
                                            </form>
                                        <?php elseif ($course['enrollment_status'] === 'rejected'): ?>
                                            <span class="btn btn-secondary" style="cursor: not-allowed;">
                                                <i class="fas fa-ban"></i> Ditolak
                                            </span>
                                        <?php else: ?>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Yakin ingin mengajukan pendaftaran mata kuliah ini?')">
                                                <input type="hidden" name="action" value="request_enroll">
                                                <input type="hidden" name="course_id" value="<?php echo $course['course_id']; ?>">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="fas fa-plus"></i> Daftar
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div id="my-courses" class="tab-content">
                    <?php if (empty($enrolledCourses)): ?>
                        <div class="empty-state">
                            <i class="fas fa-graduation-cap"></i>
                            <h3>Belum Mengambil Mata Kuliah</h3>
                            <p>Anda belum mendaftar di mata kuliah manapun.</p>
                        </div>
                    <?php else: ?>
                        <div class="course-grid">
                            <?php foreach ($enrolledCourses as $course): ?>
                                <div class="course-card">
                                    <div class="course-header">
                                        <div class="course-icon">
                                            <i class="fas <?php echo getCourseIcon($course['nama_matkul']); ?>"></i>
                                        </div>
                                        <div class="course-info">
                                            <div class="course-title"><?php echo htmlspecialchars($course['nama_matkul']); ?></div>
                                            <div class="course-code"><?php echo htmlspecialchars($course['kode_matkul']); ?></div>
                                            <span class="status-badge enrolled-badge">
                                                <i class="fas fa-check"></i> Terdaftar
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="course-details">
                                        <div class="info-item">
                                            <i class="fas fa-chalkboard-teacher"></i>
                                            <?php echo htmlspecialchars($course['dosen_name']); ?>
                                        </div>
                                        <div class="info-item">
                                            <i class="fas fa-calendar-alt"></i>
                                            <?php echo htmlspecialchars($course['semester']); ?>
                                        </div>
                                        <div class="info-item">
                                            <i class="fas fa-clock"></i>
                                            Terdaftar: <?php echo date('d M Y', strtotime($course['enrolled_at'])); ?>
                                        </div>
                                    </div>
                                    
                                    <div class="course-actions">
                                        <a href="mata_kuliah_detail.php?id=<?php echo $course['course_id']; ?>" class="btn btn-primary">
                                            <i class="fas fa-door-open"></i> Masuk Kelas
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div id="pending-courses" class="tab-content">
                    <?php if (empty($pendingEnrollments)): ?>
                        <div class="empty-state">
                            <i class="fas fa-hourglass-half"></i>
                            <h3>Tidak Ada Permintaan Pending</h3>
                            <p>Anda tidak memiliki permintaan pendaftaran yang menunggu persetujuan.</p>
                        </div>
                    <?php else: ?>
                        <div class="course-grid">
                            <?php foreach ($pendingEnrollments as $course): ?>
                                <div class="course-card">
                                    <div class="course-header">
                                        <div class="course-icon">
                                            <i class="fas <?php echo getCourseIcon($course['nama_matkul']); ?>"></i>
                                        </div>
                                        <div class="course-info">
                                            <div class="course-title"><?php echo htmlspecialchars($course['nama_matkul']); ?></div>
                                            <div class="course-code"><?php echo htmlspecialchars($course['kode_matkul']); ?></div>
                                            <span class="status-badge pending-badge">
                                                <i class="fas fa-clock"></i> Menunggu Persetujuan
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="course-details">
                                        <div class="info-item">
                                            <i class="fas fa-chalkboard-teacher"></i>
                                            <?php echo htmlspecialchars($course['dosen_name']); ?>
                                        </div>
                                        <div class="info-item">
                                            <i class="fas fa-calendar-alt"></i>
                                            <?php echo htmlspecialchars($course['semester']); ?>
                                        </div>
                                        <div class="info-item">
                                            <i class="fas fa-paper-plane"></i>
                                            Diajukan: <?php echo date('d M Y H:i', strtotime($course['enrolled_at'])); ?>
                                        </div>
                                    </div>
                                    
                                    <div class="course-actions">
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Yakin ingin membatalkan permintaan pendaftaran?')">
                                            <input type="hidden" name="action" value="cancel_request">
                                            <input type="hidden" name="course_id" value="<?php echo $course['course_id']; ?>">
                                            <button type="submit" class="btn btn-warning">
                                                <i class="fas fa-times"></i> Batalkan
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Tab switching function
        function switchTab(event, tabName) {
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.getElementById(tabName).classList.add('active');
            event.currentTarget.classList.add('active');
        }

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(() => alert.style.display = 'none', 500);
            });
        }, 5000);
    </script>
    <script src="mahasiswa.js"></script>
</body>
</html>