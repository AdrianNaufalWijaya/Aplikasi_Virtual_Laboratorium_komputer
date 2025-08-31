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

// Handle password change
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_message = 'Semua field password harus diisi!';
    } elseif ($new_password !== $confirm_password) {
        $error_message = 'Konfirmasi password baru tidak cocok!';
    } elseif (strlen($new_password) < 6) {
        $error_message = 'Password baru minimal 6 karakter!';
    } else {
        // Verify current password
        $verify_sql = "SELECT password FROM users WHERE user_id = ?";
        $verify_stmt = $pdo->prepare($verify_sql);
        $verify_stmt->execute([$user_id]);
        $current_user = $verify_stmt->fetch();
        
        if ($current_user && password_verify($current_password, $current_user['password'])) {
            // Update password
            $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $update_sql = "UPDATE users SET password = ?, updated_at = NOW() WHERE user_id = ?";
            $update_stmt = $pdo->prepare($update_sql);
            
            if ($update_stmt->execute([$new_password_hash, $user_id])) {
                $success_message = 'Password berhasil diubah!';
            } else {
                $error_message = 'Gagal mengubah password!';
            }
        } else {
            $error_message = 'Password lama tidak benar!';
        }
    }
}

// Get user profile data
function getUserProfile($pdo, $user_id) {
    $sql = "SELECT * FROM users WHERE user_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get user statistics
function getUserStats($pdo, $user_id) {
    // Get enrollment count
    $enrollment_sql = "SELECT COUNT(*) as total_courses FROM enrollment WHERE user_id = ? AND status = 'approved'";
    $enrollment_stmt = $pdo->prepare($enrollment_sql);
    $enrollment_stmt->execute([$user_id]);
    $enrollment_result = $enrollment_stmt->fetch();
    
    // Get submission count
    $submission_sql = "SELECT COUNT(*) as total_assignments FROM submission WHERE id_mahasiswa = ?";
    $submission_stmt = $pdo->prepare($submission_sql);
    $submission_stmt->execute([$user_id]);
    $submission_result = $submission_stmt->fetch();
    
    // Get graded count
    $graded_sql = "SELECT COUNT(*) as total_graded FROM submission WHERE id_mahasiswa = ? AND status = 'graded'";
    $graded_stmt = $pdo->prepare($graded_sql);
    $graded_stmt->execute([$user_id]);
    $graded_result = $graded_stmt->fetch();
    
    return [
        'total_courses' => $enrollment_result['total_courses'] ?? 0,
        'total_assignments' => $submission_result['total_assignments'] ?? 0,
        'total_graded' => $graded_result['total_graded'] ?? 0
    ];
}

$userProfile = getUserProfile($pdo, $user_id);
$userStats = getUserStats($pdo, $user_id);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Dashboard Mahasiswa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="mahasiswa.css">
</head>
<body>
    <button class="mobile-menu-btn"><i class="fas fa-bars"></i></button>
    <?php include 'sidebar_mahasiswa.html'; ?>

    <div class="main-content">
        <div class="top-nav">
            <div class="nav-left">
                <h1 class="page-title">Profile Mahasiswa</h1>
                <div class="breadcrumb">
                    <a href="dashboard_mahasiswa.php">Home</a>
                    <i class="fas fa-chevron-right"></i>
                    <span>Profile</span>
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
            <div class="profile-layout">
                <div>
                    <div class="section mb-20">
                        <div class="d-flex align-items-center gap-15">
                            <div class="user-avatar" style="width: 80px; height: 80px; font-size: 32px;">
                                <?php echo strtoupper(substr($full_name, 0, 1)); ?>
                            </div>
                            <div>
                                <h2 style="font-size: 24px; margin-bottom: 5px;"><?php echo htmlspecialchars($userProfile['full_name']); ?></h2>
                                <p style="color: #6c757d;">@<?php echo htmlspecialchars($userProfile['username']); ?></p>
                            </div>
                        </div>
                        <hr style="margin: 20px 0; border: 1px solid #f0f0f0;">
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
                            <div class="info-item"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($userProfile['email']); ?></div>
                            <div class="info-item"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($userProfile['phone_number'] ?? '-'); ?></div>
                            <div class="info-item"><i class="fas fa-user-tag"></i> <?php echo ucfirst($userProfile['role']); ?></div>
                            <div class="info-item"><i class="fas fa-calendar-plus"></i> Terdaftar: <?php echo date('d M Y', strtotime($userProfile['created_at'])); ?></div>
                            <div class="info-item"><i class="fas fa-sign-in-alt"></i> Login Terakhir: <?php echo $userProfile['last_login'] ? date('d M Y H:i', strtotime($userProfile['last_login'])) : 'N/A'; ?></div>
                            <div class="info-item"><i class="fas fa-circle" style="color: #28a745;"></i> Status: <?php echo ucfirst($userProfile['status']); ?></div>
                        </div>
                    </div>

                    <div class="section">
                        <h3 class="section-title"><i class="fas fa-key"></i> Ganti Password</h3>
                        <?php if (isset($success_message)): ?>
                            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?></div>
                        <?php endif; ?>
                        <?php if (isset($error_message)): ?>
                            <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?></div>
                        <?php endif; ?>
                        <form method="POST" onsubmit="return confirm('Yakin ingin mengubah password?')">
                            <input type="hidden" name="action" value="change_password">
                            <div class="form-group">
                                <label class="form-label" for="current_password">Password Lama</label>
                                <input type="password" name="current_password" id="current_password" class="form-input" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="new_password">Password Baru</label>
                                <input type="password" name="new_password" id="new_password" class="form-input" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="confirm_password">Konfirmasi Password Baru</label>
                                <input type="password" name="confirm_password" id="confirm_password" class="form-input" required>
                            </div>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Ganti Password</button>
                        </form>
                    </div>
                </div>

                <div class="section">
                    <h3 class="section-title">Statistik Akademik</h3>
                    <div class="d-flex flex-column gap-15">
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $userStats['total_courses']; ?></div>
                            <div class="stat-label">Mata Kuliah Diikuti</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $userStats['total_assignments']; ?></div>
                            <div class="stat-label">Tugas Dikumpulkan</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $userStats['total_graded']; ?></div>
                            <div class="stat-label">Tugas Dinilai</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="mahasiswa.js"></script>
    <script>
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
</body>
</html>