<?php
require_once 'auth_admin.php'; 
require_once '../koneksi.php'; 

$message = '';
$message_type = '';

// Handle form submissions
if ($_POST) {
    $database = new Database();
    $conn = $database->getConnection();
    
    if ($conn) {
        // Handle Add User
        if (isset($_POST['action']) && $_POST['action'] === 'add_user') {
            try {
                $required_fields = ['full_name', 'username', 'email', 'role', 'password'];
                foreach ($required_fields as $field) {
                    if (empty($_POST[$field])) {
                        throw new Exception("Field $field harus diisi!");
                    }
                }
                
                $check_query = "SELECT user_id FROM users WHERE username = :username OR email = :email";
                $check_stmt = $conn->prepare($check_query);
                $check_stmt->bindParam(':username', $_POST['username']);
                $check_stmt->bindParam(':email', $_POST['email']);
                $check_stmt->execute();
                
                if ($check_stmt->rowCount() > 0) {
                    throw new Exception("Username atau email sudah digunakan!");
                }
                
                $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                
                $insert_query = "INSERT INTO users (username, password, email, full_name, phone_number, role, status) 
                               VALUES (:username, :password, :email, :full_name, :phone_number, :role, :status)";
                $insert_stmt = $conn->prepare($insert_query);
                $insert_stmt->bindParam(':username', $_POST['username']);
                $insert_stmt->bindParam(':password', $hashed_password);
                $insert_stmt->bindParam(':email', $_POST['email']);
                $insert_stmt->bindParam(':full_name', $_POST['full_name']);
                $insert_stmt->bindParam(':phone_number', $_POST['phone_number']);
                $insert_stmt->bindParam(':role', $_POST['role']);
                $insert_stmt->bindParam(':status', $_POST['status']);
                
                if ($insert_stmt->execute()) {
                    $message = "User baru berhasil ditambahkan!";
                    $message_type = "success";
                } else {
                    throw new Exception("Gagal menambahkan user!");
                }
                
            } catch (Exception $e) {
                $message = $e->getMessage();
                $message_type = "error";
            }
        }
        
        // Handle Edit User
        if (isset($_POST['action']) && $_POST['action'] === 'edit_user') {
            try {
                $user_id = $_POST['user_id'];
                
                $update_query = "UPDATE users SET 
                                full_name = :full_name,
                                username = :username,
                                email = :email,
                                phone_number = :phone_number,
                                role = :role,
                                status = :status";
                
                $params = [
                    ':user_id' => $user_id,
                    ':full_name' => $_POST['full_name'],
                    ':username' => $_POST['username'],
                    ':email' => $_POST['email'],
                    ':phone_number' => $_POST['phone_number'],
                    ':role' => $_POST['role'],
                    ':status' => $_POST['status']
                ];
                
                if (!empty($_POST['password'])) {
                    $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    $update_query .= ", password = :password";
                    $params[':password'] = $hashed_password;
                }
                
                $update_query .= " WHERE user_id = :user_id";
                
                $update_stmt = $conn->prepare($update_query);
                
                if ($update_stmt->execute($params)) {
                    $message = "User berhasil diupdate!";
                    $message_type = "success";
                } else {
                    throw new Exception("Gagal mengupdate user!");
                }
                
            } catch (Exception $e) {
                $message = $e->getMessage();
                $message_type = "error";
            }
        }
        
        // Handle Toggle Status
        if (isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
            try {
                $user_id = $_POST['user_id'];
                $new_status = $_POST['new_status'];
                
                // Tambahan keamanan: Cek lagi agar admin tidak menonaktifkan admin lain
                $role_check_query = "SELECT role FROM users WHERE user_id = :user_id";
                $role_check_stmt = $conn->prepare($role_check_query);
                $role_check_stmt->execute([':user_id' => $user_id]);
                $user_to_toggle = $role_check_stmt->fetch();
                
                if ($user_to_toggle && $user_to_toggle['role'] === 'admin') {
                     throw new Exception("Akun admin tidak dapat dinonaktifkan.");
                }

                $update_query = "UPDATE users SET status = :status WHERE user_id = :user_id";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bindParam(':status', $new_status);
                $update_stmt->bindParam(':user_id', $user_id);
                
                if ($update_stmt->execute()) {
                    $message = "Status user berhasil diubah!";
                    $message_type = "success";
                } else {
                    throw new Exception("Gagal mengubah status user!");
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

// Get users data from database
$users = [];
$stats = ['total_users' => 0, 'total_admin' => 0, 'total_dosen' => 0, 'total_mahasiswa' => 0, 'active_users' => 0];

$database = new Database();
$conn = $database->getConnection();

if ($conn) {
    try {
        $users_query = "SELECT * FROM users ORDER BY created_at DESC";
        $users_stmt = $conn->prepare($users_query);
        $users_stmt->execute();
        $users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stats['total_users'] = count($users);
        foreach ($users as $user) {
            if ($user['role'] === 'admin') $stats['total_admin']++;
            if ($user['role'] === 'dosen') $stats['total_dosen']++;
            if ($user['role'] === 'mahasiswa') $stats['total_mahasiswa']++;
            if ($user['status'] === 'active') $stats['active_users']++;
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
    <title>Kelola Pengguna - Admin Panel</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="admin.css" rel="stylesheet">
</head>
<body>
    
<?php 
    $current_page = basename($_SERVER['SCRIPT_NAME']);
    include 'sidebar_admin.php';
?>

    <div class="main-content">
        
    <?php include 'header_admin.php'; ?>

        <div class="content">
            <div class="page-header">
                <h1 class="page-title">Kelola Pengguna</h1>
                <button class="btn btn-primary" onclick="openModal('addUserModal')">
                    <i class="fas fa-user-plus"></i>
                    Tambah User Baru
                </button>
            </div>

            <?php if ($message): ?>
                <div class="message <?php echo $message_type; ?>">
                    <i class="fas <?php echo $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_users']; ?></div>
                    <div class="stat-label">Total Pengguna</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_admin']; ?></div>
                    <div class="stat-label">Admin</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_dosen']; ?></div>
                    <div class="stat-label">Dosen</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_mahasiswa']; ?></div>
                    <div class="stat-label">Mahasiswa</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['active_users']; ?></div>
                    <div class="stat-label">Pengguna Aktif</div>
                </div>
            </div>

            <div class="controls">
                <div class="search-box">
                    <input type="text" class="search-input" placeholder="Cari pengguna..." id="searchInput">
                    <button class="btn btn-primary btn-sm">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <select class="filter-select" id="roleFilter">
                        <option value="">Semua Role</option>
                        <option value="admin">Admin</option>
                        <option value="dosen">Dosen</option>
                        <option value="mahasiswa">Mahasiswa</option>
                    </select>
                    <select class="filter-select" id="statusFilter">
                        <option value="">Semua Status</option>
                        <option value="active">Aktif</option>
                        <option value="inactive">Tidak Aktif</option>
                    </select>
                </div>
            </div>

            <div class="users-table">
                <?php if (!empty($users)): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Pengguna</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Login Terakhir</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="usersTableBody">
                            <?php foreach($users as $user): ?>
                                <tr data-role="<?php echo $user['role']; ?>" data-status="<?php echo $user['status']; ?>">
                                    <td>
                                        <div class="user-info-cell">
                                            <div class="user-avatar-table">
                                                <?php echo strtoupper(substr($user['full_name'], 0, 2)); ?>
                                            </div>
                                            <div class="user-details">
                                                <h4><?php echo htmlspecialchars($user['full_name']); ?></h4>
                                                <p><?php echo htmlspecialchars($user['email']); ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($user['username']); ?></strong><br>
                                        <small><?php echo htmlspecialchars($user['phone_number'] ?? '-'); ?></small>
                                    </td>
                                    <td>
                                        <span class="role-badge role-<?php echo $user['role']; ?>">
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $user['status']; ?>">
                                            <?php echo $user['status'] === 'active' ? 'Aktif' : 'Tidak Aktif'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="last-login">
                                            <?php echo $user['last_login'] ? date('d/m/Y H:i', strtotime($user['last_login'])) : 'Belum pernah'; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn btn-primary btn-sm" onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>

                                            <?php if ($user['role'] !== 'admin'): ?>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Apakah Anda yakin?')">
                                                    <input type="hidden" name="action" value="toggle_status">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                                    <input type="hidden" name="new_status" value="<?php echo $user['status'] === 'active' ? 'inactive' : 'active'; ?>">
                                                    <?php if($user['status'] === 'active'): ?>
                                                        <button type="submit" class="btn btn-danger btn-sm" title="Nonaktifkan">
                                                            <i class="fas fa-ban"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <button type="submit" class="btn btn-success btn-sm" title="Aktifkan">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </form>
                                            <?php endif; ?>
                                            </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-users" style="font-size: 48px; margin-bottom: 15px; color: #ccc;"></i>
                        <p>Belum ada data pengguna atau koneksi database gagal.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div id="editUserModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">Edit Pengguna</h2>
            <span class="close" onclick="closeModal('editUserModal')">&times;</span>
        </div>
        <div class="modal-body">
            <form id="editUserForm" method="POST">
                <input type="hidden" name="action" value="edit_user">
                <input type="hidden" name="user_id" id="editUserId">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="editFullName">Nama Lengkap</label>
                        <input type="text" id="editFullName" name="full_name" class="form-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="editUsername">Username</label>
                        <input type="text" id="editUsername" name="username" class="form-input" required>
                    </div>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="editEmail">Email</label>
                        <input type="email" id="editEmail" name="email" class="form-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="editPhone">Nomor Telepon</label>
                        <input type="tel" id="editPhone" name="phone_number" class="form-input">
                    </div>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="editRole">Role</label>
                        <select id="editRole" name="role" class="form-select" required>
                            <option value="">Pilih Role</option>
                            <option value="admin">Admin</option>
                            <option value="dosen">Dosen</option>
                            <option value="mahasiswa">Mahasiswa</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="editStatus">Status</label>
                        <select id="editStatus" name="status" class="form-select" required>
                            <option value="">Pilih Status</option>
                            <option value="active">Aktif</option>
                            <option value="inactive">Tidak Aktif</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="editPassword">Password Baru</label>
                    <input type="password" id="editPassword" name="password" class="form-input">
                    <div class="form-help">Kosongkan jika tidak ingin mengubah password</div>
                </div>
                
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('editUserModal')">
                <i class="fas fa-times"></i>
                Batal
            </button>
            <button type="submit" form="editUserForm" class="btn btn-primary">
                <i class="fas fa-save"></i>
                Update User
            </button>
        </div>
    </div>
</div>

<!-- Modal Add User - Jika belum ada -->
<div id="addUserModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">Tambah User Baru</h2>
            <span class="close" onclick="closeModal('addUserModal')">&times;</span>
        </div>
        <div class="modal-body">
            <form id="addUserForm" method="POST">
                <input type="hidden" name="action" value="add_user">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="fullName">Nama Lengkap</label>
                        <input type="text" id="fullName" name="full_name" class="form-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="username">Username</label>
                        <input type="text" id="username" name="username" class="form-input" required>
                    </div>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="phoneNumber">Nomor Telepon</label>
                        <input type="tel" id="phoneNumber" name="phone_number" class="form-input">
                    </div>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="role">Role</label>
                        <select id="role" name="role" class="form-select" required>
                            <option value="">Pilih Role</option>
                            <option value="admin">Admin</option>
                            <option value="dosen">Dosen</option>
                            <option value="mahasiswa">Mahasiswa</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="status">Status</label>
                        <select id="status" name="status" class="form-select" required>
                            <option value="active">Aktif</option>
                            <option value="inactive">Tidak Aktif</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-input" required>
                </div>
                
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('addUserModal')">
                <i class="fas fa-times"></i>
                Batal
            </button>
            <button type="submit" form="addUserForm" class="btn btn-primary">
                <i class="fas fa-user-plus"></i>
                Tambah User
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

        function editUser(userData) {
            document.getElementById('editUserId').value = userData.user_id;
            document.getElementById('editFullName').value = userData.full_name;
            document.getElementById('editUsername').value = userData.username;
            document.getElementById('editEmail').value = userData.email;
            document.getElementById('editPhone').value = userData.phone_number || '';
            document.getElementById('editRole').value = userData.role;
            document.getElementById('editStatus').value = userData.status;
            
            openModal('editUserModal');
        }

        function filterUsers() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const roleFilter = document.getElementById('roleFilter').value;
            const statusFilter = document.getElementById('statusFilter').value;
            const rows = document.querySelectorAll('#usersTableBody tr');

            rows.forEach(row => {
                const userText = row.textContent.toLowerCase();
                const userRole = row.getAttribute('data-role');
                const userStatus = row.getAttribute('data-status');

                const matchSearch = searchTerm === '' || userText.includes(searchTerm);
                const matchRole = roleFilter === '' || userRole === roleFilter;
                const matchStatus = statusFilter === '' || userStatus === statusFilter;

                row.style.display = matchSearch && matchRole && matchStatus ? '' : 'none';
            });
        }

        function hidePassword(inputId) {
            const passwordInput = document.getElementById(inputId);
            const toggleIcon = document.getElementById(inputId + 'ToggleIcon');
            
            if (passwordInput && toggleIcon) {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
                toggleIcon.title = 'Tampilkan password';
            }
        }

        // Event listener untuk menyembunyikan password saat form di-submit
        document.addEventListener('DOMContentLoaded', function() {
            const forms = ['addUserForm', 'editUserForm'];
            
            forms.forEach(formId => {
                const form = document.getElementById(formId);
                if (form) {
                    form.addEventListener('submit', function() {
                        // Sembunyikan semua password sebelum submit
                        const passwordInputs = form.querySelectorAll('input[type="text"][name="password"], input[type="text"][id*="assword"]');
                        passwordInputs.forEach(input => {
                            if (input.id) {
                                hidePassword(input.id);
                            }
                        });
                    });
                }
            });
            
            // Hide password saat modal ditutup
            const modals = ['addUserModal', 'editUserModal'];
            modals.forEach(modalId => {
                const modal = document.getElementById(modalId);
                if (modal) {
                    // Event saat modal ditutup dengan tombol close
                    const closeBtn = modal.querySelector('.close');
                    if (closeBtn) {
                        closeBtn.addEventListener('click', function() {
                            hideAllPasswordsInModal(modalId);
                        });
                    }
                    
                    // Event saat modal ditutup dengan klik luar
                    modal.addEventListener('click', function(e) {
                        if (e.target === modal) {
                            hideAllPasswordsInModal(modalId);
                        }
                    });
                }
            });
        });

        function hideAllPasswordsInModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                const passwordInputs = modal.querySelectorAll('input[type="text"][name="password"], input[type="text"][id*="assword"]');
                passwordInputs.forEach(input => {
                    if (input.id) {
                        hidePassword(input.id);
                    }
                });
            }
        }

        // Keyboard accessibility - Enter/Space untuk toggle
        document.addEventListener('keydown', function(e) {
            if ((e.key === 'Enter' || e.key === ' ') && e.target.classList.contains('password-toggle-btn')) {
                e.preventDefault();
                e.target.click();
            }
        });

        // Event listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Search and filter functionality
            document.getElementById('searchInput').addEventListener('input', filterUsers);
            document.getElementById('roleFilter').addEventListener('change', filterUsers);
            document.getElementById('statusFilter').addEventListener('change', filterUsers);

            // Form submissions - handled by PHP, no need to preventDefault
            document.getElementById('addUserForm').addEventListener('submit', function(e) {
                // Form will be submitted normally to PHP
                closeModal('addUserModal');
            });

            document.getElementById('editUserForm').addEventListener('submit', function(e) {
                // Form will be submitted normally to PHP
                closeModal('editUserModal');
            });

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