<?php
session_start();
require_once '../koneksi.php'; 

// membuat koneksi database
$database = new Database();
$pdo = $database->getConnection();

// Periksa apakah pengguna sudah masuk
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$full_name = $_SESSION['full_name'];

// ============= REAL DATA FUNCTIONS =============

// fungsi untuk mendapatkan data tugas mendatang REAL
function getUpcomingAssignments($pdo, $user_id, $limit = 5) {
    $sql = "
        SELECT 
            a.assignment_id,
            a.title,
            a.description,
            a.due_date,
            a.max_score,
            c.nama_matkul,
            c.kode_matkul,
            s.submission_id
        FROM assignment a
        INNER JOIN course c ON a.id_matkul = c.course_id
        INNER JOIN enrollment e ON c.course_id = e.course_id
        LEFT JOIN submission s ON a.assignment_id = s.id_tugas AND s.id_mahasiswa = ?
        WHERE e.user_id = ? 
        AND e.status = 'approved'
        AND a.status = 'published'
        AND a.due_date > NOW()
        AND s.submission_id IS NULL
        ORDER BY a.due_date ASC
        LIMIT ?
    ";
    
$stmt = $pdo->prepare($sql);

$stmt->bindValue(1, $user_id, PDO::PARAM_INT);
$stmt->bindValue(2, $user_id, PDO::PARAM_INT);
$stmt->bindValue(3, $limit, PDO::PARAM_INT);
$stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fungsi untuk mendapatkan enrolled courses REAL
function getEnrolledCourses($pdo, $user_id, $limit = 6) {
    $sql = "
        SELECT 
            c.course_id,
            c.kode_matkul,
            c.nama_matkul,
            c.semester,
            u.full_name as dosen_name,
            e.status as enrollment_status,
            e.enrolled_at
        FROM course c
        INNER JOIN enrollment e ON c.course_id = e.course_id
        INNER JOIN users u ON c.id_dosen = u.user_id
        WHERE e.user_id = ? 
        AND c.status = 'active' 
        AND e.status = 'approved'
        ORDER BY c.nama_matkul ASC
        LIMIT ?
    ";
    
    
$stmt = $pdo->prepare($sql);
$stmt->bindValue(1, $user_id, PDO::PARAM_INT);
$stmt->bindValue(2, $limit, PDO::PARAM_INT);
$stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function untuk mendapatkan recent activities REAL
function getRecentActivities($pdo, $user_id, $limit = 5) {
    $activities = [];
    
    // Get recent submissions
    $submission_sql = "
        SELECT 
            CONCAT('Tugas \"', a.title, '\" dikumpulkan') as title,
            CONCAT('Mata kuliah: ', c.nama_matkul) as description,
            s.tanggal_dikumpulkan as activity_time,
            'upload' as icon_type
        FROM submission s
        INNER JOIN assignment a ON s.id_tugas = a.assignment_id
        INNER JOIN course c ON a.id_matkul = c.course_id
        WHERE s.id_mahasiswa = ?
        ORDER BY s.tanggal_dikumpulkan DESC
        LIMIT 2
    ";
    
    $stmt = $pdo->prepare($submission_sql);
    $stmt->execute([$user_id]);
    $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($submissions as $sub) {
        $activities[] = [
            'type' => $sub['icon_type'],
            'title' => $sub['title'],
            'description' => $sub['description'],
            'time' => $sub['activity_time']
        ];
    }
    
    // Get recent enrollments
    $enrollment_sql = "
        SELECT 
            CONCAT('Terdaftar di mata kuliah \"', c.nama_matkul, '\"') as title,
            CONCAT('Kode: ', c.kode_matkul, ' - ', c.semester) as description,
            e.enrolled_at as activity_time,
            'book' as icon_type
        FROM enrollment e
        INNER JOIN course c ON e.course_id = c.course_id
        WHERE e.user_id = ? AND e.status = 'approved'
        ORDER BY e.enrolled_at DESC
        LIMIT 3
    ";
    
    $stmt = $pdo->prepare($enrollment_sql);
    $stmt->execute([$user_id]);
    $enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($enrollments as $enroll) {
        $activities[] = [
            'type' => $enroll['icon_type'],
            'title' => $enroll['title'],
            'description' => $enroll['description'],
            'time' => $enroll['activity_time']
        ];
    }
    
    // Urutkan berdasarkan waktu dan kembalikan aktivitas terbaru
    usort($activities, function($a, $b) {
        return strtotime($b['time']) - strtotime($a['time']);
    });
    
    return array_slice($activities, 0, $limit);
}

// Function untuk mendapatkan statistics REAL
function getUserStatistics($pdo, $user_id) {
    $stats = [];
    
    // Total courses enrolled
    $course_sql = "
        SELECT COUNT(*) as total
        FROM enrollment e
        INNER JOIN course c ON e.course_id = c.course_id
        WHERE e.user_id = ? 
        AND e.status = 'approved' 
        AND c.status = 'active'
    ";
    $stmt = $pdo->prepare($course_sql);
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['total_courses'] = (int) $result['total'];
    
    // Total pending assignments
    $assignment_sql = "
        SELECT COUNT(*) as total
        FROM assignment a
        INNER JOIN course c ON a.id_matkul = c.course_id
        INNER JOIN enrollment e ON c.course_id = e.course_id
        LEFT JOIN submission s ON a.assignment_id = s.id_tugas AND s.id_mahasiswa = ?
        WHERE e.user_id = ? 
        AND e.status = 'approved'
        AND a.status = 'published'
        AND a.due_date > NOW()
        AND s.submission_id IS NULL
    ";
    $stmt = $pdo->prepare($assignment_sql);
    $stmt->execute([$user_id, $user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['total_assignments'] = (int) $result['total'];
    
    // Total lab virtual computers
    $lab_sql = "
        SELECT COUNT(*) as total
        FROM virtual_computer vc
        INNER JOIN laboratory l ON vc.lab_id = l.lab_id
        WHERE vc.status IN ('available', 'occupied')
        AND vc.maintenance_status = 'normal'
    ";
    $stmt = $pdo->prepare($lab_sql);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['total_labs'] = (int) $result['total'];
    
    // Nilai rata-rata
    $grade_sql = "
        SELECT 
            AVG((s.score / a.max_score) * 100) as avg_percentage
        FROM submission s
        INNER JOIN assignment a ON s.id_tugas = a.assignment_id
        WHERE s.id_mahasiswa = ? 
        AND s.status = 'graded' 
        AND s.score IS NOT NULL
        AND a.max_score > 0
    ";
    $stmt = $pdo->prepare($grade_sql);
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['average_grade'] = $result['avg_percentage'] ? round($result['avg_percentage'], 1) : 0;
    
    return $stats;
}

// Function untuk mendapatkan trends REAL
function getStatsTrends($pdo, $user_id) {
    $trends = [];
    
    // Course trend - enrollment bulan ini
    $course_trend_sql = "
        SELECT COUNT(*) as this_month
        FROM enrollment e
        WHERE e.user_id = ? 
        AND e.status = 'approved'
        AND MONTH(e.enrolled_at) = MONTH(CURDATE())
        AND YEAR(e.enrolled_at) = YEAR(CURDATE())
    ";
    $stmt = $pdo->prepare($course_trend_sql);
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $this_month = (int) $result['this_month'];
    
    $trends['courses'] = $this_month > 0 ? "+$this_month bulan ini" : "Tidak ada enrollment baru";
    
    // Assignment trend - deadline minggu ini
    $assignment_trend_sql = "
        SELECT COUNT(*) as this_week
        FROM assignment a
        INNER JOIN course c ON a.id_matkul = c.course_id
        INNER JOIN enrollment e ON c.course_id = e.course_id
        WHERE e.user_id = ? 
        AND e.status = 'approved'
        AND a.status = 'published'
        AND a.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    ";
    $stmt = $pdo->prepare($assignment_trend_sql);
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $this_week = (int) $result['this_week'];
    
    $trends['assignments'] = "$this_week deadline minggu ini";
    
    // Lab availability trend
    $lab_availability_sql = "
        SELECT 
            COUNT(*) as total,
            COUNT(CASE WHEN status = 'available' THEN 1 END) as available
        FROM virtual_computer
        WHERE maintenance_status = 'normal'
    ";
    $stmt = $pdo->prepare($lab_availability_sql);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['total'] > 0) {
        $percentage = round(($result['available'] / $result['total']) * 100);
        $trends['labs'] = $percentage > 90 ? "Semua tersedia" : "$percentage% tersedia";
    } else {
        $trends['labs'] = "Tidak ada lab";
    }
    
    // Grade trend
    $grade_avg = getUserStatistics($pdo, $user_id)['average_grade'];
    if ($grade_avg > 0) {
        if ($grade_avg >= 90) {
            $trends['grades'] = "Prestasi sangat baik!";
        } elseif ($grade_avg >= 80) {
            $trends['grades'] = "Prestasi baik";
        } elseif ($grade_avg >= 70) {
            $trends['grades'] = "Prestasi cukup";
        } else {
            $trends['grades'] = "Butuh perbaikan";
        }
    } else {
        $trends['grades'] = "Belum ada nilai";
    }
    
    return $trends;
}

// Function untuk get notification count REAL
function getUnreadNotificationCount($pdo, $user_id) {
    $sql = "SELECT COUNT(*) as total FROM notification WHERE user_id = ? AND is_read = 0";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return (int) $result['total'];
}

// Function untuk get course icon
function getCourseIcon($courseName) {
    $name = strtolower($courseName);
    if (strpos($name, 'web') !== false || strpos($name, 'website') !== false) {
        return 'fa-globe';
    } elseif (strpos($name, 'data') !== false || strpos($name, 'database') !== false || strpos($name, 'basis') !== false) {
        return 'fa-database';
    } elseif (strpos($name, 'program') !== false) {
        return 'fa-code';
    } elseif (strpos($name, 'network') !== false || strpos($name, 'jaringan') !== false) {
        return 'fa-network-wired';
    } elseif (strpos($name, 'sistem') !== false) {
        return 'fa-cogs';
    } else {
        return 'fa-book';
    }
}

// Utility functions
function timeAgo($datetime) {
    if (!$datetime) return 'Tidak diketahui';
    
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'Baru saja';
    if ($time < 3600) return floor($time/60) . ' menit yang lalu';
    if ($time < 86400) return floor($time/3600) . ' jam yang lalu';
    if ($time < 2592000) return floor($time/86400) . ' hari yang lalu';
    
    return date('d M Y', strtotime($datetime));
}

function formatDeadline($deadline) {
    if (!$deadline) return 'Tidak ada deadline';
    
    $now = new DateTime();
    $due = new DateTime($deadline);
    $diff = $now->diff($due);
    
    if ($due < $now) {
        return "Terlambat " . $diff->days . " hari";
    }
    
    $totalHours = ($diff->days * 24) + $diff->h;
    
    if ($totalHours <= 24) {
        return $totalHours . " jam lagi";
    } else {
        return $diff->days . " hari lagi";
    }
}

// ============= GET REAL DATA =============
$stats = getUserStatistics($pdo, $user_id);
$trends = getStatsTrends($pdo, $user_id);
$upcomingAssignments = getUpcomingAssignments($pdo, $user_id, 5);
$enrolledCourses = getEnrolledCourses($pdo, $user_id, 6);
$recentActivities = getRecentActivities($pdo, $user_id, 5);
$notificationCount = getUnreadNotificationCount($pdo, $user_id);

// Extract individual stats
$totalCourses = $stats['total_courses'];
$totalAssignments = $stats['total_assignments'];
$totalLabs = $stats['total_labs'];
$averageGrade = $stats['average_grade'];

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Mahasiswa - Virtual Laboratory</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="mahasiswa.css">
</head>
<body>
    <button class="mobile-menu-btn">
        <i class="fas fa-bars"></i>
    </button>

    <?php include 'sidebar_mahasiswa.html' ?>
   
    <div class="main-content">
        <div class="top-nav">
            <div class="nav-left">
                <h1 class="page-title">Dashboard</h1>
                <div class="breadcrumb">
                    <a href="dashboard_mahasiswa.php">Home</a>
                    <i class="fas fa-chevron-right"></i>
                    <span>Dashboard</span>
                </div>
            </div>
            <div class="nav-right">
                <div class="search-box">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" class="search-input" placeholder="Cari mata kuliah, tugas, atau materi...">
                </div>
                <div class="notifications">
                    <i class="fas fa-bell"></i>
                    <?php if ($notificationCount > 0): ?>
                        <span class="notification-badge"><?php echo $notificationCount; ?></span>
                    <?php endif; ?>
                </div>
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
            <div class="content-header">
                <div class="header-content">
                    <div class="welcome-text">
                        <h1>Selamat Datang, <?php echo htmlspecialchars($full_name); ?>!</h1>
                        <p>Semoga hari ini produktif dan menyenangkan. Mari lanjutkan perjalanan belajar Anda.</p>
                        <div class="welcome-stats">
                            <div class="welcome-stat">
                                <span class="welcome-stat-number"><?php echo $totalCourses; ?></span>
                                <span class="welcome-stat-label">Mata Kuliah Aktif</span>
                            </div>
                            <div class="welcome-stat">
                                <span class="welcome-stat-number"><?php echo $totalAssignments; ?></span>
                                <span class="welcome-stat-label">Tugas Pending</span>
                            </div>
                            <?php if ($averageGrade > 0): ?>
                            <div class="welcome-stat">
                                <span class="welcome-stat-number"><?php echo $averageGrade; ?>%</span>
                                <span class="welcome-stat-label">Rata-rata Nilai</span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="current-time">
                        <i class="fas fa-clock"></i>
                        <span id="current-time"><?php echo date('H:i'); ?></span>
                    </div>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-book"></i>
                        </div>
                        <div class="stat-trend"><?php echo $trends['courses']; ?></div>
                    </div>
                    <div class="stat-number"><?php echo $totalCourses; ?></div>
                    <div class="stat-label">Mata Kuliah Aktif</div>
                </div>
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <div class="stat-trend"><?php echo $trends['assignments']; ?></div>
                    </div>
                    <div class="stat-number"><?php echo $totalAssignments; ?></div>
                    <div class="stat-label">Tugas Mendatang</div>
                </div>
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-desktop"></i>
                        </div>
                        <div class="stat-trend"><?php echo $trends['labs']; ?></div>
                    </div>
                    <div class="stat-number"><?php echo $totalLabs; ?></div>
                    <div class="stat-label">Lab Virtual</div>
                </div>
                <?php if ($averageGrade > 0): ?>
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-trophy"></i>
                        </div>
                        <div class="stat-trend"><?php echo $trends['grades']; ?></div>
                    </div>
                    <div class="stat-number"><?php echo $averageGrade; ?>%</div>
                    <div class="stat-label">Rata-rata Nilai</div>
                </div>
                <?php endif; ?>
            </div>

            <div class="section">
                <h2 class="section-title">
                    <i class="fas fa-rocket"></i>
                    Aksi Cepat
                </h2>
                <div class="actions-grid">
                    <div class="action-card" onclick="window.location.href='lab_virtual.php'">
                        <div class="action-icon">
                            <i class="fas fa-desktop"></i>
                        </div>
                        <div class="action-title">Akses Lab Virtual</div>
                        <div class="action-description">Mulai praktikum dengan komputer virtual</div>
                    </div>
                    <div class="action-card" onclick="window.location.href='tugas.php'">
                        <div class="action-icon">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <div class="action-title">Lihat Tugas</div>
                        <div class="action-description">Kelola dan kumpulkan tugas dari mata kuliah</div>
                    </div>
                    <div class="action-card" onclick="window.location.href='matakuliah.php'">
                        <div class="action-icon">
                            <i class="fas fa-book-open"></i>
                        </div>
                        <div class="action-title">Mata Kuliah</div>
                        <div class="action-description">Lihat semua mata kuliah dan materi</div>
                    </div>
                    <div class="action-card" onclick="window.location.href='nilai.php'">
                        <div class="action-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="action-title">Lihat Nilai</div>
                        <div class="action-description">Pantau progress dan nilai akademik</div>
                    </div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
                <div class="section">
                    <h2 class="section-title">
                        <i class="fas fa-tasks"></i>
                        Tugas Mendatang
                    </h2>
                    <?php if (empty($upcomingAssignments)): ?>
                         <div class="empty-state">
                            <i class="fas fa-clipboard-check"></i>
                            <h3>Tidak Ada Tugas</h3>
                            <p>Anda tidak memiliki tugas yang akan datang.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($upcomingAssignments as $assignment): ?>
                            <?php 
                                $deadline = formatDeadline($assignment['due_date']);
                                $isUrgent = strtotime($assignment['due_date']) - time() <= 86400 * 2; // 48 hours
                            ?>
                            <div class="tugas-item">
                                <div class="tugas-header">
                                    <div>
                                        <div class="tugas-title"><?php echo htmlspecialchars($assignment['title']); ?></div>
                                        <div class="tugas-course">
                                            <i class="fas fa-book"></i>
                                            <?php echo htmlspecialchars($assignment['nama_matkul']); ?>
                                        </div>
                                    </div>
                                    <div class="tugas-deadline <?php echo $isUrgent ? 'urgent' : ''; ?>">
                                        <i class="fas fa-clock"></i>
                                        <?php echo $deadline; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                 <div class="section">
                    <h2 class="section-title">
                        <i class="fas fa-history"></i>
                        Aktivitas Terbaru
                    </h2>
                    <?php if (empty($recentActivities)): ?>
                        <div class="empty-state">
                            <i class="fas fa-bell-slash"></i>
                            <h3>Tidak Ada Aktivitas</h3>
                            <p>Aktivitas terbaru Anda akan muncul di sini.</p>
                        </div>
                    <?php else: ?>
                    <div class="activity-list">
                        <?php foreach ($recentActivities as $activity): ?>
                            <div class="activity-item">
                                <div class="activity-icon">
                                    <i class="fas fa-<?php echo $activity['type']; ?>"></i>
                                </div>
                                <div class="activity-content">
                                    <div class="activity-title"><?php echo htmlspecialchars($activity['title']); ?></div>
                                    <div class="activity-time"><?php echo timeAgo($activity['time']); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="sidebar.js"></script>
</body>
</html>