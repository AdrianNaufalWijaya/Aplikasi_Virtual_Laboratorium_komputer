

<?php
require_once 'auth_admin.php'; // Pastikan hanya admin yang bisa akses
require_once '../koneksi.php';

$database = new Database();
$conn = $database->getConnection();

// Logika untuk menyetujui admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_user_id'])) {
    $user_to_approve = $_POST['approve_user_id'];
    $sql_approve = "UPDATE users SET status = 'active' WHERE user_id = ? AND role = 'admin'";
    $stmt_approve = $conn->prepare($sql_approve);
    if ($stmt_approve->execute([$user_to_approve])) {
        $message = "Admin berhasil diverifikasi!";
        $message_type = "success";
    }
}

// Ambil daftar admin yang statusnya 'pending'
$sql_pending = "SELECT user_id, full_name, username, email, created_at FROM users WHERE role = 'admin' AND status = 'pending' ORDER BY created_at ASC";
$stmt_pending = $conn->prepare($sql_pending);
$stmt_pending->execute();
$pending_admins = $stmt_pending->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Verifikasi Admin</title>
    <link href="admin.css" rel="stylesheet">
</head>
<body>
    <?php 
        $current_page = 'verifikasi_admin.php'; // Untuk menandai menu aktif di sidebar
        include 'sidebar_admin.php'; 
    ?>
    <div class="main-content">
        <?php include 'header_admin.php'; ?>
        <div class="content">
            <div class="page-header">
                <h1 class="page-title">Verifikasi Pendaftaran Admin</h1>
            </div>

            <?php if (!empty($message)): ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <i class="fas fa-user-check"></i>
                    Permintaan Pendaftaran Baru
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nama Lengkap</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Tanggal Daftar</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($pending_admins)): ?>
                                <tr><td colspan="5" style="text-align: center;">Tidak ada permintaan verifikasi baru.</td></tr>
                            <?php else: ?>
                                <?php foreach ($pending_admins as $admin): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($admin['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($admin['username']); ?></td>
                                    <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                    <td><?php echo date('d M Y, H:i', strtotime($admin['created_at'])); ?></td>
                                    <td>
                                        <form method="POST" onsubmit="return confirm('Anda yakin ingin menyetujui admin ini?');">
                                            <input type="hidden" name="approve_user_id" value="<?php echo $admin['user_id']; ?>">
                                            <button type="submit" class="btn btn-success btn-sm">
                                                <i class="fas fa-check"></i> Setujui
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>