<?php

// Aktifkan error reporting untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'auth_admin.php'; 
require_once '../koneksi.php'; 
require_once 'NotificationManager.php';

// Initialize database connection
$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    die("Koneksi database gagal!");
}

$notificationManager = new NotificationManager($conn);

$message = '';
$messageType = '';

// Handle form submission
if ($_POST) {
    $title = trim($_POST['title']);
    $content = trim($_POST['message']);
    $type = $_POST['type'];
    $target = $_POST['target'];
    $recipients = isset($_POST['recipients']) ? $_POST['recipients'] : [];

    if (empty($title) || empty($content)) {
        $message = 'Judul dan pesan notifikasi harus diisi!';
        $messageType = 'error';
    } else {
        // Determine recipients based on target
        $targetRecipients = [];
        
        if ($target === 'all') {
            // Send to all users (handled in sendNotification method)
            $targetRecipients = [];
        } elseif ($target === 'mahasiswa') {
            $users = $notificationManager->getUsersByRole('mahasiswa');
            $targetRecipients = array_column($users, 'user_id');
            if (empty($targetRecipients)) {
                $message = 'Tidak ada mahasiswa yang ditemukan!';
                $messageType = 'error';
            } else {
                // Debug: tampilkan berapa mahasiswa yang ditemukan
                error_log("Found " . count($targetRecipients) . " mahasiswa: " . implode(', ', $targetRecipients));
            }
        } elseif ($target === 'dosen') {
            $users = $notificationManager->getUsersByRole('dosen');
            $targetRecipients = array_column($users, 'user_id');
            if (empty($targetRecipients)) {
                $message = 'Tidak ada dosen yang ditemukan!';
                $messageType = 'error';
            } else {
                // Debug: tampilkan berapa dosen yang ditemukan
                error_log("Found " . count($targetRecipients) . " dosen: " . implode(', ', $targetRecipients));
            }
        } elseif ($target === 'specific') {
            $targetRecipients = $recipients;
            if (empty($targetRecipients)) {
                $message = 'Silakan pilih minimal satu penerima!';
                $messageType = 'error';
            }
        }

        // Only send if we have valid recipients or it's "all" target, and no previous errors
        if (empty($message)) {
            $result = $notificationManager->sendNotification($title, $content, $type, $targetRecipients, $admin_user['user_id']);
            
            if ($result['success']) {
                $message = 'Notifikasi berhasil dikirim ke ' . $result['sent_count'] . ' pengguna!';
                $messageType = 'success';
            } else {
                $message = 'Gagal mengirim notifikasi: ' . $result['message'];
                $messageType = 'error';
            }
        }
    }
}

