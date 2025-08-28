<?php

session_start();
require_once '../koneksi.php';

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($full_name) || empty($username) || empty($email) || empty($password)) {
        $error_message = 'Semua field wajib diisi.';
    } elseif ($password !== $confirm_password) {
        $error_message = 'Konfirmasi password tidak cocok.';
    } else {
        $database = new Database();
        $conn = $database->getConnection();
        
        // Cek duplikasi username atau email
        $sql_check = "SELECT user_id FROM users WHERE username = :username OR email = :email";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->execute(['username' => $username, 'email' => $email]);

        if ($stmt_check->rowCount() > 0) {
            $error_message = 'Username atau email sudah terdaftar.';
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Simpan admin baru ke database
            $sql_insert = "INSERT INTO users (full_name, username, email, password, role, status) 
                           VALUES (:full_name, :username, :email, :password, 'admin', 'pending')";
            $stmt_insert = $conn->prepare($sql_insert);
            
            if ($stmt_insert->execute([
                'full_name' => $full_name,
                'username' => $username,
                'email' => $email,
                'password' => $hashed_password
            ])) {
                $_SESSION['register_success'] = 'Registrasi admin baru berhasil. Silakan login.';
                header('Location: login_admin.php');
                exit();
            } else {
                $error_message = 'Terjadi kesalahan. Gagal mendaftarkan admin baru.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Register Admin - Virtual Lab</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="login_admin.css"> </head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h2 class="login-title">Registrasi Admin Baru</h2>
            <p class="login-subtitle">Buat akun untuk administrator baru</p>
        </div>

        <?php if (!empty($error_message)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle" style="margin-right: 8px;"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="input-group">
                <i class="fas fa-user-circle input-icon"></i>
                <input type="text" name="full_name" class="form-input" placeholder="Nama Lengkap" required>
            </div>
            <div class="input-group">
                <i class="fas fa-user input-icon"></i>
                <input type="text" name="username" class="form-input" placeholder="Username" required>
            </div>
            <div class="input-group">
                <i class="fas fa-envelope input-icon"></i>
                <input type="email" name="email" class="form-input" placeholder="Email" required>
            </div>
            <div class="input-group">
                <i class="fas fa-lock input-icon"></i>
                <input type="password" name="password" class="form-input" placeholder="Password" required>
            </div>
            <div class="input-group">
                <i class="fas fa-check-circle input-icon"></i>
                <input type="password" name="confirm_password" class="form-input" placeholder="Konfirmasi Password" required>
            </div>
            <button type="submit" class="btn-login">
                <i class="fas fa-user-plus"></i>
                Register
            </button>
        </form>
        <p style="margin-top: 20px; font-size: 14px; color: #555;">
            Sudah punya akun? <a href="login_admin.php" style="color: #051F20; text-decoration: none; font-weight: 600;">Login di sini</a>
        </p>
    </div>
</body>
</html>