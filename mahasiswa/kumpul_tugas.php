<?php
session_start();
require_once '../koneksi.php';

// Membuat koneksi database
$database = new Database();
$pdo = $database->getConnection();

// Memeriksa apakah pengguna sudah login dan merupakan mahasiswa
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mahasiswa') {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];

// Ambil ID Tugas dari URL
$assignment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($assignment_id === 0) {
    header('Location: tugas.php');
    exit();
}

// Menangani form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submission_title = trim($_POST['submission_title']);
    $student_comment = trim($_POST['student_comment']);
    $posted_assignment_id = intval($_POST['assignment_id']);
    
    // Validasi assignment_id
    if ($posted_assignment_id !== $assignment_id) {
        $error_message = "Assignment ID tidak valid.";
    }
    // Validasi dasar
    elseif (empty($submission_title)) {
        $error_message = "Judul pengumpulan wajib diisi.";
    }
    // Cek apakah file ada dan valid
    elseif (!isset($_FILES['assignment_file']) || $_FILES['assignment_file']['error'] !== UPLOAD_ERR_OK) {
        $upload_errors = [
            UPLOAD_ERR_INI_SIZE => 'File terlalu besar (melebihi upload_max_filesize)',
            UPLOAD_ERR_FORM_SIZE => 'File terlalu besar (melebihi MAX_FILE_SIZE)',
            UPLOAD_ERR_PARTIAL => 'File hanya terupload sebagian',
            UPLOAD_ERR_NO_FILE => 'Tidak ada file yang dipilih',
            UPLOAD_ERR_NO_TMP_DIR => 'Folder temporary tidak ditemukan',
            UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file ke disk',
            UPLOAD_ERR_EXTENSION => 'Upload dihentikan oleh extension'
        ];
        
        $error_code = $_FILES['assignment_file']['error'];
        $error_message = isset($upload_errors[$error_code]) ? 
                        $upload_errors[$error_code] : 
                        "Error upload tidak diketahui (kode: $error_code)";
    } else {
        // Proses upload file
        $upload_dir = '../uploads/submissions/';
        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0777, true)) {
                $error_message = "Gagal membuat direktori upload.";
            }
        }
        
        if (!isset($error_message)) {
            $file = $_FILES['assignment_file'];
            $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            // Validasi ekstensi file
            $allowed_extensions = ['pdf', 'doc', 'docx', 'zip', 'rar', 'jpg', 'jpeg', 'png', 'txt'];
            if (!in_array($file_extension, $allowed_extensions)) {
                $error_message = "Format file tidak diizinkan. Gunakan: " . implode(', ', $allowed_extensions);
            }
            // Validasi ukuran file (max 10MB)
            elseif ($file['size'] > 10 * 1024 * 1024) {
                $error_message = "File terlalu besar. Maksimal 10MB.";
            } else {
                $file_name = time() . '_' . $user_id . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', basename($file['name']));
                $file_path = $upload_dir . $file_name;

                $db_file_path = 'uploads/submissions/' . $file_name; // Path untuk disimpan di database

                if (move_uploaded_file($file['tmp_name'], $file_path)) {
                    try {
                        // Cek apakah sudah pernah mengumpulkan
                        $check_sql = "SELECT submission_id FROM submission WHERE id_tugas = ? AND id_mahasiswa = ?";
                        $check_stmt = $pdo->prepare($check_sql);
                        $check_stmt->execute([$posted_assignment_id, $user_id]);

                        if ($check_stmt->rowCount() > 0) {
                            // Perbarui data (UPDATE)
                            $sql = "UPDATE submission SET file_path = ?, submission_title = ?, student_comment = ?, tanggal_dikumpulkan = NOW(), status = 'submitted' WHERE id_tugas = ? AND id_mahasiswa = ?";
                            $params = [$db_file_path, $submission_title, $student_comment, $posted_assignment_id, $user_id];
                        } else {
                            // Buat data baru (INSERT) - tanpa id_pengumpulan
                            $sql = "INSERT INTO submission (id_tugas, id_mahasiswa, file_path, submission_title, student_comment, tanggal_dikumpulkan, status) VALUES (?, ?, ?, ?, ?, NOW(), 'submitted')";
                            $params = [$posted_assignment_id, $user_id, $db_file_path, $submission_title, $student_comment];
                        }

                        $stmt = $pdo->prepare($sql);
                        if ($stmt->execute($params)) {
                            $success_message = "Tugas berhasil dikumpulkan! Anda akan diarahkan kembali dalam 3 detik.";
                            echo "<script>setTimeout(function(){ window.location.href = 'tugas.php'; }, 3000);</script>";
                        } else {
                            // Hapus file jika gagal menyimpan ke database
                            unlink($file_path);
                            $error_message = "Gagal menyimpan data pengumpulan: " . implode(", ", $stmt->errorInfo());
                        }
                    } catch (Exception $e) {
                        // Hapus file jika ada error
                        if (file_exists($file_path)) {
                            unlink($file_path);
                        }
                        $error_message = "Database error: " . $e->getMessage();
                    }
                } else {
                    $error_message = "Gagal memindahkan file yang diunggah.";
                }
            }
        }
    }
}

// Ambil detail tugas untuk ditampilkan
$sql_assignment = "
    SELECT a.title, a.description, a.due_date, c.nama_matkul
    FROM assignment a
    JOIN course c ON a.id_matkul = c.course_id
    WHERE a.assignment_id = ?";
$stmt_assignment = $pdo->prepare($sql_assignment);
$stmt_assignment->execute([$assignment_id]);
$assignment = $stmt_assignment->fetch(PDO::FETCH_ASSOC);

