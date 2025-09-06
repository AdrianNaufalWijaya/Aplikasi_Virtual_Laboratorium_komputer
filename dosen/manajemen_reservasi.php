<?php
session_start();
require_once '../koneksi.php';

// Cek jika user sudah login dan rolenya adalah dosen
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'dosen') {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];

$database = new Database();
$pdo = $database->getConnection();

// --- LOGIKA UNTUK MENANGANI AKSI APPROVE/REJECT ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reservation_id'])) {
    $reservation_id = $_POST['reservation_id'];
    $action = $_POST['action'];
    $computer_id = $_POST['computer_id'] ?? null;
    $new_status = '';

    if ($action === 'approve' && !empty($computer_id)) {
        $new_status = 'confirmed';
        $sql = "UPDATE reservation SET status = ?, approved_by = ?, computer_id = ? WHERE reservation_id = ?";
        $params = [$new_status, $user_id, $computer_id, $reservation_id];
    } elseif ($action === 'reject') {
        $new_status = 'rejected';
        $sql = "UPDATE reservation SET status = ?, approved_by = ? WHERE reservation_id = ?";
        $params = [$new_status, $user_id, $reservation_id];
    }

    if (!empty($new_status)) {
        try {
            $stmt = $pdo->prepare($sql);
            if ($stmt->execute($params)) {
                $success_message = "Status reservasi berhasil diperbarui.";
            } else {
                $error_message = "Gagal memperbarui status reservasi.";
            }
        } catch (PDOException $e) {
            $error_message = "Database error: " . $e->getMessage();
        }
    } else {
        $error_message = "Aksi tidak valid atau komputer belum dipilih.";
    }
}


// --- FUNGSI UNTUK MENGAMBIL DATA RESERVASI ---
function getPendingReservations($pdo) {
    $sql = "SELECT r.reservation_id, r.lab_id, r.reservation_date, r.start_time, r.end_time, r.purpose,
                   u.full_name as student_name, l.lab_name
            FROM reservation r
            JOIN users u ON r.user_id = u.user_id
            JOIN laboratory l ON r.lab_id = l.lab_id
            WHERE r.status = 'pending'
            ORDER BY r.created_at ASC";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Ambil data reservasi yang pending
$pending_reservations = getPendingReservations($pdo);

$sql_pending = "SELECT COUNT(e.enrollment_id) as total
                FROM enrollment e
                JOIN course c ON e.course_id = c.course_id
                WHERE e.status = 'pending' AND c.id_dosen = ?";
$stmt_pending = $pdo->prepare($sql_pending);
$stmt_pending->execute([$user_id]);
$pending_count = $stmt_pending->fetch(PDO::FETCH_ASSOC)['total'];

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Reservasi</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="dosen.css">
</head>
<body>
    <button class="mobile-menu-btn"><i class="fas fa-bars"></i></button>
    <?php include 'sidebar_dosen.html'; ?>

    <div class="main-content">
        <header class="header">
            <h1>Manajemen Reservasi</h1>
            <p style="color: #6c757d;">Setujui atau tolak permintaan reservasi dari mahasiswa.</p>
        </header>

        <div class="section">
            <h2 class="section-title">Permintaan Reservasi Baru</h2>
            <div style="overflow-x: auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Mahasiswa</th>
                            <th>Laboratorium</th>
                            <th>Tanggal</th>
                            <th>Waktu</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_reservations as $res): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($res['student_name']); ?></td>
                            <td><?php echo htmlspecialchars($res['lab_name']); ?></td>
                            <td><?php echo date('d M Y', strtotime($res['reservation_date'])); ?></td>
                            <td><?php echo date('H:i', strtotime($res['start_time'])) . ' - ' . date('H:i', strtotime($res['end_time'])); ?></td>
                            <td class="action-buttons">
                                <form method="POST" style="display:inline;">
                                    <button type="button" class="btn btn-approve" title="Setujui" 
                                            onclick="openApproveModal(
                                                <?php echo $res['reservation_id']; ?>, 
                                                '<?php echo $res['lab_id']; ?>',
                                                '<?php echo $res['reservation_date']; ?>',
                                                '<?php echo $res['start_time']; ?>',
                                                '<?php echo $res['end_time']; ?>'
                                            )">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    
                                    <input type="hidden" name="reservation_id" value="<?php echo $res['reservation_id']; ?>">
                                    <button type="submit" name="action" value="reject" class="btn btn-reject" title="Tolak" onclick="return confirm('Anda yakin ingin menolak reservasi ini?')">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="approveModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Setujui & Tetapkan Komputer</h3>
                <button class="modal-close" onclick="closeApproveModal()">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="reservation_id" id="modal_reservation_id">
                <input type="hidden" name="action" value="approve">

                <div class="form-group">
                    <label for="computer_id" class="form-label">Pilih Komputer yang Tersedia:</label>
                    <select name="computer_id" id="modal_computer_select" class="form-select" required>
                        <option value="">Memuat komputer...</option>
                    </select>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeApproveModal()">Batal</button>
                    <button type="submit" class="btn btn-approve">Konfirmasi Persetujuan</button>
                </div>
            </form>
        </div>
    </div>

    <script src="dosen.js"></script>

    <script>
        const modal = document.getElementById('approveModal');
        const reservationIdInput = document.getElementById('modal_reservation_id');
        const computerSelect = document.getElementById('modal_computer_select');

        function openApproveModal(reservationId, labId, date, startTime, endTime) {
            reservationIdInput.value = reservationId;
            computerSelect.innerHTML = '<option value="">Memuat komputer...</option>';
            modal.style.display = 'flex';

            // Panggil file PHP untuk mendapatkan daftar komputer via AJAX
            const url = `get_available_computers.php?lab_id=${labId}&date=${date}&start=${startTime}&end=${endTime}`;
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    computerSelect.innerHTML = '<option value="" disabled selected>-- Pilih Komputer --</option>';
                    if (data.length > 0) {
                        data.forEach(computer => {
                            const option = document.createElement('option');
                            option.value = computer.computer_id;
                            option.textContent = computer.computer_name;
                            computerSelect.appendChild(option);
                        });
                    } else {
                        computerSelect.innerHTML = '<option value="" disabled>Tidak ada komputer tersedia</option>';
                    }
                })
                .catch(error => {
                    console.error('Error fetching computers:', error);
                    computerSelect.innerHTML = '<option value="" disabled>Gagal memuat data</option>';
                });
        }

        function closeApproveModal() {
            modal.style.display = 'none';
        }

        // Tutup modal jika user klik di luar area konten
        window.onclick = function(event) {
            if (event.target == modal) {
                closeApproveModal();
            }
        }
        document.getElementById('nav-reservasi').classList.add('active');
    </script>
</body>
</html>