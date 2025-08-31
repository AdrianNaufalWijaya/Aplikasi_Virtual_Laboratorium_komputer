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

// Function to get all graded submissions
function getGradedSubmissions($pdo, $user_id) {
    $sql = "
        SELECT 
            s.submission_id, s.score, s.feedback, s.tanggal_dikumpulkan, s.tanggal_dinilai, s.file_path,
            a.assignment_id, a.title as assignment_title, a.max_score, a.due_date,
            c.course_id, c.nama_matkul, c.kode_matkul, c.semester, u.full_name as dosen_name
        FROM submission s
        INNER JOIN assignment a ON s.id_tugas = a.assignment_id
        INNER JOIN course c ON a.id_matkul = c.course_id
        INNER JOIN users u ON c.id_dosen = u.user_id
        WHERE s.id_mahasiswa = ? AND s.status = 'graded' AND s.score IS NOT NULL
        ORDER BY s.tanggal_dinilai DESC, c.nama_matkul ASC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get course statistics
// Function to get course statistics
function getCourseStatistics($pdo, $user_id) {
    $sql = "
        SELECT 
            c.course_id, c.nama_matkul, c.kode_matkul, c.semester, u.full_name as dosen_name,
            
            -- Menghitung total tugas yang sudah dinilai untuk mahasiswa ini di matkul ini
            (SELECT COUNT(s.submission_id) 
             FROM submission s
             JOIN assignment a_sub ON s.id_tugas = a_sub.assignment_id
             WHERE a_sub.id_matkul = c.course_id AND s.id_mahasiswa = :user_id1 AND s.status = 'graded') as total_graded,
            
            -- Menghitung total tugas yang pernah dipublikasikan di matkul ini
            (SELECT COUNT(a_total.assignment_id) 
             FROM assignment a_total 
             WHERE a_total.id_matkul = c.course_id AND a_total.status = 'published') as total_assignments_published,

            -- Kalkulasi skor yang sudah ada
            ROUND(AVG(s.score), 2) as average_score,
            SUM(s.score) as total_score, 
            SUM(a.max_score) as total_max_score,
            MIN(s.score) as min_score, 
            MAX(s.score) as max_score
            
        FROM course c
        INNER JOIN enrollment e ON c.course_id = e.course_id
        INNER JOIN users u ON c.id_dosen = u.user_id
        LEFT JOIN assignment a ON c.course_id = a.id_matkul AND a.status = 'published'
        LEFT JOIN submission s ON a.assignment_id = s.id_tugas AND s.id_mahasiswa = :user_id2 AND s.status = 'graded'
        WHERE e.user_id = :user_id3 AND e.status = 'approved' AND c.status = 'active'
        GROUP BY c.course_id, c.nama_matkul, c.kode_matkul, c.semester, u.full_name
        HAVING total_graded > 0
        ORDER BY c.nama_matkul ASC
    ";
    
    $stmt = $pdo->prepare($sql);
    // Kita perlu bind user_id tiga kali karena digunakan di subquery dan klausa WHERE utama
    $stmt->execute(['user_id1' => $user_id, 'user_id2' => $user_id, 'user_id3' => $user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Helper functions for grade calculation and display
function getGradeLetter($percentage) {
    if ($percentage >= 85) return 'A';
    if ($percentage >= 75) return 'B';
    if ($percentage >= 65) return 'C';
    if ($percentage >= 50) return 'D';
    return 'E';
}

function getGradeColor($percentage) {
    if ($percentage >= 85) return '#28a745';
    if ($percentage >= 75) return '#17a2b8';
    if ($percentage >= 65) return '#ffc107';
    if ($percentage >= 50) return '#fd7e14';
    return '#dc3545';
}

function getCourseIcon($courseName) {
    $name = strtolower($courseName);
    if (strpos($name, 'web') !== false) return 'fa-globe';
    if (strpos($name, 'data') !== false) return 'fa-database';
    if (strpos($name, 'program') !== false) return 'fa-code';
    if (strpos($name, 'network') !== false) return 'fa-network-wired';
    return 'fa-book';
}

// Get data
$gradedSubmissions = getGradedSubmissions($pdo, $user_id);
$courseStats = getCourseStatistics($pdo, $user_id);

// Calculate overall statistics
$overallStats = ['total_assignments' => count($gradedSubmissions), 'total_courses' => count($courseStats), 'overall_average' => 0, 'total_score' => 0, 'total_max_score' => 0];
if (!empty($gradedSubmissions)) {
    $totalScore = array_sum(array_column($gradedSubmissions, 'score'));
    $totalMaxScore = array_sum(array_column($gradedSubmissions, 'max_score'));
    $overallStats['total_score'] = $totalScore;
    $overallStats['total_max_score'] = $totalMaxScore;
    $overallStats['overall_average'] = $totalMaxScore > 0 ? round(($totalScore / $totalMaxScore) * 100, 2) : 0;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nilai - Dashboard Mahasiswa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="mahasiswa.css">
</head>
<body>
    <button class="mobile-menu-btn"><i class="fas fa-bars"></i></button>
    <?php include 'sidebar_mahasiswa.html'; ?>

    <div class="main-content">
        <div class="top-nav">
            <div class="nav-left">
                <h1 class="page-title">Nilai Akademik</h1>
                <div class="breadcrumb">
                    <a href="dashboard_mahasiswa.php">Home</a>
                    <i class="fas fa-chevron-right"></i>
                    <span>Nilai</span>
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
            <?php if (!empty($gradedSubmissions)): ?>
            <div class="section">
                <h2 class="section-title"><i class="fas fa-chart-bar"></i> Ringkasan Prestasi</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $overallStats['overall_average']; ?>%</div>
                        <div class="stat-label">Rata-rata Keseluruhan</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $overallStats['total_courses']; ?></div>
                        <div class="stat-label">Mata Kuliah Dinilai</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $overallStats['total_assignments']; ?></div>
                        <div class="stat-label">Tugas Dinilai</div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="tabs-container">
                <div class="tabs">
                    <button class="tab active" onclick="openTab(event, 'courses')"><i class="fas fa-book"></i> Per Mata Kuliah</button>
                    <button class="tab" onclick="openTab(event, 'assignments')"><i class="fas fa-tasks"></i> Per Tugas</button>
                </div>

                <div id="courses" class="tab-content active">
                    <?php if (!empty($courseStats)): ?>
                        <div class="course-grid">
                            <?php foreach ($courseStats as $course): 
                                $percentage = $course['total_max_score'] > 0 ? round(($course['total_score'] / $course['total_max_score']) * 100, 2) : 0;
                                $gradeLetter = getGradeLetter($percentage);
                                $gradeColor = getGradeColor($percentage);
                            ?>
                            <div class="course-card">
                                <div class="course-header">
                                    <div class="course-icon"><i class="fas <?php echo getCourseIcon($course['nama_matkul']); ?>"></i></div>
                                    <div class="course-info">
                                        <h3 class="course-title"><?php echo htmlspecialchars($course['nama_matkul']); ?></h3>
                                        <div class="course-code"><?php echo htmlspecialchars($course['kode_matkul']); ?> | Semester <?php echo $course['semester']; ?></div>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-15">
                                     <div class="info-item">
                                    <i class="fas fa-chalkboard-teacher"></i>
                                    <span><?php echo htmlspecialchars($course['dosen_name']); ?></span>
                                </div>
                                
                                <div style="text-align: right;">
                                    <div style="font-size: 24px; font-weight: 700; color: <?php echo $gradeColor; ?>"><?php echo $percentage; ?>%</div>
                                    <div style="font-size: 12px;"><?php echo $course['total_graded']; ?> / <?php echo $course['total_assignments_published']; ?> tugas dinilai</div>
                                </div>
                                </div>
                                <div class="stats-grid" style="gap: 10px;">
                                    <div class="stat-card" style="padding: 10px; border-width: 2px;">
                                        <div class="stat-number" style="font-size: 18px;"><?php echo $course['average_score']; ?></div>
                                        <div class="stat-label" style="font-size: 11px;">Rata-rata</div>
                                    </div>
                                    <div class="stat-card" style="padding: 10px; border-width: 2px;">
                                        <div class="stat-number" style="font-size: 18px;"><?php echo $course['max_score']; ?></div>
                                        <div class="stat-label" style="font-size: 11px;">Tertinggi</div>
                                    </div>
                                    <div class="stat-card" style="padding: 10px; border-width: 2px;">
                                        <div class="stat-number" style="font-size: 18px;"><?php echo $course['min_score']; ?></div>
                                        <div class="stat-label" style="font-size: 11px;">Terendah</div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state"><i class="fas fa-graduation-cap"></i><h3>Belum Ada Nilai</h3><p>Anda belum memiliki mata kuliah dengan nilai yang tersedia.</p></div>
                    <?php endif; ?>
                </div>

                <div id="assignments" class="tab-content">
                    <?php if (!empty($gradedSubmissions)): ?>
                        <div class="assignment-grid">
                            <?php foreach ($gradedSubmissions as $submission): 
                                $percentage = $submission['max_score'] > 0 ? round(($submission['score'] / $submission['max_score']) * 100, 2) : 0;
                            ?>
                            <div class="assignment-item">
                                <div class="assignment-header">
                                    <div class="assignment-info">
                                        <div class="assignment-title"><?php echo htmlspecialchars($submission['assignment_title']); ?></div>
                                        <div class="assignment-course"><i class="fas <?php echo getCourseIcon($submission['nama_matkul']); ?>"></i><?php echo htmlspecialchars($submission['nama_matkul']); ?></div>
                                    </div>
                                    <div style="text-align: right;">
                                        <div style="font-size: 18px; font-weight: 700; color: <?php echo getGradeColor($percentage); ?>"><?php echo $submission['score']; ?>/<?php echo $submission['max_score']; ?></div>
                                        <div style="font-size: 12px; color: #6c757d;"><?php echo $percentage; ?>%</div>
                                    </div>
                                </div>
                                <?php if (!empty($submission['feedback'])): ?>
                                <div class="feedback-content mt-10" style="background: #f8f9fa; padding: 10px; border-radius: 6px;">
                                    <strong><i class="fas fa-comment-dots"></i> Feedback:</strong> <?php echo nl2br(htmlspecialchars($submission['feedback'])); ?>
                                </div>
                                <?php endif; ?>
                                <div class="d-flex justify-content-between mt-10" style="font-size: 12px; color: #6c757d;">
                                    <span>Dinilai: <?php echo date('d M Y', strtotime($submission['tanggal_dinilai'])); ?></span>
                                    <a href="../uploads/<?php echo htmlspecialchars($submission['file_path']); ?>" target="_blank">Lihat File</a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state"><i class="fas fa-tasks"></i><h3>Belum Ada Tugas Dinilai</h3><p>Tugas Anda yang telah dinilai akan muncul di sini.</p></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="mahasiswa.js"></script>
    <script>
        function openTab(evt, tabName) {
            document.querySelectorAll(".tab-content").forEach(tab => tab.classList.remove("active"));
            document.querySelectorAll(".tab").forEach(btn => btn.classList.remove("active"));
            document.getElementById(tabName).classList.add("active");
            evt.currentTarget.classList.add("active");
        }
    </script>
</body>
</html>