// Get all users for recipient selection
$allUsers = $notificationManager->getAllUsers();
$notificationHistory = $notificationManager->getNotificationHistory(20);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kirim Notifikasi - Virtual Lab System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="admin.css" rel="stylesheet">
</head>
<body>
    
    <?php 
    $current_page = basename($_SERVER['SCRIPT_NAME']);
    include 'sidebar_admin.php' ?>

    <div class="main-content">
        
    <?php include 'header_admin.php' ?>

        <div class="content">
            <h1 class="page-title">Kirim Notifikasi</h1>


            <!-- Notification Form Card -->
            <div class="card" style="margin-bottom: 30px;">
                <div class="card-header">
                    <i class="fas fa-paper-plane"></i>
                    Detail Notifikasi
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="form-group">
                            <label class="form-label">Judul Notification *</label>
                            <input type="text" name="title" class="form-input" placeholder="Masukkan judul notifikasi" required>
                            <small style="color: #666; font-size: 12px;">Tulis Judul dari pesan Anda</small>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Pesan *</label>
                            <textarea name="message" class="form-textarea" placeholder="Tulis pesan notifikasi disini..." required style="min-height: 120px;"></textarea>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Tipe</label>
                                <select name="type" class="form-select" onchange="updatePreview()">
                                    <option value="info">Info</option>
                                    <option value="warning">Peringatan</option>
                                    <option value="urgent">Urgent</option>
                                    <option value="success">Sukses</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Status</label>
                                <select class="form-select" disabled>
                                    <option>Siap Dikirim</option>
                                </select>
                            </div>
                        </div>

                        <!-- Pilih Penerima Section -->
                        <div style="margin: 30px 0;">
                            <div style="display: flex; align-items: center; margin-bottom: 15px;">
                                <i class="fas fa-users" style="margin-right: 10px; color: #051F20;"></i>
                                <span style="font-weight: 600; color: #333;">Pilih Penerima</span>
                            </div>
                            
                            <div class="radio-group" style="margin-bottom: 20px;">
                                <div class="radio-item">
                                    <input type="radio" name="target" value="mahasiswa" id="target-mahasiswa" checked onchange="toggleRecipients()">
                                    <label for="target-mahasiswa">Semua Mahasiswa</label>
                                </div>
                                <div class="radio-item">
                                    <input type="radio" name="target" value="dosen" id="target-dosen" onchange="toggleRecipients()">
                                    <label for="target-dosen">Semua Dosen</label>
                                </div>
                            </div>

                            <!-- Recipients Grid -->
                            <div id="specific-recipients" style="display: none;">
                                <div style="border: 1px solid #e1e1e1; border-radius: 8px; padding: 15px; background-color: #f8f9fa;">
                                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 15px;">
                                        <?php foreach ($allUsers as $user): ?>
                                            <div style="display: flex; align-items: center; gap: 10px; padding: 8px; background: white; border-radius: 6px; border: 1px solid #e1e1e1;">
                                                <input type="checkbox" name="recipients[]" value="<?php echo $user['user_id']; ?>" id="user-<?php echo $user['user_id']; ?>">
                                                <div style="display: flex; align-items: center; gap: 8px; flex: 1;">
                                                    <i class="fas fa-user" style="color: #051F20;"></i>
                                                    <div>
                                                        <div style="font-weight: 500; font-size: 13px;"><?php echo htmlspecialchars($user['user_id']); ?></div>
                                                        <div style="font-size: 11px; color: #666;"><?php echo isset($user['role']) ? ucfirst($user['role']) : 'Mahasiswa'; ?></div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div style="display: flex; gap: 10px; justify-content: flex-end;">
                            <button type="button" class="btn btn-secondary" onclick="resetForm()">
                                <i class="fas fa-undo"></i>
                                Reset
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i>
                                Kirim Notifikasi
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Notification History -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-history"></i>
                    Notifikasi Terbaru
                    <button class="btn btn-sm btn-secondary" onclick="location.reload()" style="margin-left: auto;">
                        <i class="fas fa-sync-alt"></i>
                        Refresh
                    </button>
                </div>
                <div class="card-body">
                    <?php if (empty($notificationHistory)): ?>
                        <div class="no-activity">
                            <i class="fas fa-bell-slash"></i>
                            <p>Belum ada notifikasi yang dikirim</p>
                        </div>
                    <?php else: ?>
                        <div class="table" style="overflow-x: auto;">
                            <table style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr style="background-color: #f8f9fa;">
                                        <th style="padding: 12px; text-align: left; border-bottom: 1px solid #dee2e6; font-weight: 600; font-size: 13px;">TANGGAL</th>
                                        <th style="padding: 12px; text-align: left; border-bottom: 1px solid #dee2e6; font-weight: 600; font-size: 13px;">JUDUL</th>
                                        <th style="padding: 12px; text-align: left; border-bottom: 1px solid #dee2e6; font-weight: 600; font-size: 13px;">PENERIMA</th>
                                        <th style="padding: 12px; text-align: left; border-bottom: 1px solid #dee2e6; font-weight: 600; font-size: 13px;">STATUS</th>
                                        <th style="padding: 12px; text-align: left; border-bottom: 1px solid #dee2e6; font-weight: 600; font-size: 13px;">AKSI</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($notificationHistory as $notification): ?>
                                        <tr style="border-bottom: 1px solid #f0f0f0;">
                                            <td style="padding: 12px; font-size: 12px; color: #666;">
                                                <?php echo date('d M Y', strtotime($notification['created_at'])); ?><br>
                                                <span style="color: #999;"><?php echo date('H:i', strtotime($notification['created_at'])); ?></span>
                                            </td>
                                            <td style="padding: 12px;">
                                                <div style="font-weight: 500; margin-bottom: 4px; font-size: 14px;">
                                                    <?php echo htmlspecialchars($notification['title']); ?>
                                                </div>
                                                <div style="font-size: 12px; color: #666;">
                                                    <?php echo htmlspecialchars(substr($notification['message'], 0, 50)) . (strlen($notification['message']) > 50 ? '...' : ''); ?>
                                                </div>
                                            </td>
                                            <td style="padding: 12px; font-size: 12px;">
                                                <?php 
                                                $read_percentage = $notification['total_recipients'] > 0 ? 
                                                    round(($notification['read_count'] / $notification['total_recipients']) * 100) : 0;
                                                ?>
                                                <?php echo $notification['total_recipients']; ?> pengguna<br>
                                                <span style="color: #28a745;"><?php echo $notification['read_count']; ?> terbaca (<?php echo $read_percentage; ?>%)</span>
                                            </td>
                                            <td style="padding: 12px;">
                                                <span class="status-badge status-sent">Terkirim</span>
                                            </td>
                                            <td style="padding: 12px;">
                                                <button class="btn btn-sm btn-secondary" onclick="alert('Detail: <?php echo addslashes($notification['title']); ?>')">
                                                    Detail
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
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

        // Toggle recipients selection
        function toggleRecipients() {
            const specificDiv = document.getElementById('specific-recipients');
            const specificRadio = document.getElementById('target-specific');
            
            if (specificRadio.checked) {
                specificDiv.style.display = 'block';
            } else {
                specificDiv.style.display = 'none';
            }
        }

        // Reset form
        function resetForm() {
            document.querySelector('form').reset();
            document.getElementById('specific-recipients').style.display = 'none';
        }

        // Preview notification (bisa ditambahkan nanti)
        function updatePreview() {
            // Preview functionality bisa ditambahkan di sini
        }
    </script>
</body>
</html>