if (!$assignment) {
    header('Location: tugas.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kumpulkan Tugas - <?php echo htmlspecialchars($assignment['title']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="mahasiswa.css">
</head>
<body>
    <?php include 'sidebar_mahasiswa.html'; ?>
    <div class="main-content">
        <div class="top-nav">
            <div class="nav-left">
                <h1 class="page-title">Kumpulkan Tugas</h1>
            </div>
            <div class="nav-right"></div>
        </div>
        <div class="content-area">
            <a href="tugas.php" class="btn btn-secondary mb-20"><i class="fas fa-arrow-left"></i> Kembali ke Daftar Tugas</a>

            <div class="assignment-details-card">
                <h2><?php echo htmlspecialchars($assignment['title']); ?></h2>
                <p><?php echo nl2br(htmlspecialchars($assignment['description'])); ?></p>
                <div class="assignment-meta-info">
                    <span><i class="fas fa-book"></i> <?php echo htmlspecialchars($assignment['nama_matkul']); ?></span>
                    <span><i class="fas fa-calendar-alt"></i> Deadline: <?php echo date('d M Y, H:i', strtotime($assignment['due_date'])); ?></span>
                </div>
            </div>

            <div class="submission-container">
                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <?php if (!isset($success_message)): ?>
                <form id="submissionForm" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="assignment_id" value="<?php echo $assignment_id; ?>">
                    
                    <div class="form-group">
                        <label for="submission_title" class="form-label">Judul Pengumpulan *</label>
                        <input type="text" id="submission_title" name="submission_title" class="form-input" 
                               placeholder="Contoh: Tugas ERD Perpustakaan - <?php echo $full_name; ?>" 
                               value="<?php echo isset($_POST['submission_title']) ? htmlspecialchars($_POST['submission_title']) : ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="student_comment" class="form-label">Komentar / Catatan</label>
                        <textarea id="student_comment" name="student_comment" rows="4" class="form-textarea" 
                                  placeholder="Berikan komentar atau catatan tentang tugas yang anda kumpulkan..."><?php echo isset($_POST['student_comment']) ? htmlspecialchars($_POST['student_comment']) : ''; ?></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Upload File *</label>
                        <div id="drop-area" onclick="document.getElementById('fileElem').click()">
                            <input type="file" name="assignment_file" id="fileElem" style="display:none" accept=".pdf,.doc,.docx,.zip,.rar,.jpg,.jpeg,.png,.txt" required>
                            <i class="fas fa-cloud-upload-alt" style="font-size: 40px; color: #adb5bd;"></i>
                            <p><b>Pilih file</b> atau seret file ke sini.</p>
                            <p style="font-size: 12px; color: #6c757d;">Format yang diterima: PDF, DOC, DOCX, ZIP, RAR, JPG, PNG, TXT (Max: 10MB)</p>
                        </div>
                        <div id="file-info">Belum ada file dipilih.</div>
                    </div>

                    <div style="text-align: right; margin-top: 25px;">
                        <a href="tugas.php" class="btn btn-secondary">Batal</a>
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="fas fa-paper-plane"></i> Kumpulkan Tugas
                        </button>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        // Fixed JavaScript untuk Drag & Drop
        const dropArea = document.getElementById('drop-area');
        const fileElem = document.getElementById('fileElem');
        const fileInfo = document.getElementById('file-info');
        const submitBtn = document.getElementById('submitBtn');

        // Prevent default drag behaviors
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropArea.addEventListener(eventName, preventDefaults, false);
            document.body.addEventListener(eventName, preventDefaults, false);
        });

        // Highlight drop area when item is dragged over it
        ['dragenter', 'dragover'].forEach(eventName => {
            dropArea.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropArea.addEventListener(eventName, unhighlight, false);
        });

        // Handle dropped files
        dropArea.addEventListener('drop', handleDrop, false);
        fileElem.addEventListener('change', function() {
            handleFiles(this.files);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        function highlight() {
            dropArea.classList.add('highlight');
        }

        function unhighlight() {
            dropArea.classList.remove('highlight');
        }

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            handleFiles(files);
        }

        function handleFiles(files) {
            if (files.length > 0) {
                const file = files[0];
                
                // Create new FileList object
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                fileElem.files = dataTransfer.files;
                
                fileInfo.textContent = `File dipilih: ${file.name} (${formatFileSize(file.size)})`;
                fileInfo.style.color = '#28a745';
                
                // Validate file
                validateFile(file);
            }
        }

        function validateFile(file) {
            const allowedTypes = ['application/pdf', 'application/msword', 
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'application/zip', 'application/x-rar-compressed',
                                'image/jpeg', 'image/jpg', 'image/png', 'text/plain'];
            
            const maxSize = 10 * 1024 * 1024; // 10MB
            
            if (!allowedTypes.includes(file.type)) {
                fileInfo.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Format file tidak diizinkan!';
                fileInfo.style.color = '#dc3545';
                submitBtn.disabled = true;
                return false;
            }
            
            if (file.size > maxSize) {
                fileInfo.innerHTML = '<i class="fas fa-exclamation-triangle"></i> File terlalu besar! Maksimal 10MB';
                fileInfo.style.color = '#dc3545';
                submitBtn.disabled = true;
                return false;
            }
            
            submitBtn.disabled = false;
            return true;
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        // Form validation before submit
        document.getElementById('submissionForm').addEventListener('submit', function(e) {
            const title = document.getElementById('submission_title').value.trim();
            const fileInput = document.getElementById('fileElem');
            
            if (!title) {
                alert('Judul pengumpulan wajib diisi!');
                e.preventDefault();
                return false;
            }
            
            if (!fileInput.files.length) {
                alert('File wajib dipilih!');
                e.preventDefault();
                return false;
            }
            
            // Disable submit button to prevent double submission
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengirim...';
            
            return true;
        });
    </script>
</body>
</html>