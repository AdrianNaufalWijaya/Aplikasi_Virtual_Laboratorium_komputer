<?php

require_once 'auth_admin.php'; 
require_once '../koneksi.php'; 

// Initialize variables
$stats = [
    'total_users' => 0,
    'total_courses' => 0,
    'total_labs' => 0,
    'active_sessions' => 0
];

$recent_activities = [];
$notifications = [
    ['type' => 'urgent', 'title' => 'Server Maintenance', 'message' => 'Scheduled maintenance tonight'],
    ['type' => 'info', 'title' => 'New User Registration', 'message' => '5 new students registered today'],
    ['type' => 'warning', 'title' => 'Low Storage', 'message' => 'Lab storage 85% full'],
];

// Get real data from database
$database = new Database();
$conn = $database->getConnection();

if ($conn) {
    try {
        // Get total users
        $users_query = "SELECT COUNT(*) as total FROM users";
        $users_stmt = $conn->prepare($users_query);
        $users_stmt->execute();
        $users_result = $users_stmt->fetch(PDO::FETCH_ASSOC);
        $stats['total_users'] = $users_result['total'];
        
        // Get total courses
        $courses_query = "SELECT COUNT(*) as total FROM course WHERE status = 'active'";
        $courses_stmt = $conn->prepare($courses_query);
        $courses_stmt->execute();
        $courses_result = $courses_stmt->fetch(PDO::FETCH_ASSOC);
        $stats['total_courses'] = $courses_result['total'];
        
        // Get total labs
        $labs_query = "SELECT COUNT(*) as total FROM laboratory";
        $labs_stmt = $conn->prepare($labs_query);
        $labs_stmt->execute();
        $labs_result = $labs_stmt->fetch(PDO::FETCH_ASSOC);
        $stats['total_labs'] = $labs_result['total'];
        
        // Get active sessions
        $sessions_query = "SELECT COUNT(*) as total FROM session WHERE status = 'active'";
        $sessions_stmt = $conn->prepare($sessions_query);
        $sessions_stmt->execute();
        $sessions_result = $sessions_stmt->fetch(PDO::FETCH_ASSOC);
        $stats['active_sessions'] = $sessions_result['total'];
        
        // Get recent activities from activity_log
        $activities_query = "SELECT al.*, u.full_name as user_name 
                            FROM activity_log al 
                            JOIN users u ON al.user_id = u.user_id 
                            ORDER BY al.created_at DESC 
                            LIMIT 10";
        $activities_stmt = $conn->prepare($activities_query);
        $activities_stmt->execute();
        $activities_result = $activities_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format recent activities
        foreach ($activities_result as $activity) {
            $time_diff = time() - strtotime($activity['created_at']);
            
            if ($time_diff < 60) {
                $time_display = $time_diff . ' detik lalu';
            } elseif ($time_diff < 3600) {
                $time_display = floor($time_diff / 60) . ' menit lalu';
            } elseif ($time_diff < 86400) {
                $time_display = floor($time_diff / 3600) . ' jam lalu';
            } else {
                $time_display = floor($time_diff / 86400) . ' hari lalu';
            }
            
            // Format action description
            $action_description = '';
            switch ($activity['action']) {
                case 'login':
                    $action_description = 'Login ke sistem';
                    break;
                case 'logout':
                    $action_description = 'Logout dari sistem';
                    break;
                case 'create_user':
                    $action_description = 'Membuat user baru';
                    break;
                case 'update_user':
                    $action_description = 'Mengupdate data user';
                    break;
                case 'enroll_course':
                    $action_description = 'Mendaftar mata kuliah';
                    break;
                case 'submit_assignment':
                    $action_description = 'Mengumpulkan tugas';
                    break;
                case 'start_session':
                    $action_description = 'Memulai sesi lab';
                    break;
                case 'end_session':
                    $action_description = 'Mengakhiri sesi lab';
                    break;
                default:
                    $action_description = ucfirst(str_replace('_', ' ', $activity['action']));
            }
            
            $recent_activities[] = [
                'user' => $activity['user_name'],
                'action' => $action_description,
                'time' => $time_display
            ];
        }
        
        // If no activities in database, use some default ones based on recent user registrations
        if (empty($recent_activities)) {
            $recent_users_query = "SELECT full_name, created_at FROM users ORDER BY created_at DESC LIMIT 3";
            $recent_users_stmt = $conn->prepare($recent_users_query);
            $recent_users_stmt->execute();
            $recent_users = $recent_users_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($recent_users as $user) {
                $time_diff = time() - strtotime($user['created_at']);
                if ($time_diff < 86400) {
                    $time_display = floor($time_diff / 3600) . ' jam lalu';
                } else {
                    $time_display = floor($time_diff / 86400) . ' hari lalu';
                }
                
                $recent_activities[] = [
                    'user' => $user['full_name'],
                    'action' => 'Mendaftar sebagai user baru',
                    'time' => $time_display
                ];
            }
        }
        
        // Update notifications with real data
        $new_users_today_query = "SELECT COUNT(*) as total FROM users WHERE DATE(created_at) = CURDATE()";
        $new_users_stmt = $conn->prepare($new_users_today_query);
        $new_users_stmt->execute();
        $new_users_result = $new_users_stmt->fetch(PDO::FETCH_ASSOC);
        $new_users_count = $new_users_result['total'];
        
        // Update the notification message
        $notifications[1]['message'] = $new_users_count . ' user baru terdaftar hari ini';
        
    } catch (Exception $e) {
        // If error, use default simulated data
        $stats = [
            'total_users' => 0,
            'total_courses' => 0,
            'total_labs' => 0,
            'active_sessions' => 0
        ];
        
        $recent_activities = [
            ['user' => 'System', 'action' => 'Database connection error', 'time' => 'Sekarang'],
        ];
    }
} else {
    // If no connection, show default data
    $recent_activities = [
        ['user' => 'System', 'action' => 'Database tidak terhubung', 'time' => 'Sekarang'],
    ];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Virtual Lab System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="admin.css" rel="stylesheet">
</head>
<body>
    
    <?php include 'sidebar_admin.php'; ?>

    <div class="main-content">
         <?php include 'header_admin.php' ?>

        <div class="content">
            <h1 class="page-title">Monitor Sesi Virtual Lab</h1>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-number"><?php echo $stats['total_users']; ?></div>
                            <div class="stat-label">Total Users</div>
                        </div>
                        <div class="stat-icon users">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-number"><?php echo $stats['total_courses']; ?></div>
                            <div class="stat-label">Mata Kuliah</div>
                        </div>
                        <div class="stat-icon courses">
                            <i class="fas fa-book"></i>
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-number"><?php echo $stats['total_labs']; ?></div>
                            <div class="stat-label">Laboratorium</div>
                        </div>
                        <div class="stat-icon labs">
                            <i class="fas fa-desktop"></i>
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-number"><?php echo $stats['active_sessions']; ?></div>
                            <div class="stat-label">Sesi Aktif</div>
                        </div>
                        <div class="stat-icon sessions">
                            <i class="fas fa-play-circle"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dashboard -->
            <div class="dashboard">
                <!-- Recent Activities -->
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-clock"></i>
                        Aktivitas Terbaru
                    </div>
                    <div class="card-body">
                        <?php if (!empty($recent_activities)): ?>
                            <?php foreach($recent_activities as $activity): ?>
                                <div class="activity-item">
                                    <div class="activity-info">
                                        <h4><?php echo htmlspecialchars($activity['user']); ?></h4>
                                        <p><?php echo htmlspecialchars($activity['action']); ?></p>
                                    </div>
                                    <div class="activity-time">
                                        <?php echo htmlspecialchars($activity['time']); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="no-activity">
                                <i class="fas fa-info-circle"></i>
                                Belum ada aktivitas yang tercatat
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Notifications -->
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-bell"></i>
                        notifikasi
                    </div>
                    <div class="card-body">
                        <?php foreach($notifications as $notification): ?>
                            <div class="notification-item">
                                <div class="notification-icon <?php echo $notification['type']; ?>">
                                    <?php 
                                    $icons = [
                                        'urgent' => 'fas fa-exclamation-triangle',
                                        'warning' => 'fas fa-exclamation-circle',
                                        'info' => 'fas fa-info-circle'
                                    ];
                                    echo '<i class="' . $icons[$notification['type']] . '"></i>';
                                    ?>
                                </div>
                                <div class="notification-content">
                                    <h4><?php echo htmlspecialchars($notification['title']); ?></h4>
                                    <p><?php echo htmlspecialchars($notification['message']); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <a href="kelola_pengguna.php" class="action-btn">
                    <i class="fas fa-user-plus"></i>
                    Tambah User Baru
                </a>
                <a href="notifikasi.php" class="action-btn">
                    <i class="fas fa-bell"></i>
                    Notifikasi
                </a>
                <a href="kelola_lab.php" class="action-btn">
                    <i class="fas fa-power-off"></i>
                    Nonaktifkan Lab
                </a>
            </div>
        </div>
    </div>

    <script>
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('id-ID', { 
                hour: '2-digit', 
                minute: '2-digit',
                hour12: false 
            });
            document.getElementById('current-time').textContent = timeString;
        }

        // Update time immediately and then every second
        updateTime();
        setInterval(updateTime, 1000);

        // Auto refresh page every 5 minutes to get latest data
        setTimeout(function() {
            location.reload();
        }, 300000); // 5 minutes
    </script>
</body>
</html>