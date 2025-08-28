<?php

session_start();
require_once '../koneksi.php';

// Cek jika admin sudah login
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header('Location: dashboard_admin.php');
    exit();
}

$error_message = '';

// Proses form login
if ($_SERVER['REQUEST_METHOD'] === 'POST') { 

    $username = $_POST['username'];
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error_message = 'Username dan password wajib diisi.';
    } else {
        $database = new Database();
        $conn = $database->getConnection();
        
        $sql = "SELECT * FROM users WHERE (username = :username OR email = :username) AND role = 'admin'";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (password_verify($password, $user['password'])) {
                if ($user['status'] === 'active') {
                    // Login berhasil, set session
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['role'] = $user['role'];
                    
                    header('Location: dashboard_admin.php');
                    exit();
                } elseif ($user['status'] === 'pending') {
                    $error_message = 'Akun Anda sedang menunggu verifikasi dari Super Admin.';
                } else { // Jika status 'inactive'
                    $error_message = 'Akun Anda tidak aktif.';
                }
            } else {
                $error_message = 'Username atau password salah.';
            }
        } else {
             $error_message = 'Username atau password salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Admin Login - Virtual Lab</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="login_admin.css"> 
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h2 class="login-title">Virtual Lab</h2>
            <p class="login-subtitle">Administrator Access</p>
        </div>

        <?php if (!empty($error_message)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle" style="margin-right: 8px;"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="input-group">
                <i class="fas fa-user input-icon"></i>
                <input type="text" name="username" class="form-input" placeholder="Username atau Email" required>
            </div>
            <div class="input-group">
                <i class="fas fa-lock input-icon"></i>
                <input type="password" name="password" class="form-input" placeholder="Password" required>
            </div>
            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i>
                Sign In
            </button>
        </form>

        <p style="margin-top: 20px; font-size: 14px; color: #555;">
            Belum punya akun? <a href="register_admin.php" style="color: #051F20; text-decoration: none; font-weight: 600;">Register di sini</a>
        </p>
    </div>
</body>
</html>