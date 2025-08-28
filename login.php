<?php
session_start();
require_once 'koneksi.php';

$database = new Database();
$pdo = $database->getConnection();

if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['role'];
    if ($role === 'dosen') {
        header('Location: dosen/dashboard_dosen.php');
    } elseif ($role === 'mahasiswa') {
        header('Location: mahasiswa/dashboard_mahasiswa.php');
    }
    // Tambahkan role lain jika ada, misal admin
    exit();
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    // Tentukan role yang diharapkan dari tombol yang ditekan
    $expected_role = isset($_POST['login_as']) ? $_POST['login_as'] : '';

    if (empty($username) || empty($password) || empty($expected_role)) {
        $error_message = 'Semua field harus diisi!';
    } else {
        try {
            // Query mencari user berdasarkan username/email dan role yang diharapkan
            $stmt = $pdo->prepare("
                SELECT user_id, username, password, full_name, role, status 
                FROM users 
                WHERE (username = :username OR email = :username) AND role = :role AND status = 'active'
            ");
            $stmt->execute(['username' => $username, 'role' => $expected_role]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Login berhasil
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                
                // Update last_login
                $update_stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
                $update_stmt->execute([$user['user_id']]);
                
                // Redirect berdasarkan role
                if ($user['role'] === 'dosen') {
                    header('Location: dosen/dashboard_dosen.php');
                } else { // Mahasiswa
                    header('Location: mahasiswa/dashboard_mahasiswa.php');
                }
                exit();
                
            } else {
                // Pesan error dibuat lebih spesifik
                $error_message = "Login sebagai " . ucfirst($expected_role) . " gagal. Periksa kembali username dan password Anda.";
            }
        } catch (PDOException $e) {
            $error_message = 'Terjadi kesalahan sistem. Silakan coba lagi.';
            // error_log("Login error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Virtual Laboratory</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="login.css">
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1 class="login-title">Virtual Lab Login</h1>
            <p class="login-subtitle">Silakan masuk sesuai dengan peran Anda</p>
        </div>

        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php" id="loginForm">
            <div class="input-container">
                <i class="fas fa-user input-icon"></i>
                <input type="text" name="username" class="form-input" placeholder="Username atau Email" required>
            </div>

            <div class="input-container">
                <i class="fas fa-lock input-icon"></i>
                <input type="password" name="password" id="password" class="form-input" placeholder="Password" required>
                <i class="fas fa-eye password-toggle" id="togglePassword"></i>
            </div>

            <div class="form-actions">
                <button type="submit" name="login_as" value="mahasiswa" class="login-btn btn-mahasiswa">
                    <span class="btn-text">Login sebagai Mahasiswa</span>
                    <div class="loading-spinner"></div>
                </button>
                
                <button type="submit" name="login_as" value="dosen" class="login-btn btn-dosen">
                    <span class="btn-text">Login sebagai Dosen</span>
                    <div class="loading-spinner"></div>
                </button>
            </div>
        </form>
    </div>

    <script>
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });

        document.getElementById('loginForm').addEventListener('submit', function(event) {
            const clickedButton = event.submitter;
            clickedButton.classList.add('loading');
            clickedButton.querySelector('.btn-text').style.display = 'none';
        });
    </script>
</body>
</html>