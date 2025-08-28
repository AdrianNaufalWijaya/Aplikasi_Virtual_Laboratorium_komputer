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
        // Handle Add Laboratory
        if (isset($_POST['action']) && $_POST['action'] === 'add_lab') {
            try {
                // Validate required fields
                $required_fields = ['lab_name', 'capacity', 'lab_type'];
                foreach ($required_fields as $field) {
                    if (empty($_POST[$field])) {
                        throw new Exception("Field $field harus diisi!");
                    }
                }
                
                // Check if lab name already exists
                $check_query = "SELECT lab_id FROM laboratory WHERE lab_name = :lab_name";
                $check_stmt = $conn->prepare($check_query);
                $check_stmt->bindParam(':lab_name', $_POST['lab_name']);
                $check_stmt->execute();
                
                if ($check_stmt->rowCount() > 0) {
                    throw new Exception("Nama laboratorium sudah digunakan!");
                }
                
                // Insert new laboratory
                $insert_query = "INSERT INTO laboratory (lab_name, capacity, lab_type, description, location) 
                                VALUES (:lab_name, :capacity, :lab_type, :description, :location)";
                $insert_stmt = $conn->prepare($insert_query);
                $insert_stmt->bindParam(':lab_name', $_POST['lab_name']);
                $insert_stmt->bindParam(':capacity', $_POST['capacity']);
                $insert_stmt->bindParam(':lab_type', $_POST['lab_type']);
                $insert_stmt->bindParam(':description', $_POST['description']);
                $insert_stmt->bindParam(':location', $_POST['location']);
                
                if ($insert_stmt->execute()) {
                    $new_lab_id = $conn->lastInsertId();
                    
                    // Log activity (dengan error handling)
                    try {
                        $verify_admin = "SELECT user_id FROM users WHERE user_id = :user_id";
                        $verify_stmt = $conn->prepare($verify_admin);
                        $verify_stmt->bindParam(':user_id', $admin_user['user_id']);
                        $verify_stmt->execute();
                        
                        if ($verify_stmt->rowCount() > 0) {
                            $log_query = "INSERT INTO activity_log (user_id, action, entity_type, entity_id) VALUES (?, 'create_lab', 'laboratory', ?)";
                            $log_stmt = $conn->prepare($log_query);
                            $log_stmt->execute([$admin_user['user_id'], $new_lab_id]);
                        }
                    } catch (Exception $log_error) {
                        error_log("Activity log error: " . $log_error->getMessage());
                    }
                    
                    $message = "Laboratorium baru berhasil ditambahkan!";
                    $message_type = "success";
                } else {
                    throw new Exception("Gagal menambahkan laboratorium!");
                }
                
            } catch (Exception $e) {
                $message = $e->getMessage();
                $message_type = "error";
            }
        }
        
        // Handle Edit Laboratory
        if (isset($_POST['action']) && $_POST['action'] === 'edit_lab') {
            try {
                $lab_id = $_POST['lab_id'];
                
                // Update laboratory data
                $update_query = "UPDATE laboratory SET 
                                lab_name = :lab_name,
                                capacity = :capacity,
                                lab_type = :lab_type,
                                description = :description,
                                location = :location
                                WHERE lab_id = :lab_id";
                
                $update_stmt = $conn->prepare($update_query);
                $params = [
                    ':lab_id' => $lab_id,
                    ':lab_name' => $_POST['lab_name'],
                    ':capacity' => $_POST['capacity'],
                    ':lab_type' => $_POST['lab_type'],
                    ':description' => $_POST['description'],
                    ':location' => $_POST['location']
                ];
                
                if ($update_stmt->execute($params)) {
                    // Log activity
                    try {
                        $verify_admin = "SELECT user_id FROM users WHERE user_id = :user_id";
                        $verify_stmt = $conn->prepare($verify_admin);
                        $verify_stmt->bindParam(':user_id', $admin_user['user_id']);
                        $verify_stmt->execute();
                        
                        if ($verify_stmt->rowCount() > 0) {
                            $log_query = "INSERT INTO activity_log (user_id, action, entity_type, entity_id) VALUES (?, 'update_lab', 'laboratory', ?)";
                            $log_stmt = $conn->prepare($log_query);
                            $log_stmt->execute([$admin_user['user_id'], $lab_id]);
                        }
                    } catch (Exception $log_error) {
                        error_log("Activity log error: " . $log_error->getMessage());
                    }
                    
                    $message = "Laboratorium berhasil diupdate!";
                    $message_type = "success";
                } else {
                    throw new Exception("Gagal mengupdate laboratorium!");
                }
                
            } catch (Exception $e) {
                $message = $e->getMessage();
                $message_type = "error";
            }
        }
        
        // Handle Delete Laboratory
        if (isset($_POST['action']) && $_POST['action'] === 'delete_lab') {
            try {
                $lab_id = $_POST['lab_id'];
                
                // Check if lab has virtual computers
                $check_computers = "SELECT COUNT(*) as count FROM virtual_computer WHERE lab_id = :lab_id";
                $check_stmt = $conn->prepare($check_computers);
                $check_stmt->bindParam(':lab_id', $lab_id);
                $check_stmt->execute();
                $computer_count = $check_stmt->fetch(PDO::FETCH_ASSOC)['count'];
                
                if ($computer_count > 0) {
                    throw new Exception("Tidak dapat menghapus laboratorium yang masih memiliki komputer virtual!");
                }
                
                // Check if lab has active reservations
                $check_reservations = "SELECT COUNT(*) as count FROM reservation WHERE lab_id = :lab_id AND status IN ('pending', 'confirmed')";
                $check_res_stmt = $conn->prepare($check_reservations);
                $check_res_stmt->bindParam(':lab_id', $lab_id);
                $check_res_stmt->execute();
                $reservation_count = $check_res_stmt->fetch(PDO::FETCH_ASSOC)['count'];
                
                if ($reservation_count > 0) {
                    throw new Exception("Tidak dapat menghapus laboratorium yang memiliki reservasi aktif!");
                }
                
                $delete_query = "DELETE FROM laboratory WHERE lab_id = :lab_id";
                $delete_stmt = $conn->prepare($delete_query);
                $delete_stmt->bindParam(':lab_id', $lab_id);
                
                if ($delete_stmt->execute()) {
                    // Log activity
                    try {
                        $verify_admin = "SELECT user_id FROM users WHERE user_id = :user_id";
                        $verify_stmt = $conn->prepare($verify_admin);
                        $verify_stmt->bindParam(':user_id', $admin_user['user_id']);
                        $verify_stmt->execute();
                        
                        if ($verify_stmt->rowCount() > 0) {
                            $log_query = "INSERT INTO activity_log (user_id, action, entity_type, entity_id) VALUES (?, 'delete_lab', 'laboratory', ?)";
                            $log_stmt = $conn->prepare($log_query);
                            $log_stmt->execute([$admin_user['user_id'], $lab_id]);
                        }
                    } catch (Exception $log_error) {
                        error_log("Activity log error: " . $log_error->getMessage());
                    }
                    
                    $message = "Laboratorium berhasil dihapus!";
                    $message_type = "success";
                } else {
                    throw new Exception("Gagal menghapus laboratorium!");
                }
                
            } catch (Exception $e) {
                $message = $e->getMessage();
                $message_type = "error";
            }
        }
        
        // Handle Add Virtual Computer
        if (isset($_POST['action']) && $_POST['action'] === 'add_computer') {
            try {
                $required_fields = ['lab_id', 'computer_name', 'ip_address', 'cpu_cores', 'ram_size', 'storage_size'];
                foreach ($required_fields as $field) {
                    if (empty($_POST[$field])) {
                        throw new Exception("Field $field harus diisi!");
                    }
                }
                
                // Check if computer name already exists in this lab
                $check_query = "SELECT computer_id FROM virtual_computer WHERE lab_id = :lab_id AND computer_name = :computer_name";
                $check_stmt = $conn->prepare($check_query);
                $check_stmt->bindParam(':lab_id', $_POST['lab_id']);
                $check_stmt->bindParam(':computer_name', $_POST['computer_name']);
                $check_stmt->execute();
                
                if ($check_stmt->rowCount() > 0) {
                    throw new Exception("Nama komputer sudah digunakan di laboratorium ini!");
                }
                
                // Check if IP address already exists
                $check_ip = "SELECT computer_id FROM virtual_computer WHERE ip_address = :ip_address";
                $check_ip_stmt = $conn->prepare($check_ip);
                $check_ip_stmt->bindParam(':ip_address', $_POST['ip_address']);
                $check_ip_stmt->execute();
                
                if ($check_ip_stmt->rowCount() > 0) {
                    throw new Exception("IP Address sudah digunakan!");
                }
                
                $insert_query = "INSERT INTO virtual_computer (lab_id, computer_name, ip_address, mac_address, cpu_cores, ram_size, storage_size, status) 
                                VALUES (:lab_id, :computer_name, :ip_address, :mac_address, :cpu_cores, :ram_size, :storage_size, :status)";
                $insert_stmt = $conn->prepare($insert_query);
                $insert_stmt->bindParam(':lab_id', $_POST['lab_id']);
                $insert_stmt->bindParam(':computer_name', $_POST['computer_name']);
                $insert_stmt->bindParam(':ip_address', $_POST['ip_address']);
                $insert_stmt->bindParam(':mac_address', $_POST['mac_address']);
                $insert_stmt->bindParam(':cpu_cores', $_POST['cpu_cores']);
                $insert_stmt->bindParam(':ram_size', $_POST['ram_size']);
                $insert_stmt->bindParam(':storage_size', $_POST['storage_size']);
                $insert_stmt->bindParam(':status', $_POST['status']);
                
                if ($insert_stmt->execute()) {
                    $message = "Komputer virtual berhasil ditambahkan!";
                    $message_type = "success";
                } else {
                    throw new Exception("Gagal menambahkan komputer virtual!");
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

// Get laboratories data from database
$laboratories = [];
$stats = ['total_labs' => 0, 'total_computers' => 0, 'available_computers' => 0, 'occupied_computers' => 0];

if ($conn) {
    try {
        // Check if laboratory table exists
        $table_check = $conn->query("SHOW TABLES LIKE 'laboratory'");
        if ($table_check->rowCount() == 0) {
            $message = "Tabel 'laboratory' belum ada. Silakan jalankan setup database terlebih dahulu.";
            $message_type = "error";
        } else {
            // Get all laboratories with computer count
            $labs_query = "SELECT l.*,
                                 COALESCE((SELECT COUNT(*) FROM virtual_computer vc WHERE vc.lab_id = l.lab_id), 0) as total_computers,
                                 COALESCE((SELECT COUNT(*) FROM virtual_computer vc WHERE vc.lab_id = l.lab_id AND vc.status = 'available'), 0) as available_computers,
                                 COALESCE((SELECT COUNT(*) FROM virtual_computer vc WHERE vc.lab_id = l.lab_id AND vc.status = 'occupied'), 0) as occupied_computers,
                                 COALESCE((SELECT COUNT(*) FROM virtual_computer vc WHERE vc.lab_id = l.lab_id AND vc.status = 'maintenance'), 0) as maintenance_computers
                          FROM laboratory l 
                          ORDER BY l.created_at DESC";
            $labs_stmt = $conn->prepare($labs_query);
            $labs_stmt->execute();
            $laboratories = $labs_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calculate statistics
            $stats['total_labs'] = count($laboratories);
            foreach ($laboratories as $lab) {
                $stats['total_computers'] += intval($lab['total_computers']);
                $stats['available_computers'] += intval($lab['available_computers']);
                $stats['occupied_computers'] += intval($lab['occupied_computers']);
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
    <title>Kelola Laboratorium - Admin Panel</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="admin.css" rel="stylesheet">
    <style>
        .lab-card {
            background-color: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        
        .lab-card:hover {
            transform: translateY(-2px);
        }
        
        .lab-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }
        
        .lab-info h3 {
            color: #051F20;
            font-size: 20px;
            margin-bottom: 5px;
        }
        
        .lab-type {
            background-color: #051F20;
            color: white;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .lab-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .detail-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .detail-icon {
            width: 30px;
            height: 30px;
            background-color: #f8f9fa;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #051F20;
        }
        
        .computer-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-item {
            text-align: center;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: 700;
            color: #051F20;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 12px;
            color: #666;
        }
        
        .lab-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
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
        
        .modal-tabs {
            display: flex;
            border-bottom: 1px solid #eee;
            margin-bottom: 20px;
        }
        
        .tab-btn {
            background: none;
            border: none;
            padding: 15px 20px;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            font-weight: 500;
            color: #666;
        }
        
        .tab-btn.active {
            color: #051F20;
            border-bottom-color: #051F20;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
    </style>
</head>
<body>
   <?php include 'sidebar_admin.php' ?>

    <div class="main-content">
        <?php include 'header_admin.php' ?>

        <div class="content">
            <div class="page-header">
                <h1 class="page-title">Kelola Laboratorium</h1>
                <?php if (!empty($laboratories) || empty($message)): ?>
                    <div style="display: flex; gap: 10px;">
                        <button class="btn btn-primary" onclick="openModal('addLabModal')">
                            <i class="fas fa-plus"></i>
                            Tambah Laboratorium
                        </button>
                        <button class="btn btn-success" onclick="openModal('addComputerModal')">
                            <i class="fas fa-desktop"></i>
                            Tambah Komputer
                        </button>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($message): ?>
                <div class="message <?php echo $message_type; ?>">
                    <i class="fas <?php echo $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if (empty($laboratories) && $message_type === 'error' && strpos($message, 'laboratory') !== false): ?>
                <div class="setup-required">
                    <h3><i class="fas fa-database"></i> Setup Database Diperlukan</h3>
                    <p>Tabel laboratorium belum ada atau belum ada data. Silakan setup database terlebih dahulu.</p>
                    <a href="setup_laboratory.php" class="setup-btn">
                        <i class="fas fa-cog"></i> Auto Setup Laboratory
                    </a>
                    <p style="margin-top: 15px; font-size: 14px;">
                        Atau import script SQL untuk membuat tabel laboratorium
                    </p>
                </div>
            <?php else: ?>
                <!-- Statistics -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['total_labs']; ?></div>
                        <div class="stat-label">Total Laboratorium</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['total_computers']; ?></div>
                        <div class="stat-label">Total Komputer</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['available_computers']; ?></div>
                        <div class="stat-label">Komputer Tersedia</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['occupied_computers']; ?></div>
                        <div class="stat-label">Komputer Terpakai</div>
                    </div>
                </div>

                <?php if (!empty($laboratories)): ?>
                    <!-- Controls -->
                    <div class="controls">
                        <div class="search-box">
                            <input type="text" class="search-input" placeholder="Cari laboratorium..." id="searchInput">
                            <button class="btn btn-primary btn-sm">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <select class="filter-select" id="typeFilter">
                                <option value="">Semua Tipe</option>
                                <option value="programming">Programming</option>
                                <option value="database">Database</option>
                                <option value="networking">Networking</option>
                                <option value="multimedia">Multimedia</option>
                            </select>
                        </div>
                    </div>

                    <!-- Laboratories List -->
                    <div id="laboratoriesContainer">
                        <?php foreach($laboratories as $lab): ?>
                            <div class="lab-card" data-type="<?php echo $lab['lab_type']; ?>">
                                <div class="lab-header">
                                    <div class="lab-info">
                                        <h3><?php echo htmlspecialchars($lab['lab_name']); ?></h3>
                                        <p style="color: #666; margin-bottom: 10px;"><?php echo htmlspecialchars($lab['description'] ?? 'Tidak ada deskripsi'); ?></p>
                                    </div>
                                    <div class="lab-type"><?php echo ucfirst($lab['lab_type']); ?></div>
                                </div>
                                
                                <div class="lab-details">
                                    <div class="detail-item">
                                        <div class="detail-icon">
                                            <i class="fas fa-users"></i>
                                        </div>
                                        <div>
                                            <div style="font-weight: 600;"><?php echo $lab['capacity']; ?> Orang</div>
                                            <div style="font-size: 12px; color: #666;">Kapasitas</div>
                                        </div>
                                    </div>
                                    <div class="detail-item">
                                        <div class="detail-icon">
                                            <i class="fas fa-map-marker-alt"></i>
                                        </div>
                                        <div>
                                            <div style="font-weight: 600;"><?php echo htmlspecialchars($lab['location'] ?? 'Tidak diset'); ?></div>
                                            <div style="font-size: 12px; color: #666;">Lokasi</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="computer-stats">
                                    <div class="stat-item">
                                        <div class="stat-number"><?php echo $lab['total_computers']; ?></div>
                                        <div class="stat-label">Total PC</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-number" style="color: #28a745;"><?php echo $lab['available_computers']; ?></div>
                                        <div class="stat-label">Tersedia</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-number" style="color: #dc3545;"><?php echo $lab['occupied_computers']; ?></div>
                                        <div class="stat-label">Terpakai</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-number" style="color: #ffc107;"><?php echo $lab['maintenance_computers']; ?></div>
                                        <div class="stat-label">Maintenance</div>
                                    </div>
                                </div>
                                
                                <div class="lab-actions">
                                    <button class="btn btn-primary btn-sm" onclick="editLab(<?php echo htmlspecialchars(json_encode($lab)); ?>)">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button class="btn btn-success btn-sm" onclick="viewComputers(<?php echo $lab['lab_id']; ?>, '<?php echo htmlspecialchars($lab['lab_name']); ?>')">
                                        <i class="fas fa-desktop"></i> Lihat Komputer
                                    </button>
                                    <?php if($lab['total_computers'] == 0): ?>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus laboratorium ini?')">
                                            <input type="hidden" name="action" value="delete_lab">
                                            <input type="hidden" name="lab_id" value="<?php echo $lab['lab_id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">
                                                <i class="fas fa-trash"></i> Hapus
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Laboratory Modal -->
    <div id="addLabModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Tambah Laboratorium Baru</h3>
                <span class="close" onclick="closeModal('addLabModal')">&times;</span>
            </div>
            <div class="modal-body">
                <form id="addLabForm" method="POST">
                    <input type="hidden" name="action" value="add_lab">
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Nama Laboratorium *</label>
                            <input type="text" name="lab_name" class="form-input" placeholder="Lab Programming 1" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Kapasitas *</label>
                            <input type="number" name="capacity" class="form-input" placeholder="30" min="1" required>
                        </div>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Tipe Laboratorium *</label>
                            <select name="lab_type" class="form-select" required>
                                <option value="">Pilih Tipe</option>
                                <option value="programming">Programming</option>
                                <option value="database">Database</option>
                                <option value="networking">Networking</option>
                                <option value="multimedia">Multimedia</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Lokasi</label>
                            <input type="text" name="location" class="form-input" placeholder="Gedung Teknik Lantai 2">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Deskripsi</label>
                        <textarea name="description" class="form-textarea" placeholder="Deskripsi laboratorium..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addLabModal')">Batal</button>
                <button type="submit" form="addLabForm" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    Simpan
                </button>
            </div>
        </div>
    </div>

    <!-- Edit Laboratory Modal -->
    <div id="editLabModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Edit Laboratorium</h3>
                <span class="close" onclick="closeModal('editLabModal')">&times;</span>
            </div>
            <div class="modal-body">
                <form id="editLabForm" method="POST">
                    <input type="hidden" name="action" value="edit_lab">
                    <input type="hidden" name="lab_id" id="editLabId">
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Nama Laboratorium *</label>
                            <input type="text" name="lab_name" id="editLabName" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Kapasitas *</label>
                            <input type="number" name="capacity" id="editCapacity" class="form-input" min="1" required>
                        </div>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Tipe Laboratorium *</label>
                            <select name="lab_type" id="editLabType" class="form-select" required>
                                <option value="">Pilih Tipe</option>
                                <option value="programming">Programming</option>
                                <option value="database">Database</option>
                                <option value="networking">Networking</option>
                                <option value="multimedia">Multimedia</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Lokasi</label>
                            <input type="text" name="location" id="editLocation" class="form-input">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Deskripsi</label>
                        <textarea name="description" id="editDescription" class="form-textarea"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editLabModal')">Batal</button>
                <button type="submit" form="editLabForm" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    Update
                </button>
            </div>
        </div>
    </div>

    <!-- Add Computer Modal -->
    <div id="addComputerModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Tambah Komputer Virtual</h3>
                <span class="close" onclick="closeModal('addComputerModal')">&times;</span>
            </div>
            <div class="modal-body">
                <form id="addComputerForm" method="POST">
                    <input type="hidden" name="action" value="add_computer">
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Laboratorium *</label>
                            <select name="lab_id" class="form-select" required>
                                <option value="">Pilih Laboratorium</option>
                                <?php foreach($laboratories as $lab): ?>
                                    <option value="<?php echo $lab['lab_id']; ?>">
                                        <?php echo htmlspecialchars($lab['lab_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Nama Komputer *</label>
                            <input type="text" name="computer_name" class="form-input" placeholder="PC-PROG-001" required>
                        </div>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">IP Address *</label>
                            <input type="text" name="ip_address" class="form-input" placeholder="192.168.1.101" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">MAC Address</label>
                            <input type="text" name="mac_address" class="form-input" placeholder="00:11:22:33:44:01">
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">CPU Cores *</label>
                            <select name="cpu_cores" class="form-select" required>
                                <option value="">Pilih CPU</option>
                                <option value="2">2 Cores</option>
                                <option value="4">4 Cores</option>
                                <option value="6">6 Cores</option>
                                <option value="8">8 Cores</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">RAM (GB) *</label>
                            <select name="ram_size" class="form-select" required>
                                <option value="">Pilih RAM</option>
                                <option value="4">4 GB</option>
                                <option value="8">8 GB</option>
                                <option value="16">16 GB</option>
                                <option value="32">32 GB</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Storage (GB) *</label>
                            <select name="storage_size" class="form-select" required>
                                <option value="">Pilih Storage</option>
                                <option value="50">50 GB</option>
                                <option value="100">100 GB</option>
                                <option value="150">150 GB</option>
                                <option value="200">200 GB</option>
                                <option value="500">500 GB</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="available">Available</option>
                                <option value="maintenance">Maintenance</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addComputerModal')">Batal</button>
                <button type="submit" form="addComputerForm" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    Simpan
                </button>
            </div>
        </div>
    </div>

    <!-- View Computers Modal -->
    <div id="viewComputersModal" class="modal">
        <div class="modal-content" style="max-width: 900px;">
            <div class="modal-header">
                <h3 class="modal-title" id="computersModalTitle">Komputer Virtual</h3>
                <span class="close" onclick="closeModal('viewComputersModal')">&times;</span>
            </div>
            <div class="modal-body">
                <div id="computersContainer">
                    <!-- Computer list will be loaded here -->
                     
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

        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function editLab(labData) {
            document.getElementById('editLabId').value = labData.lab_id;
            document.getElementById('editLabName').value = labData.lab_name;
            document.getElementById('editCapacity').value = labData.capacity;
            document.getElementById('editLabType').value = labData.lab_type;
            document.getElementById('editLocation').value = labData.location || '';
            document.getElementById('editDescription').value = labData.description || '';
            
            openModal('editLabModal');
        }

        function viewComputers(labId, labName) {
            document.getElementById('computersModalTitle').textContent = 'Komputer Virtual - ' + labName;
            
            // Show loading
            document.getElementById('computersContainer').innerHTML = '<div style="text-align: center; padding: 20px;"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
            
            // Open modal
            openModal('viewComputersModal');
            
            // Fetch computers data (simulate with static data for now)
            setTimeout(() => {
                fetchComputers(labId);
            }, 500);
        }

        function addComputerToLab(labId) {
            // Pre-select the lab in add computer modal
            const labSelect = document.querySelector('#addComputerModal select[name="lab_id"]');
            if (labSelect) {
                labSelect.value = labId;
            }
            
            closeModal('viewComputersModal');
            openModal('addComputerModal');
        }

        function editComputerInModal(computerData) {
            document.getElementById('editComputerId').value = computerData.computer_id;
            document.getElementById('editComputerLabId').value = computerData.lab_id;
            document.getElementById('editComputerName').value = computerData.computer_name;
            document.getElementById('editIpAddress').value = computerData.ip_address;
            document.getElementById('editMacAddress').value = computerData.mac_address || '';
            document.getElementById('editCpuCores').value = computerData.cpu_cores;
            document.getElementById('editRamSize').value = computerData.ram_size;
            document.getElementById('editStorageSize').value = computerData.storage_size;
            document.getElementById('editComputerStatus').value = computerData.status;
            
            closeModal('viewComputersModal');
            openModal('editComputerModal');
        }

        function deleteComputerConfirm(computerId, computerName) {
            if (confirm(`Apakah Anda yakin ingin menghapus komputer "${computerName}"?\n\nTindakan ini tidak dapat dibatalkan!`)) {
                deleteComputer(computerId);
            }
        }

        function deleteComputer(computerId) {
            const formData = new FormData();
            formData.append('action', 'delete_computer');
            formData.append('computer_id', computerId);

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                // Reload the page to show the result
                window.location.reload();
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi error saat menghapus komputer!');
            });
        }

        function toggleComputerStatus(computerId, currentStatus) {
            const newStatus = currentStatus === 'available' ? 'maintenance' : 'available';
            const statusText = newStatus === 'available' ? 'Tersedia' : 'Maintenance';
            
            if (confirm(`Ubah status komputer menjadi "${statusText}"?`)) {
                // Update status via AJAX
                const formData = new FormData();
                formData.append('action', 'toggle_computer_status');
                formData.append('computer_id', computerId);
                formData.append('new_status', newStatus);

                fetch('toggle_computer_status.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        // Refresh the computer list
                        const modalTitle = document.getElementById('computersModalTitle').textContent;
                        const labName = modalTitle.replace('Komputer Virtual - ', '');
                        
                        // Find the lab ID from the current modal
                        const labCards = document.querySelectorAll('.lab-card');
                        let currentLabId = null;
                        labCards.forEach(card => {
                            const labNameEl = card.querySelector('h3');
                            if (labNameEl && labNameEl.textContent.trim() === labName) {
                                const viewButton = card.querySelector('button[onclick*="viewComputers"]');
                                if (viewButton) {
                                    const match = viewButton.getAttribute('onclick').match(/viewComputers\((\d+)/);
                                    if (match) {
                                        currentLabId = match[1];
                                    }
                                }
                            }
                        });
                        
                        if (currentLabId) {
                            fetchComputers(currentLabId);
                        }
                    } else {
                        alert(data.message || 'Gagal mengubah status komputer!');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi error saat mengubah status!');
                });
            }
        }

        function filterLabs() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const typeFilter = document.getElementById('typeFilter').value;
            const labCards = document.querySelectorAll('.lab-card');

            labCards.forEach(card => {
                const labText = card.textContent.toLowerCase();
                const labType = card.getAttribute('data-type');

                const matchSearch = searchTerm === '' || labText.includes(searchTerm);
                const matchType = typeFilter === '' || labType === typeFilter;

                card.style.display = matchSearch && matchType ? '' : 'none';
            });
        }

        // Keep the original editComputer function for backward compatibility
        function editComputer(computerData) {
            editComputerInModal(computerData);
        }

        function fetchComputers(labId) {
            // AJAX call to get computers
            fetch('get_computers.php?lab_id=' + labId)
                .then(response => response.json())
                .then(data => {
                    let html = '';
                    
                    // Add header with add computer button
                    html += `
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                            <div>
                                <h4 style="margin: 0; color: #051F20;">Total Komputer: ${data.total || 0}</h4>
                                <p style="margin: 5px 0 0 0; color: #666; font-size: 14px;">Kelola komputer virtual di laboratorium ini</p>
                            </div>
                            <button class="btn btn-success btn-sm" onclick="addComputerToLab(${labId})">
                                <i class="fas fa-plus"></i> Tambah Komputer
                            </button>
                        </div>
                    `;
                    
                    html += '<div class="computers-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 15px;">';
                    
                    if (data.computers && data.computers.length > 0) {
                        data.computers.forEach(computer => {
                            const statusClass = computer.status === 'available' ? 'success' : 
                                              computer.status === 'occupied' ? 'danger' : 'warning';
                            const statusText = computer.status === 'available' ? 'Tersedia' : 
                                             computer.status === 'occupied' ? 'Terpakai' : 'Maintenance';
                            
                            const statusIcon = computer.status === 'available' ? 'fa-check-circle' : 
                                             computer.status === 'occupied' ? 'fa-times-circle' : 'fa-wrench';
                            
                            html += `
                                <div class="computer-card" style="background: white; border: 1px solid #eee; border-radius: 12px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); transition: transform 0.2s;">
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px;">
                                        <div>
                                            <h4 style="color: #051F20; margin: 0 0 5px 0; font-size: 18px;">${computer.computer_name}</h4>
                                            <span class="status-badge status-${computer.status}" style="display: flex; align-items: center; gap: 5px; width: fit-content;">
                                                <i class="fas ${statusIcon}" style="font-size: 10px;"></i>
                                                ${statusText}
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="computer-specs" style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 13px;">
                                            <div><strong>IP Address:</strong><br>${computer.ip_address}</div>
                                            <div><strong>MAC Address:</strong><br>${computer.mac_address || 'Tidak diset'}</div>
                                            <div><strong>CPU Cores:</strong><br>${computer.cpu_cores} Cores</div>
                                            <div><strong>RAM:</strong><br>${computer.ram_size} GB</div>
                                            <div><strong>Storage:</strong><br>${computer.storage_size} GB</div>
                                            <div><strong>Status:</strong><br>${statusText}</div>
                                        </div>
                                    </div>
                                    
                                    <div class="computer-actions" style="display: flex; gap: 8px; justify-content: space-between;">
                                        <div style="display: flex; gap: 8px;">
                                            <button class="btn btn-primary btn-sm" onclick='editComputerInModal(${JSON.stringify(computer)})' title="Edit Komputer">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            ${computer.status === 'available' || computer.status === 'maintenance' ? `
                                            <button class="btn btn-warning btn-sm" onclick="toggleComputerStatus('${computer.computer_id}', '${computer.status}')" title="Ubah Status">
                                                <i class="fas fa-exchange-alt"></i>
                                            </button>
                                            ` : ''}
                                        </div>
                                        ${computer.status !== 'occupied' ? `
                                        <button class="btn btn-danger btn-sm" onclick="deleteComputerConfirm('${computer.computer_id}', '${computer.computer_name}')" title="Hapus Komputer">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        ` : `
                                        <span style="font-size: 11px; color: #666; align-self: center;">Sedang digunakan</span>
                                        `}
                                    </div>
                                </div>
                            `;
                        });
                    } else {
                        html += `
                            <div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px; color: #666;">
                                <i class="fas fa-desktop" style="font-size: 64px; margin-bottom: 20px; color: #ccc;"></i>
                                <h4 style="margin-bottom: 10px;">Belum ada komputer virtual</h4>
                                <p style="margin-bottom: 20px;">Laboratorium ini belum memiliki komputer virtual. Tambahkan komputer pertama!</p>
                                <button class="btn btn-success" onclick="addComputerToLab(${labId})">
                                    <i class="fas fa-plus"></i> Tambah Komputer Pertama
                                </button>
                            </div>
                        `;
                    }
                    
                    html += '</div>';
                    document.getElementById('computersContainer').innerHTML = html;
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('computersContainer').innerHTML = '<div style="text-align: center; padding: 20px; color: #dc3545;"><i class="fas fa-exclamation-triangle"></i> Error loading computers</div>';
                });
        }

        // Event listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Search and filter functionality
            const searchInput = document.getElementById('searchInput');
            const typeFilter = document.getElementById('typeFilter');
            
            if (searchInput) searchInput.addEventListener('input', filterLabs);
            if (typeFilter) typeFilter.addEventListener('change', filterLabs);

            // Close modal when clicking outside
            window.onclick = function(event) {
                if (event.target.classList.contains('modal')) {
                    event.target.style.display = 'none';
                }
            }

            // Handle edit computer form submission
            const editComputerForm = document.getElementById('editComputerForm');
            if (editComputerForm) {
                editComputerForm.addEventListener('submit', function(e) {
                    // Allow normal form submission, but refresh computer list after success
                    setTimeout(function() {
                        // Check if modal is still open and refresh if needed
                        const editModal = document.getElementById('editComputerModal');
                        if (editModal && editModal.style.display === 'none') {
                            // Form was submitted successfully, refresh the page
                            window.location.reload();
                        }
                    }, 100);
                });
            }

            // Handle add computer form submission
            const addComputerForm = document.getElementById('addComputerForm');
            if (addComputerForm) {
                addComputerForm.addEventListener('submit', function(e) {
                    // Allow normal form submission, but refresh after success
                    setTimeout(function() {
                        const addModal = document.getElementById('addComputerModal');
                        if (addModal && addModal.style.display === 'none') {
                            window.location.reload();
                        }
                    }, 100);
                });
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