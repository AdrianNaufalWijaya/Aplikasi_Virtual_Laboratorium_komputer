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
            // Handle Add Software
            if (isset($_POST['action']) && $_POST['action'] === 'add_software') {
                try {
                    // Validate required fields
                    $required_fields = ['software_name', 'category', 'version', 'license_type'];
                    foreach ($required_fields as $field) {
                        if (empty($_POST[$field])) {
                            throw new Exception("Field $field harus diisi!");
                        }
                    }
                    
                    // Check if software already exists with same version
                    $check_query = "SELECT software_id FROM software WHERE software_name = :software_name AND version = :version";
                    $check_stmt = $conn->prepare($check_query);
                    $check_stmt->bindParam(':software_name', $_POST['software_name']);
                    $check_stmt->bindParam(':version', $_POST['version']);
                    $check_stmt->execute();
                    
                    if ($check_stmt->rowCount() > 0) {
                        throw new Exception("Software dengan versi yang sama sudah ada!");
                    }
                    
                    // Insert new software
                    $insert_query = "INSERT INTO software (software_name, category, version, license_type, vendor, status, created_at, updated_at) 
                                    VALUES (:software_name, :category, :version, :license_type, :vendor, :status, NOW(), NOW())";
                    $insert_stmt = $conn->prepare($insert_query);
                    $insert_stmt->bindParam(':software_name', $_POST['software_name']);
                    $insert_stmt->bindParam(':category', $_POST['category']);
                    $insert_stmt->bindParam(':version', $_POST['version']);
                    $insert_stmt->bindParam(':license_type', $_POST['license_type']);
                    $insert_stmt->bindParam(':vendor', $_POST['vendor']);
                    $insert_stmt->bindParam(':status', $_POST['status']);
                    
                    if ($insert_stmt->execute()) {
                        $new_software_id = $conn->lastInsertId();
                        
                        // Log activity
                        try {
                            $log_query = "INSERT INTO activity_log (user_id, action, entity_type, entity_id) VALUES (?, 'create_software', 'software', ?)";
                            $log_stmt = $conn->prepare($log_query);
                            $log_stmt->execute([$admin_user['user_id'], $new_software_id]);
                        } catch (Exception $log_error) {
                            error_log("Activity log error: " . $log_error->getMessage());
                        }
                        
                        $message = "Software berhasil ditambahkan!";
                        $message_type = "success";
                    } else {
                        throw new Exception("Gagal menambahkan software!");
                    }
                    
                } catch (Exception $e) {
                    $message = $e->getMessage();
                    $message_type = "error";
                }
            }
            
            // Handle Edit Software
            if (isset($_POST['action']) && $_POST['action'] === 'edit_software') {
                try {
                    $software_id = $_POST['software_id'];
                    
                    // Update software data
                    $update_query = "UPDATE software SET 
                                    software_name = :software_name,
                                    category = :category,
                                    version = :version,
                                    license_type = :license_type,
                                    vendor = :vendor,
                                    status = :status,
                                    updated_at = NOW()
                                    WHERE software_id = :software_id";
                    
                    $update_stmt = $conn->prepare($update_query);
                    $update_stmt->bindParam(':software_id', $software_id);
                    $update_stmt->bindParam(':software_name', $_POST['software_name']);
                    $update_stmt->bindParam(':category', $_POST['category']);
                    $update_stmt->bindParam(':version', $_POST['version']);
                    $update_stmt->bindParam(':license_type', $_POST['license_type']);
                    $update_stmt->bindParam(':vendor', $_POST['vendor']);
                    $update_stmt->bindParam(':status', $_POST['status']);
                    
                    if ($update_stmt->execute()) {
                        // Log activity
                        try {
                            $log_query = "INSERT INTO activity_log (user_id, action, entity_type, entity_id) VALUES (?, 'update_software', 'software', ?)";
                            $log_stmt = $conn->prepare($log_query);
                            $log_stmt->execute([$admin_user['user_id'], $software_id]);
                        } catch (Exception $log_error) {
                            error_log("Activity log error: " . $log_error->getMessage());
                        }
                        
                        $message = "Software berhasil diupdate!";
                        $message_type = "success";
                    } else {
                        throw new Exception("Gagal mengupdate software!");
                    }
                    
                } catch (Exception $e) {
                    $message = $e->getMessage();
                    $message_type = "error";
                }
            }
            
            // Handle Delete Software
            if (isset($_POST['action']) && $_POST['action'] === 'delete_software') {
                try {
                    $software_id = $_POST['software_id'];
                    
                    // Check if software is installed in any lab
                    $check_installations = "SELECT COUNT(*) as count FROM lab_software WHERE software_id = :software_id";
                    $check_stmt = $conn->prepare($check_installations);
                    $check_stmt->bindParam(':software_id', $software_id);
                    $check_stmt->execute();
                    $installation_count = $check_stmt->fetch(PDO::FETCH_ASSOC)['count'];
                    
                    if ($installation_count > 0) {
                        throw new Exception("Tidak dapat menghapus software yang masih terinstall di laboratorium!");
                    }
                    
                    $delete_query = "DELETE FROM software WHERE software_id = :software_id";
                    $delete_stmt = $conn->prepare($delete_query);
                    $delete_stmt->bindParam(':software_id', $software_id);
                    
                    if ($delete_stmt->execute()) {
                        // Log activity
                        try {
                            $log_query = "INSERT INTO activity_log (user_id, action, entity_type, entity_id) VALUES (?, 'delete_software', 'software', ?)";
                            $log_stmt = $conn->prepare($log_query);
                            $log_stmt->execute([$admin_user['user_id'], $software_id]);
                        } catch (Exception $log_error) {
                            error_log("Activity log error: " . $log_error->getMessage());
                        }
                        
                        $message = "Software berhasil dihapus!";
                        $message_type = "success";
                    } else {
                        throw new Exception("Gagal menghapus software!");
                    }
                    
                } catch (Exception $e) {
                    $message = $e->getMessage();
                    $message_type = "error";
                }
            }
            
            // Handle Install Software to Lab
            if (isset($_POST['action']) && $_POST['action'] === 'install_software') {
                try {
                    $software_id = $_POST['software_id'];
                    $lab_id = $_POST['lab_id'];
                    
                    // Check if already installed
                    $check_query = "SELECT lab_software_id FROM lab_software WHERE lab_software_id = :lab_id AND software_id = :software_id";
                    $check_stmt = $conn->prepare($check_query);
                    $check_stmt->bindParam(':lab_id', $lab_id);
                    $check_stmt->bindParam(':software_id', $software_id);
                    $check_stmt->execute();
                    
                    if ($check_stmt->rowCount() > 0) {
                        throw new Exception("Software sudah terinstall di laboratorium ini!");
                    }
                    
                    // Install software to lab
                    $install_query = "INSERT INTO lab_software (lab_id, software_id, installation_date, installed_by) 
                                    VALUES (:lab_id, :software_id, NOW(), :installed_by)";
                    $install_stmt = $conn->prepare($install_query);
                    $install_stmt->bindParam(':lab_id', $lab_id);
                    $install_stmt->bindParam(':software_id', $software_id);
                    $install_stmt->bindParam(':installed_by', $admin_user['user_id']);
                    
                    if ($install_stmt->execute()) {
                        $message = "Software berhasil diinstall ke laboratorium!";
                        $message_type = "success";
                    } else {
                        throw new Exception("Gagal menginstall software!");
                    }
                    
                } catch (Exception $e) {
                    $message = $e->getMessage();
                    $message_type = "error";
                }
            }
            
            // Handle Uninstall Software from Lab
            if (isset($_POST['action']) && $_POST['action'] === 'uninstall_software') {
                try {
                    $lab_software_id = $_POST['lab_software_id'];
                    
                    $uninstall_query = "DELETE FROM lab_software WHERE lab_software_id = :lab_software_id";
                    $uninstall_stmt = $conn->prepare($uninstall_query);
                    $uninstall_stmt->bindParam(':lab_software_id', $lab_software_id);
                    
                    if ($uninstall_stmt->execute()) {
                        $message = "Software berhasil diuninstall dari laboratorium!";
                        $message_type = "success";
                    } else {
                        throw new Exception("Gagal menguninstall software!");
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

    // Get software data from database
    $software_list = [];
    $laboratories = [];
    $stats = ['total_software' => 0, 'total_installations' => 0, 'active_licenses' => 0, 'deprecated_licenses' => 0];

    if ($conn) {
        try {
            // Check if software table exists
            $table_check = $conn->query("SHOW TABLES LIKE 'software'");
            if ($table_check->rowCount() == 0) {
                // Create software table if not exists (sesuai dengan struktur database Anda)
                $create_table = "CREATE TABLE IF NOT EXISTS software (
                    software_id INT AUTO_INCREMENT PRIMARY KEY,
                    software_name VARCHAR(100) NOT NULL,
                    version VARCHAR(50),
                    category VARCHAR(50),
                    license_type ENUM('free', 'trial', 'paid', 'educational') DEFAULT 'free',
                    vendor VARCHAR(100),
                    status ENUM('active', 'inactive', 'deprecated') DEFAULT 'active',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )";
                $conn->exec($create_table);
                
                // Create lab_software table if not exists
                $create_lab_software = "CREATE TABLE IF NOT EXISTS lab_software (
                    lab_software_id INT AUTO_INCREMENT PRIMARY KEY,
                    lab_id INT,
                    software_id INT,
                    installation_date DATETIME,
                    installed_by INT,
                    FOREIGN KEY (lab_id) REFERENCES laboratory(lab_id) ON DELETE CASCADE,
                    FOREIGN KEY (software_id) REFERENCES software(software_id) ON DELETE CASCADE,
                    UNIQUE KEY unique_lab_software (lab_id, software_id)
                )";
                $conn->exec($create_lab_software);
            }
            
            // Get all software with installation count
            $software_query = "SELECT s.*,
                            COALESCE((SELECT COUNT(*) FROM lab_software ls WHERE ls.software_id = s.software_id), 0) as installation_count
                            FROM software s 
                            ORDER BY s.created_at DESC";
            $software_stmt = $conn->prepare($software_query);
            $software_stmt->execute();
            $software_list = $software_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get laboratories for installation
            $labs_query = "SELECT lab_id, lab_name FROM laboratory ORDER BY lab_name";
            $labs_stmt = $conn->prepare($labs_query);
            $labs_stmt->execute();
            $laboratories = $labs_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calculate statistics
            $stats['total_software'] = count($software_list);
            foreach ($software_list as $software) {
                $stats['total_installations'] += intval($software['installation_count']);
                if ($software['status'] == 'active') {
                    $stats['active_licenses']++;
                } else if ($software['status'] == 'deprecated') {
                    $stats['deprecated_licenses']++;
                }
            }
            
        } catch (Exception $e) {
            $message = "Error mengambil data: " . $e->getMessage();
            $message_type = "error";
        }
    }

    // Software categories
    $categories = [
        'programming' => 'Programming',
        'database' => 'Database',
        'design' => 'Design & Graphics',
        'office' => 'Office Suite',
        'networking' => 'Networking',
        'security' => 'Security',
        'multimedia' => 'Multimedia',
        'utility' => 'Utility',
        'development' => 'Development Tools',
        'virtualization' => 'Virtualization'
    ];
    ?>

    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Kelola Software - Admin Panel</title>
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
        <link href="admin.css" rel="stylesheet">
    </head>
    <body>
        <?php include 'sidebar_admin.php' ?>

        <div class="main-content">
            <?php include 'header_admin.php' ?>

            <div class="content">
                <div class="page-header">
                    <h1 class="page-title">Kelola Software</h1>
                    <div style="display: flex; gap: 10px;">
                        <button class="btn btn-primary" onclick="openModal('addSoftwareModal')">
                            <i class="fas fa-plus"></i>
                            Tambah Software
                        </button>
                        <button class="btn btn-success" onclick="openModal('installSoftwareModal')">
                            <i class="fas fa-download"></i>
                            Install ke Lab
                        </button>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="message <?php echo $message_type; ?>">
                        <i class="fas <?php echo $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>"></i>
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <!-- Statistics -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['total_software']; ?></div>
                        <div class="stat-label">Total Software</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['total_installations']; ?></div>
                        <div class="stat-label">Total Instalasi</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['active_licenses']; ?></div>
                        <div class="stat-label">Software Aktif</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['deprecated_licenses']; ?></div>
                        <div class="stat-label">Deprecated</div>
                    </div>
                </div>

                <!-- Controls -->
                <div class="controls">
                    <div class="search-box">
                        <input type="text" class="search-input" placeholder="Cari software..." id="searchInput">
                        <button class="btn btn-primary btn-sm">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <select class="filter-select" id="categoryFilter">
                            <option value="">Semua Kategori</option>
                            <?php foreach($categories as $key => $value): ?>
                                <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select class="filter-select" id="licenseFilter">
                            <option value="">Semua Lisensi</option>
                            <option value="free">Free</option>
                            <option value="trial">Trial</option>
                            <option value="paid">Paid</option>
                            <option value="educational">Educational</option>
                        </select>
                    </div>
                </div>

                <!-- Software List -->
                <div id="softwareContainer">
                    <?php foreach($software_list as $software): ?>
                        <div class="software-card" data-category="<?php echo $software['category']; ?>" data-license="<?php echo $software['license_type']; ?>">
                            <div class="software-header">
                                <div class="software-info">
                                    <h3>
                                        <div class="software-icon">
                                            <?php
                                            $icon = 'fas fa-cube';
                                            switch($software['category']) {
                                                case 'programming': $icon = 'fas fa-code'; break;
                                                case 'database': $icon = 'fas fa-database'; break;
                                                case 'design': $icon = 'fas fa-paint-brush'; break;
                                                case 'office': $icon = 'fas fa-file-alt'; break;
                                                case 'networking': $icon = 'fas fa-network-wired'; break;
                                                case 'security': $icon = 'fas fa-shield-alt'; break;
                                                case 'multimedia': $icon = 'fas fa-video'; break;
                                                case 'utility': $icon = 'fas fa-tools'; break;
                                                case 'development': $icon = 'fas fa-terminal'; break;
                                                case 'virtualization': $icon = 'fas fa-server'; break;
                                            }
                                            ?>
                                            <i class="<?php echo $icon; ?>"></i>
                                        </div>
                                        <?php echo htmlspecialchars($software['software_name']); ?>
                                        <span style="font-size: 14px; color: #666;">v<?php echo htmlspecialchars($software['version']); ?></span>
                                    </h3>
                                </div>
                                <div>
                                    <span class="category-badge"><?php echo $categories[$software['category']] ?? ucfirst($software['category']); ?></span>
                                </div>
                            </div>
                            
                            <div class="software-details">
                                <div class="detail-item">
                                    <div class="detail-icon">
                                        <i class="fas fa-building"></i>
                                    </div>
                                    <div>
                                        <div style="font-weight: 600;"><?php echo htmlspecialchars($software['vendor'] ?? 'Unknown'); ?></div>
                                        <div style="font-size: 12px; color: #666;">Vendor</div>
                                    </div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-icon">
                                        <i class="fas fa-key"></i>
                                    </div>
                                    <div>
                                        <span class="license-badge <?php echo $software['license_type']; ?>">
                                            <?php echo ucfirst($software['license_type']); ?>
                                        </span>
                                        <div style="font-size: 12px; color: #666; margin-top: 2px;">License Type</div>
                                    </div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-icon">
                                        <i class="fas fa-download"></i>
                                    </div>
                                    <div>
                                        <div style="font-weight: 600;"><?php echo $software['installation_count']; ?> Lab</div>
                                        <div style="font-size: 12px; color: #666;">Terinstall</div>
                                    </div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-icon">
                                        <i class="fas fa-toggle-on"></i>
                                    </div>
                                    <div>
                                        <span class="status-badge <?php echo $software['status']; ?>">
                                            <?php echo ucfirst($software['status']); ?>
                                        </span>
                                        <div style="font-size: 12px; color: #666; margin-top: 2px;">Status</div>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if($software['installation_count'] > 0): ?>
                                <div style="margin-bottom: 15px;">
                                    <div class="requirements-title">Terinstall di:</div>
                                    <div class="installations-grid" id="installations-<?php echo $software['software_id']; ?>">
                                        <!-- Will be loaded via AJAX -->
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="software-actions">
                                <button class="btn btn-primary btn-sm" onclick="editSoftware(<?php echo htmlspecialchars(json_encode($software)); ?>)">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="btn btn-info btn-sm" onclick="viewInstallations(<?php echo $software['software_id']; ?>, '<?php echo htmlspecialchars($software['software_name']); ?>')">
                                    <i class="fas fa-server"></i> Lihat Instalasi
                                </button>
                                <?php if($software['installation_count'] == 0): ?>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus software ini?')">
                                        <input type="hidden" name="action" value="delete_software">
                                        <input type="hidden" name="software_id" value="<?php echo $software['software_id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash"></i> Hapus
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Add Software Modal -->
        <div id="addSoftwareModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">Tambah Software Baru</h3>
                    <span class="close" onclick="closeModal('addSoftwareModal')">&times;</span>
                </div>
                <div class="modal-body">
                    <form id="addSoftwareForm" method="POST">
                        <input type="hidden" name="action" value="add_software">
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Nama Software *</label>
                                <input type="text" name="software_name" class="form-input" placeholder="Visual Studio Code" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Versi *</label>
                                <input type="text" name="version" class="form-input" placeholder="1.75.0" required>
                            </div>
                        </div>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Kategori *</label>
                                <select name="category" class="form-select" required>
                                    <option value="">Pilih Kategori</option>
                                    <?php foreach($categories as $key => $value): ?>
                                        <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Vendor</label>
                                <input type="text" name="vendor" class="form-input" placeholder="Microsoft">
                            </div>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Tipe Lisensi *</label>
                                <select name="license_type" class="form-select" required>
                                    <option value="">Pilih Tipe</option>
                                    <option value="free">Free</option>
                                    <option value="trial">Trial</option>
                                    <option value="paid">Paid</option>
                                    <option value="educational">Educational</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="deprecated">Deprecated</option>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addSoftwareModal')">Batal</button>
                    <button type="submit" form="addSoftwareForm" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Simpan
                    </button>
                </div>
            </div>
        </div>

        <!-- Edit Software Modal -->
        <div id="editSoftwareModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">Edit Software</h3>
                    <span class="close" onclick="closeModal('editSoftwareModal')">&times;</span>
                </div>
                <div class="modal-body">
                    <form id="editSoftwareForm" method="POST">
                        <input type="hidden" name="action" value="edit_software">
                        <input type="hidden" name="software_id" id="editSoftwareId">
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Nama Software *</label>
                                <input type="text" name="software_name" id="editSoftwareName" class="form-input" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Versi *</label>
                                <input type="text" name="version" id="editVersion" class="form-input" required>
                            </div>
                        </div>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Kategori *</label>
                                <select name="category" id="editCategory" class="form-select" required>
                                    <option value="">Pilih Kategori</option>
                                    <?php foreach($categories as $key => $value): ?>
                                        <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Vendor</label>
                                <input type="text" name="vendor" id="editVendor" class="form-input">
                            </div>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Tipe Lisensi *</label>
                                <select name="license_type" id="editLicenseType" class="form-select" required>
                                    <option value="">Pilih Tipe</option>
                                    <option value="free">Free</option>
                                    <option value="trial">Trial</option>
                                    <option value="paid">Paid</option>
                                    <option value="educational">Educational</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Status</label>
                                <select name="status" id="editStatus" class="form-select">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="deprecated">Deprecated</option>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editSoftwareModal')">Batal</button>
                    <button type="submit" form="editSoftwareForm" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Update
                    </button>
                </div>
            </div>
        </div>

        <!-- Install Software Modal -->
        <div id="installSoftwareModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">Install Software ke Laboratorium</h3>
                    <span class="close" onclick="closeModal('installSoftwareModal')">&times;</span>
                </div>
                <div class="modal-body">
                    <form id="installSoftwareForm" method="POST">
                        <input type="hidden" name="action" value="install_software">
                        <div class="form-group">
                            <label class="form-label">Pilih Software *</label>
                            <select name="software_id" class="form-select" required>
                                <option value="">Pilih Software</option>
                                <?php foreach($software_list as $software): ?>
                                    <?php if($software['status'] == 'active'): ?>
                                        <option value="<?php echo $software['software_id']; ?>">
                                            <?php echo htmlspecialchars($software['software_name'] . ' v' . $software['version']); ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Pilih Laboratorium *</label>
                            <select name="lab_id" class="form-select" required>
                                <option value="">Pilih Laboratorium</option>
                                <?php foreach($laboratories as $lab): ?>
                                    <option value="<?php echo $lab['lab_id']; ?>">
                                        <?php echo htmlspecialchars($lab['lab_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('installSoftwareModal')">Batal</button>
                    <button type="submit" form="installSoftwareForm" class="btn btn-primary">
                        <i class="fas fa-download"></i>
                        Install
                    </button>
                </div>
            </div>
        </div>

        <!-- View Installations Modal -->
        <div id="viewInstallationsModal" class="modal">
            <div class="modal-content" style="max-width: 800px;">
                <div class="modal-header">
                    <h3 class="modal-title" id="installationsModalTitle">Instalasi Software</h3>
                    <span class="close" onclick="closeModal('viewInstallationsModal')">&times;</span>
                </div>
                <div class="modal-body">
                    <div id="installationsContainer">
                        <!-- Installation list will be loaded here -->
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

            function editSoftware(softwareData) {
                document.getElementById('editSoftwareId').value = softwareData.software_id;
                document.getElementById('editSoftwareName').value = softwareData.software_name;
                document.getElementById('editVersion').value = softwareData.version;
                document.getElementById('editCategory').value = softwareData.category;
                document.getElementById('editVendor').value = softwareData.vendor || '';
                document.getElementById('editLicenseType').value = softwareData.license_type;
                document.getElementById('editStatus').value = softwareData.status;
                
                openModal('editSoftwareModal');
            }

            function viewInstallations(softwareId, softwareName) {
                document.getElementById('installationsModalTitle').textContent = 'Instalasi ' + softwareName;
                
                // Show loading
                document.getElementById('installationsContainer').innerHTML = '<div style="text-align: center; padding: 20px;"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
                
                // Open modal
                openModal('viewInstallationsModal');
                
                // Fetch installations data
                fetchInstallations(softwareId);
            }

            function fetchInstallations(softwareId) {
                // AJAX call to get installations
                fetch('get_software_installations.php?software_id=' + softwareId)
                    .then(response => response.json())
                    .then(data => {
                        let html = '';
                        
                        if (data.installations && data.installations.length > 0) {
                            html = '<div class="table-responsive"><table class="table"><thead><tr>';
                            html += '<th>Laboratorium</th>';
                            html += '<th>Tanggal Install</th>';
                            html += '<th>Diinstall Oleh</th>';
                            html += '<th>Aksi</th>';
                            html += '</tr></thead><tbody>';
                            
                            data.installations.forEach(installation => {
                                html += '<tr>';
                                html += '<td>' + installation.lab_name + '</td>';
                                html += '<td>' + installation.installation_date + '</td>';
                                html += '<td>' + (installation.installer_name || 'System') + '</td>';
                                html += '<td>';
                                html += '<form method="POST" style="display: inline;" onsubmit="return confirm(\'Uninstall software dari lab ini?\')">';
                                html += '<input type="hidden" name="action" value="uninstall_software">';
                                html += '<input type="hidden" name="lab_software_id" value="' + installation.lab_software_id + '">';
                                html += '<button type="submit" class="btn btn-danger btn-sm">';
                                html += '<i class="fas fa-trash"></i> Uninstall';
                                html += '</button>';
                                html += '</form>';
                                html += '</td>';
                                html += '</tr>';
                            });
                            
                            html += '</tbody></table></div>';
                        } else {
                            html = '<div style="text-align: center; padding: 40px; color: #666;">';
                            html += '<i class="fas fa-server" style="font-size: 48px; margin-bottom: 15px;"></i>';
                            html += '<p>Software belum terinstall di laboratorium manapun.</p>';
                            html += '</div>';
                        }
                        
                        document.getElementById('installationsContainer').innerHTML = html;
                    })
                    .catch(error => {
                        document.getElementById('installationsContainer').innerHTML = '<div style="text-align: center; padding: 20px; color: #dc3545;">Error loading installations</div>';
                    });
            }

            // Load installations for each software on page load
            function loadAllInstallations() {
                <?php foreach($software_list as $software): ?>
                    <?php if($software['installation_count'] > 0): ?>
                        fetch('get_software_installations.php?software_id=<?php echo $software['software_id']; ?>&brief=1')
                            .then(response => response.json())
                            .then(data => {
                                if (data.installations) {
                                    let html = '';
                                    data.installations.forEach(installation => {
                                        html += '<span class="lab-tag"><i class="fas fa-server"></i> ' + installation.lab_name + '</span>';
                                    });
                                    const container = document.getElementById('installations-<?php echo $software['software_id']; ?>');
                                    if (container) container.innerHTML = html;
                                }
                            })
                            .catch(error => console.error('Error loading installations'));
                    <?php endif; ?>
                <?php endforeach; ?>
            }

            function filterSoftware() {
                const searchTerm = document.getElementById('searchInput').value.toLowerCase();
                const categoryFilter = document.getElementById('categoryFilter').value;
                const licenseFilter = document.getElementById('licenseFilter').value;
                const softwareCards = document.querySelectorAll('.software-card');

                softwareCards.forEach(card => {
                    const cardText = card.textContent.toLowerCase();
                    const cardCategory = card.getAttribute('data-category');
                    const cardLicense = card.getAttribute('data-license');

                    const matchSearch = searchTerm === '' || cardText.includes(searchTerm);
                    const matchCategory = categoryFilter === '' || cardCategory === categoryFilter;
                    const matchLicense = licenseFilter === '' || cardLicense === licenseFilter;

                    card.style.display = matchSearch && matchCategory && matchLicense ? '' : 'none';
                });
            }

            // Event listeners
            document.addEventListener('DOMContentLoaded', function() {
                // Search and filter functionality
                const searchInput = document.getElementById('searchInput');
                const categoryFilter = document.getElementById('categoryFilter');
                const licenseFilter = document.getElementById('licenseFilter');
                
                if (searchInput) searchInput.addEventListener('input', filterSoftware);
                if (categoryFilter) categoryFilter.addEventListener('change', filterSoftware);
                if (licenseFilter) licenseFilter.addEventListener('change', filterSoftware);

                // Load installations
                loadAllInstallations();

                // Close modal when clicking outside
                window.onclick = function(event) {
                    if (event.target.classList.contains('modal')) {
                        event.target.style.display = 'none';
                    }
                }

                // Auto-hide messages after 5 seconds
                const message = document.querySelector('.message');
                if (message) {
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