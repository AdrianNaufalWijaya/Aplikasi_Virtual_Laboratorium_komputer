<?php
session_start();
require_once '../koneksi.php';
date_default_timezone_set('Asia/Jakarta');

// Create database connection
$database = new Database();
$pdo = $database->getConnection();

// Check if user is logged in and is mahasiswa
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mahasiswa') {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];

// Ambil ID Lab dan Komputer dari URL
$lab_id = isset($_GET['lab_id']) ? intval($_GET['lab_id']) : 0;
$computer_id = isset($_GET['computer_id']) ? intval($_GET['computer_id']) : 0;

if (!$lab_id || !$computer_id) {
    header('Location: lab_virtual.php');
    exit();
}

// Fungsi untuk mengambil detail lab dan komputer
function getReservationDetails($pdo, $lab_id, $computer_id) {
    $sql = "SELECT l.lab_name, vc.computer_name 
            FROM laboratory l 
            JOIN virtual_computer vc ON l.lab_id = vc.lab_id 
            WHERE l.lab_id = ? AND vc.computer_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$lab_id, $computer_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

$details = getReservationDetails($pdo, $lab_id, $computer_id);
if (!$details) {
    header('Location: lab_virtual.php?error=notfound');
    exit();
}

function getLabSoftware($pdo, $lab_id) {
    $sql = "SELECT s.software_id, s.software_name, s.category 
            FROM software s
            JOIN lab_software ls ON s.software_id = ls.software_id
            WHERE s.status = 'active' AND ls.lab_id = ?
            ORDER BY s.software_name ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$lab_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
$software_list = getLabSoftware($pdo, $lab_id);

$lab_name = $details['lab_name'];
$computer_name = $details['computer_name'];


// Logika untuk menangani pengiriman form (POST request)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reservation_date = $_POST['reservation_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $purpose = trim($_POST['purpose']);
    $software_needed = $_POST['software_needed'];
    $submitted_computer_id = $_POST['computer_id'];

    // Validasi
    $today = date('Y-m-d');
    $now = date('H:i');
    
    if ($reservation_date < $today || ($reservation_date == $today && $start_time < $now)) {
        $error_message = "Tanggal atau waktu reservasi tidak boleh di masa lalu.";
    } elseif ($end_time <= $start_time) {
        $error_message = "Waktu selesai harus setelah waktu mulai.";
    } else {
        // Jika validasi dasar lolos, simpan ke database
        try {
            // Query INSERT sekarang menyertakan computer_id dan software_needed
            $sql = "INSERT INTO reservation (user_id, lab_id, computer_id, reservation_date, start_time, end_time, purpose, software_needed, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
            $stmt = $pdo->prepare($sql);
            
            if ($stmt->execute([$user_id, $lab_id, $submitted_computer_id, $reservation_date, $start_time, $end_time, $purpose, $software_needed])) {
                $success_message = "Permintaan reservasi untuk " . htmlspecialchars($computer_name) . " telah berhasil dikirim!";
            } else {
                $error_message = "Terjadi kesalahan. Gagal mengirim permintaan reservasi.";
            }
        } catch (PDOException $e) {
            $error_message = "Gagal menyimpan reservasi. Kemungkinan ada jadwal yang bentrok.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservasi: <?php echo htmlspecialchars($computer_name); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="mahasiswa.css">
</head>
<body>
    <?php include 'sidebar_mahasiswa.html'; ?>
    <div class="main-content">
        <div class="top-nav">
            <div class="nav-left">
                <h1 class="page-title">Buat Reservasi</h1>
                <div class="breadcrumb">
                    <a href="dashboard_mahasiswa.php">Home</a><i class="fas fa-chevron-right"></i>
                    <a href="lab_virtual.php">Lab Virtual</a><i class="fas fa-chevron-right"></i>
                    <span>Reservasi</span>
                </div>
            </div>
            <div class="nav-right">
                <div class="user-menu">
                    <div class="user-avatar"><?php echo strtoupper(substr($full_name, 0, 1)); ?></div>
                    <div class="dropdown-menu" id="userDropdown">
                        <a href="../logout.php" class="dropdown-item logout">Log out</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="content-area">
            <div class="section">
                <h2 class="section-title">Formulir Reservasi untuk Komputer: <?php echo htmlspecialchars($computer_name); ?></h2>
                <p style="color: #6c757d; margin-top:-15px; margin-bottom: 20px;">Lokasi: <?php echo htmlspecialchars($lab_name); ?></p>

                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                        <p class="mt-10">Anda akan diarahkan kembali ke halaman detail lab dalam 5 detik...</p>
                        <script>
                            setTimeout(function() {
                                window.location.href = 'lab_detail.php?id=<?php echo $lab_id; ?>';
                            }, 5000);
                        </script>
                    </div>
                <?php endif; ?>
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
                <?php endif; ?>

                <?php if (!isset($success_message)): ?>
                <form method="POST" action="reservasi.php?lab_id=<?php echo $lab_id; ?>&computer_id=<?php echo $computer_id; ?>">
                    <input type="hidden" name="computer_id" value="<?php echo $computer_id; ?>">
                    
                    <div class="form-group">
                        <label for="reservation_date" class="form-label">Tanggal Reservasi</label>
                        <input type="date" id="reservation_date" name="reservation_date" class="form-input" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label for="start_time" class="form-label">Waktu Mulai</label>
                        <input type="time" id="start_time" name="start_time" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label for="end_time" class="form-label">Waktu Selesai</label>
                        <input type="time" id="end_time" name="end_time" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label for="purpose" class="form-label">Keperluan</label>
                        <textarea id="purpose" name="purpose" class="form-textarea" rows="2" placeholder="Contoh: Praktikum Basis Data" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="software_needed" class="form-label">Software Utama yang Digunakan</label>
                        <select id="software_needed" name="software_needed" class="form-select" required>
                            <option value="" disabled selected>-- Pilih Software --</option>
                            <?php foreach ($software_list as $software): ?>
                                <option value="<?php echo htmlspecialchars($software['software_name']); ?>">
                                    <?php echo htmlspecialchars($software['software_name'] . ' (' . ucfirst($software['category']) . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                            <option value="lainnya">Lainnya...</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Kirim Permintaan</button>
                    <a href="lab_detail.php?id=<?php echo $lab_id; ?>" class="btn btn-secondary">Batal</a>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script src="sidebar.js"></script>
</body>
</html>