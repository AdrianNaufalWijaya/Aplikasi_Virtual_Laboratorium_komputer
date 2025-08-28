<?php

require_once 'auth_admin.php'; 
require_once '../koneksi.php'; 

// Try to get real admin user from database
$database = new Database();
$conn = $database->getConnection();

if ($conn) {
    try {
        $admin_query = "SELECT user_id, username, full_name, role FROM users WHERE role = 'admin' AND status = 'active' LIMIT 1";
        $admin_stmt = $conn->prepare($admin_query);
        $admin_stmt->execute();
        if ($admin_stmt->rowCount() > 0) {
            $admin_user = $admin_stmt->fetch(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {
        // Use default admin if query fails
    }
}

$message = '';
$message_type = '';

// Handle form submissions
if ($_POST) {
    $database = new Database();
    $conn = $database->getConnection();
    
    if ($conn) {
        // Handle Add Course
        if (isset($_POST['action']) && $_POST['action'] === 'add_course') {
            try {
                // Validate required fields
                $required_fields = ['kode_matkul', 'nama_matkul', 'id_dosen', 'semester'];
                foreach ($required_fields as $field) {
                    if (empty($_POST[$field])) {
                        throw new Exception("Field $field harus diisi!");
                    }
                }
                
                // Check if course code already exists
                $check_query = "SELECT course_id FROM course WHERE kode_matkul = :kode_matkul";
                $check_stmt = $conn->prepare($check_query);
                $check_stmt->bindParam(':kode_matkul', $_POST['kode_matkul']);
                $check_stmt->execute();
                
                if ($check_stmt->rowCount() > 0) {
                    throw new Exception("Kode mata kuliah sudah digunakan!");
                }
                
                // Verify dosen exists
                $dosen_check = "SELECT user_id FROM users WHERE user_id = :id_dosen AND role = 'dosen' AND status = 'active'";
                $dosen_stmt = $conn->prepare($dosen_check);
                $dosen_stmt->bindParam(':id_dosen', $_POST['id_dosen']);
                $dosen_stmt->execute();
                
                if ($dosen_stmt->rowCount() == 0) {
                    throw new Exception("Dosen yang dipilih tidak valid!");
                }
                
                // Insert new course
                $insert_query = "INSERT INTO course (kode_matkul, nama_matkul, id_dosen, semester, status) 
                                VALUES (:kode_matkul, :nama_matkul, :id_dosen, :semester, :status)";
                $insert_stmt = $conn->prepare($insert_query);
                $insert_stmt->bindParam(':kode_matkul', $_POST['kode_matkul']);
                $insert_stmt->bindParam(':nama_matkul', $_POST['nama_matkul']);
                $insert_stmt->bindParam(':id_dosen', $_POST['id_dosen']);
                $insert_stmt->bindParam(':semester', $_POST['semester']);
                $insert_stmt->bindParam(':status', $_POST['status']);
                
                if ($insert_stmt->execute()) {
                    $new_course_id = $conn->lastInsertId();
                    
                    // Log activity (dengan error handling)
                    try {
                        // Verify admin user exists before logging
                        $verify_admin = "SELECT user_id FROM users WHERE user_id = :user_id";
                        $verify_stmt = $conn->prepare($verify_admin);
                        $verify_stmt->bindParam(':user_id', $admin_user['user_id']);
                        $verify_stmt->execute();
                        
                        if ($verify_stmt->rowCount() > 0) {
                            $log_query = "INSERT INTO activity_log (user_id, action, entity_type, entity_id) VALUES (?, 'create_course', 'course', ?)";
                            $log_stmt = $conn->prepare($log_query);
                            $log_stmt->execute([$admin_user['user_id'], $new_course_id]);
                        }
                    } catch (Exception $log_error) {
                        // Log error but don't stop the main process
                        error_log("Activity log error: " . $log_error->getMessage());
                    }
                    
                    $message = "Mata kuliah baru berhasil ditambahkan!";
                    $message_type = "success";
                } else {
                    throw new Exception("Gagal menambahkan mata kuliah!");
                }
                
            } catch (Exception $e) {
                $message = $e->getMessage();
                $message_type = "error";
            }
        }
        
        // Handle Edit Course
        if (isset($_POST['action']) && $_POST['action'] === 'edit_course') {
            try {
                $course_id = $_POST['course_id'];
                
                // Verify dosen exists
                $dosen_check = "SELECT user_id FROM users WHERE user_id = :id_dosen AND role = 'dosen' AND status = 'active'";
                $dosen_stmt = $conn->prepare($dosen_check);
                $dosen_stmt->bindParam(':id_dosen', $_POST['id_dosen']);
                $dosen_stmt->execute();
                
                if ($dosen_stmt->rowCount() == 0) {
                    throw new Exception("Dosen yang dipilih tidak valid!");
                }
                
                // Update course data
                $update_query = "UPDATE course SET 
                                kode_matkul = :kode_matkul,
                                nama_matkul = :nama_matkul,
                                id_dosen = :id_dosen,
                                semester = :semester,
                                status = :status
                                WHERE course_id = :course_id";
                
                $update_stmt = $conn->prepare($update_query);
                $params = [
                    ':course_id' => $course_id,
                    ':kode_matkul' => $_POST['kode_matkul'],
                    ':nama_matkul' => $_POST['nama_matkul'],
                    ':id_dosen' => $_POST['id_dosen'],
                    ':semester' => $_POST['semester'],
                    ':status' => $_POST['status']
                ];
                
                if ($update_stmt->execute($params)) {
                    // Log activity (dengan error handling)
                    try {
                        $verify_admin = "SELECT user_id FROM users WHERE user_id = :user_id";
                        $verify_stmt = $conn->prepare($verify_admin);
                        $verify_stmt->bindParam(':user_id', $admin_user['user_id']);
                        $verify_stmt->execute();
                        
                        if ($verify_stmt->rowCount() > 0) {
                            $log_query = "INSERT INTO activity_log (user_id, action, entity_type, entity_id) VALUES (?, 'update_course', 'course', ?)";
                            $log_stmt = $conn->prepare($log_query);
                            $log_stmt->execute([$admin_user['user_id'], $course_id]);
                        }
                    } catch (Exception $log_error) {
                        error_log("Activity log error: " . $log_error->getMessage());
                    }
                    
                    $message = "Mata kuliah berhasil diupdate!";
                    $message_type = "success";
                } else {
                    throw new Exception("Gagal mengupdate mata kuliah!");
                }
                
            } catch (Exception $e) {
                $message = $e->getMessage();
                $message_type = "error";
            }
        }
        
        // Handle Toggle Status
        if (isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
            try {
                $course_id = $_POST['course_id'];
                $new_status = $_POST['new_status'];
                
                $update_query = "UPDATE course SET status = :status WHERE course_id = :course_id";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bindParam(':status', $new_status);
                $update_stmt->bindParam(':course_id', $course_id);
                
                if ($update_stmt->execute()) {
                    // Log activity (dengan error handling)
                    try {
                        $verify_admin = "SELECT user_id FROM users WHERE user_id = :user_id";
                        $verify_stmt = $conn->prepare($verify_admin);
                        $verify_stmt->bindParam(':user_id', $admin_user['user_id']);
                        $verify_stmt->execute();
                        
                        if ($verify_stmt->rowCount() > 0) {
                            $log_query = "INSERT INTO activity_log (user_id, action, entity_type, entity_id) VALUES (?, 'toggle_course_status', 'course', ?)";
                            $log_stmt = $conn->prepare($log_query);
                            $log_stmt->execute([$admin_user['user_id'], $course_id]);
                        }
                    } catch (Exception $log_error) {
                        error_log("Activity log error: " . $log_error->getMessage());
                    }
                    
                    $message = "Status mata kuliah berhasil diubah!";
                    $message_type = "success";
                } else {
                    throw new Exception("Gagal mengubah status mata kuliah!");
                }
                
            } catch (Exception $e) {
                $message = $e->getMessage();
                $message_type = "error";
            }
        }
        
        // Handle Delete Course
        if (isset($_POST['action']) && $_POST['action'] === 'delete_course') {
            try {
                $course_id = $_POST['course_id'];
                
                // Check if course has enrollments
                $check_enrollments = "SELECT COUNT(*) as count FROM enrollment WHERE course_id = :course_id";
                $check_stmt = $conn->prepare($check_enrollments);
                $check_stmt->bindParam(':course_id', $course_id);
                $check_stmt->execute();
                $enrollment_count = $check_stmt->fetch(PDO::FETCH_ASSOC)['count'];
                
                if ($enrollment_count > 0) {
                    throw new Exception("Tidak dapat menghapus mata kuliah yang sudah memiliki mahasiswa terdaftar!");
                }
                
                $delete_query = "DELETE FROM course WHERE course_id = :course_id";
                $delete_stmt = $conn->prepare($delete_query);
                $delete_stmt->bindParam(':course_id', $course_id);
                
                if ($delete_stmt->execute()) {
                    // Log activity (dengan error handling)
                    try {
                        $verify_admin = "SELECT user_id FROM users WHERE user_id = :user_id";
                        $verify_stmt = $conn->prepare($verify_admin);
                        $verify_stmt->bindParam(':user_id', $admin_user['user_id']);
                        $verify_stmt->execute();
                        
                        if ($verify_stmt->rowCount() > 0) {
                            $log_query = "INSERT INTO activity_log (user_id, action, entity_type, entity_id) VALUES (?, 'delete_course', 'course', ?)";
                            $log_stmt = $conn->prepare($log_query);
                            $log_stmt->execute([$admin_user['user_id'], $course_id]);
                        }
                    } catch (Exception $log_error) {
                        error_log("Activity log error: " . $log_error->getMessage());
                    }
                    
                    $message = "Mata kuliah berhasil dihapus!";
                    $message_type = "success";
                } else {
                    throw new Exception("Gagal menghapus mata kuliah!");
                }
                
            } catch (Exception $e) {
                $message = $e->getMessage();
                $message_type = "error";
            }
        }
    } else {
        $message = "Koneksi database gagal!";
        $message_type = "error";
    }
}

// Get courses data from database
$courses = [];
$lecturers = [];
$stats = ['total_courses' => 0, 'active_courses' => 0, 'inactive_courses' => 0, 'total_enrollments' => 0];

if ($conn) {
    try {
        // Check if course table exists
        $table_check = $conn->query("SHOW TABLES LIKE 'course'");
        if ($table_check->rowCount() == 0) {
            $message = "Tabel 'course' belum ada. Silakan jalankan setup database terlebih dahulu.";
            $message_type = "error";
        } else {
            // Get all courses with lecturer info and enrollment count
            $courses_query = "SELECT c.*, u.full_name as dosen_name,
                                     COALESCE((SELECT COUNT(*) FROM enrollment e WHERE e.course_id = c.course_id), 0) as enrollment_count
                              FROM course c 
                              LEFT JOIN users u ON c.id_dosen = u.user_id 
                              ORDER BY c.created_at DESC";
            $courses_stmt = $conn->prepare($courses_query);
            $courses_stmt->execute();
            $courses = $courses_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get all lecturers for dropdown
            $lecturers_query = "SELECT user_id, full_name FROM users WHERE role = 'dosen' AND status = 'active' ORDER BY full_name";
            $lecturers_stmt = $conn->prepare($lecturers_query);
            $lecturers_stmt->execute();
            $lecturers = $lecturers_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calculate statistics
            $stats['total_courses'] = count($courses);
            foreach ($courses as $course) {
                if ($course['status'] === 'active') $stats['active_courses']++;
                if ($course['status'] === 'inactive') $stats['inactive_courses']++;
                $stats['total_enrollments'] += intval($course['enrollment_count']);
            }
        }
        
    } catch (Exception $e) {
        $message = "Error mengambil data: " . $e->getMessage();
        $message_type = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Mata Kuliah - Admin Panel</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="admin.css" rel="stylesheet">
    <style>
        .setup-required {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .setup-btn {
            display: inline-block;
            background-color: #051F20;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 6px;
            margin: 10px;
        }
        .setup-btn:hover {
            background-color: #0a3d40;
            color: white;
        }
    </style>
</head>
<body>
    
    <?php include 'sidebar_admin.php' ?>

    <div class="main-content">
        
        <?php include 'header_admin.php' ?>

        <div class="content">
            <div class="page-header">
                <h1 class="page-title">Kelola Mata Kuliah</h1>
                <?php if (!empty($courses) || empty($message)): ?>
                    <button class="btn btn-primary" onclick="openModal('addCourseModal')">
                        <i class="fas fa-plus"></i>
                        Tambah Mata Kuliah
                    </button>
                <?php endif; ?>
            </div>

            <?php if ($message): ?>
                <div class="message <?php echo $message_type; ?>">
                    <i class="fas <?php echo $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if (empty($courses) && $message_type === 'error' && strpos($message, 'course') !== false): ?>
                <div class="setup-required">
                    <h3><i class="fas fa-database"></i> Setup Database Diperlukan</h3>
                    <p>Tabel mata kuliah belum ada atau belum ada data. Silakan setup database terlebih dahulu.</p>
                    <a href="check_course_setup.php" class="setup-btn">
                        <i class="fas fa-cog"></i> Auto Setup Database
                    </a>
                    <p style="margin-top: 15px; font-size: 14px;">
                        Atau import file <strong>setup_course.sql</strong> melalui phpMyAdmin
                    </p>
                </div>
            <?php else: ?>
                <!-- Statistics -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['total_courses']; ?></div>
                        <div class="stat-label">Total Mata Kuliah</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['active_courses']; ?></div>
                        <div class="stat-label">Mata Kuliah Aktif</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['inactive_courses']; ?></div>
                        <div class="stat-label">Mata Kuliah Nonaktif</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['total_enrollments']; ?></div>
                        <div class="stat-label">Total Pendaftar</div>
                    </div>
                </div>

                <?php if (!empty($courses)): ?>
                    <!-- Controls -->
                    <div class="controls">
                        <div class="search-box">
                            <input type="text" class="search-input" placeholder="Cari mata kuliah..." id="searchInput">
                            <button class="btn btn-primary btn-sm">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <select class="filter-select" id="semesterFilter">
                                <option value="">Semua Semester</option>
                                <option value="Ganjil 2024/2025">Ganjil 2024/2025</option>
                                <option value="Genap 2024/2025">Genap 2024/2025</option>
                                <option value="Ganjil 2025/2026">Ganjil 2025/2026</option>
                            </select>
                            <select class="filter-select" id="statusFilter">
                                <option value="">Semua Status</option>
                                <option value="active">Aktif</option>
                                <option value="inactive">Nonaktif</option>
                            </select>
                        </div>
                    </div>

                    <!-- Courses Table -->
                    <div class="users-table">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Mata Kuliah</th>
                                    <th>Dosen Pengampu</th>
                                    <th>Semester</th>
                                    <th>Status</th>
                                    <th>Mahasiswa</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="coursesTableBody">
                                <?php foreach($courses as $course): ?>
                                    <tr data-semester="<?php echo $course['semester']; ?>" data-status="<?php echo $course['status']; ?>">
                                        <td>
                                            <div class="user-info-cell">
                                                <div class="user-avatar-table">
                                                    <i class="fas fa-book"></i>
                                                </div>
                                                <div class="user-details">
                                                    <h4><?php echo htmlspecialchars($course['nama_matkul']); ?></h4>
                                                    <p><?php echo htmlspecialchars($course['kode_matkul']); ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($course['dosen_name'] ?? 'Belum ditentukan'); ?></strong>
                                        </td>
                                        <td>
                                            <span style="font-size: 14px;"><?php echo htmlspecialchars($course['semester']); ?></span>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo $course['status']; ?>">
                                                <?php echo $course['status'] === 'active' ? 'Aktif' : 'Nonaktif'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div style="text-align: center;">
                                                <div style="font-size: 18px; font-weight: 600; color: #051F20;">
                                                    <?php echo $course['enrollment_count']; ?>
                                                </div>
                                                <div style="font-size: 12px; color: #666;">Mahasiswa</div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn btn-primary btn-sm" onclick="editCourse(<?php echo htmlspecialchars(json_encode($course)); ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Apakah Anda yakin?')">
                                                    <input type="hidden" name="action" value="toggle_status">
                                                    <input type="hidden" name="course_id" value="<?php echo $course['course_id']; ?>">
                                                    <input type="hidden" name="new_status" value="<?php echo $course['status'] === 'active' ? 'inactive' : 'active'; ?>">
                                                    <?php if($course['status'] === 'active'): ?>
                                                        <button type="submit" class="btn btn-danger btn-sm" title="Nonaktifkan">
                                                            <i class="fas fa-ban"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <button type="submit" class="btn btn-success btn-sm" title="Aktifkan">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </form>
                                                <?php if($course['enrollment_count'] == 0): ?>
                                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus mata kuliah ini?')">
                                                        <input type="hidden" name="action" value="delete_course">
                                                        <input type="hidden" name="course_id" value="<?php echo $course['course_id']; ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm" title="Hapus">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Course Modal -->
    <div id="addCourseModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Tambah Mata Kuliah Baru</h3>
                <span class="close" onclick="closeModal('addCourseModal')">&times;</span>
            </div>
            <div class="modal-body">
                <form id="addCourseForm" method="POST">
                    <input type="hidden" name="action" value="add_course">
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Kode Mata Kuliah *</label>
                            <input type="text" name="kode_matkul" class="form-input" placeholder="TIF-401" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Nama Mata Kuliah *</label>
                            <input type="text" name="nama_matkul" class="form-input" placeholder="Pemrograman Web" required>
                        </div>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Dosen Pengampu *</label>
                            <select name="id_dosen" class="form-select" required>
                                <option value="">Pilih Dosen</option>
                                <?php foreach($lecturers as $lecturer): ?>
                                    <option value="<?php echo $lecturer['user_id']; ?>">
                                        <?php echo htmlspecialchars($lecturer['full_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Semester *</label>
                            <select name="semester" class="form-select" required>
                                <option value="">Pilih Semester</option>
                                <option value="Ganjil 2024/2025">Ganjil 2024/2025</option>
                                <option value="Genap 2024/2025">Genap 2024/2025</option>
                                <option value="Ganjil 2025/2026">Ganjil 2025/2026</option>
                                <option value="Genap 2025/2026">Genap 2025/2026</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="active">Aktif</option>
                            <option value="inactive">Nonaktif</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addCourseModal')">Batal</button>
                <button type="submit" form="addCourseForm" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    Simpan
                </button>
            </div>
        </div>
    </div>

    <!-- Edit Course Modal -->
    <div id="editCourseModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Edit Mata Kuliah</h3>
                <span class="close" onclick="closeModal('editCourseModal')">&times;</span>
            </div>
            <div class="modal-body">
                <form id="editCourseForm" method="POST">
                    <input type="hidden" name="action" value="edit_course">
                    <input type="hidden" name="course_id" id="editCourseId">
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Kode Mata Kuliah *</label>
                            <input type="text" name="kode_matkul" id="editKodeMatkul" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Nama Mata Kuliah *</label>
                            <input type="text" name="nama_matkul" id="editNamaMatkul" class="form-input" required>
                        </div>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Dosen Pengampu *</label>
                            <select name="id_dosen" id="editIdDosen" class="form-select" required>
                                <option value="">Pilih Dosen</option>
                                <?php foreach($lecturers as $lecturer): ?>
                                    <option value="<?php echo $lecturer['user_id']; ?>">
                                        <?php echo htmlspecialchars($lecturer['full_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Semester *</label>
                            <select name="semester" id="editSemester" class="form-select" required>
                                <option value="">Pilih Semester</option>
                                <option value="Ganjil 2024/2025">Ganjil 2024/2025</option>
                                <option value="Genap 2024/2025">Genap 2024/2025</option>
                                <option value="Ganjil 2025/2026">Ganjil 2025/2026</option>
                                <option value="Genap 2025/2026">Genap 2025/2026</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" id="editStatus" class="form-select">
                            <option value="active">Aktif</option>
                            <option value="inactive">Nonaktif</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editCourseModal')">Batal</button>
                <button type="submit" form="editCourseForm" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    Update
                </button>
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

        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function editCourse(courseData) {
            document.getElementById('editCourseId').value = courseData.course_id;
            document.getElementById('editKodeMatkul').value = courseData.kode_matkul;
            document.getElementById('editNamaMatkul').value = courseData.nama_matkul;
            document.getElementById('editIdDosen').value = courseData.id_dosen;
            document.getElementById('editSemester').value = courseData.semester;
            document.getElementById('editStatus').value = courseData.status;
            
            openModal('editCourseModal');
        }

        function filterCourses() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const semesterFilter = document.getElementById('semesterFilter').value;
            const statusFilter = document.getElementById('statusFilter').value;
            const rows = document.querySelectorAll('#coursesTableBody tr');

            rows.forEach(row => {
                const courseText = row.textContent.toLowerCase();
                const courseSemester = row.getAttribute('data-semester');
                const courseStatus = row.getAttribute('data-status');

                const matchSearch = searchTerm === '' || courseText.includes(searchTerm);
                const matchSemester = semesterFilter === '' || courseSemester === semesterFilter;
                const matchStatus = statusFilter === '' || courseStatus === statusFilter;

                row.style.display = matchSearch && matchSemester && matchStatus ? '' : 'none';
            });
        }

        // Event listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Search and filter functionality
            const searchInput = document.getElementById('searchInput');
            const semesterFilter = document.getElementById('semesterFilter');
            const statusFilter = document.getElementById('statusFilter');
            
            if (searchInput) searchInput.addEventListener('input', filterCourses);
            if (semesterFilter) semesterFilter.addEventListener('change', filterCourses);
            if (statusFilter) statusFilter.addEventListener('change', filterCourses);

            // Close modal when clicking outside
            window.onclick = function(event) {
                if (event.target.classList.contains('modal')) {
                    event.target.style.display = 'none';
                }
            }

            // Auto-hide messages after 5 seconds
            const message = document.querySelector('.message');
            if (message && message.style.display !== 'none') {
                setTimeout(function() {
                    message.style.display = 'none';
                }, 5000);
            }
        });

        // Update time immediately and then every second
        updateTime();
        setInterval(updateTime, 1000);
    </script>
</body>
</html>