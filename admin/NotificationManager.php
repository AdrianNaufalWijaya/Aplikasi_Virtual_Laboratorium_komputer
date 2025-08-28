<?php
// NotificationManager.php
class NotificationManager {
    private $conn;
    private $table_name = "notification";
    private $table_structure = [];

    public function __construct($db) {
        $this->conn = $db;
        $this->checkTableStructure();
    }

    private function checkTableStructure() {
        try {
            // Cek struktur tabel notification
            $query = "SHOW COLUMNS FROM " . $this->table_name;
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($columns as $column) {
                $this->table_structure[$column['Field']] = $column['Type'];
            }
            
        } catch (Exception $e) {
            error_log("Error checking table structure: " . $e->getMessage());
        }
    }

    private function hasColumn($column_name) {
        return isset($this->table_structure[$column_name]);
    }

    public function sendNotification($title, $message, $type, $recipients = [], $created_by = null) {
        try {
            // Pastikan kolom user_id ada
            if (!$this->hasColumn('user_id')) {
                return ['success' => false, 'message' => 'Tabel notification belum memiliki kolom user_id. Silakan jalankan script SQL update.'];
            }

            $this->conn->beginTransaction();
            
            $success_count = 0;
            
            // Jika recipients kosong dan bukan array kosong, kirim ke semua user aktif
            // Jika recipients adalah array kosong [], berarti kirim ke semua
            // Jika recipients berisi user_id, kirim hanya ke mereka
            if (empty($recipients)) {
                $recipients = $this->getAllActiveUserIds();
            }
            
            // Debug: Log recipients untuk debugging
            error_log("Sending notification to recipients: " . implode(', ', $recipients));
            
            // Insert notifikasi untuk setiap recipient
            foreach ($recipients as $user_id) {
                // Validasi user_id
                if (empty($user_id) || !is_numeric($user_id)) {
                    continue;
                }
                
                // Buat query berdasarkan kolom yang tersedia
                $columns = ['user_id', 'title', 'message', 'type'];
                $placeholders = [':user_id', ':title', ':message', ':type'];
                
                if ($this->hasColumn('is_read')) {
                    $columns[] = 'is_read';
                    $placeholders[] = '0';
                }
                
                if ($this->hasColumn('created_at')) {
                    $columns[] = 'created_at';
                    $placeholders[] = 'NOW()';
                }
                
                $query = "INSERT INTO " . $this->table_name . " 
                         (" . implode(', ', $columns) . ") 
                         VALUES (" . implode(', ', $placeholders) . ")";
                
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(":user_id", $user_id);
                $stmt->bindParam(":title", $title);
                $stmt->bindParam(":message", $message);
                $stmt->bindParam(":type", $type);
                
                if($stmt->execute()) {
                    $success_count++;
                } else {
                    error_log("Failed to insert notification for user_id: " . $user_id);
                }
            }
            
            $this->conn->commit();
            return ['success' => true, 'sent_count' => $success_count];
            
        } catch(Exception $e) {
            $this->conn->rollback();
            error_log("Error in sendNotification: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function getAllActiveUserIds() {
        try {
            // Cek apakah kolom status ada di tabel users
            $check_query = "SHOW COLUMNS FROM users LIKE 'status'";
            $check_stmt = $this->conn->prepare($check_query);
            $check_stmt->execute();
            $has_status = $check_stmt->rowCount() > 0;
            
            if ($has_status) {
                $query = "SELECT user_id FROM users WHERE status = 'active'";
            } else {
                $query = "SELECT user_id FROM users";
            }
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return array_column($result, 'user_id');
        } catch (Exception $e) {
            error_log("Error getting active users: " . $e->getMessage());
            return [];
        }
    }

    public function getUsersByRole($role) {
        try {
            // Cek apakah kolom role dan status ada
            $check_query = "SHOW COLUMNS FROM users LIKE 'role'";
            $check_stmt = $this->conn->prepare($check_query);
            $check_stmt->execute();
            $has_role = $check_stmt->rowCount() > 0;
            
            $check_query2 = "SHOW COLUMNS FROM users LIKE 'status'";
            $check_stmt2 = $this->conn->prepare($check_query2);
            $check_stmt2->execute();
            $has_status = $check_stmt2->rowCount() > 0;
            
            if (!$has_role) {
                error_log("Column 'role' not found in users table");
                return [];
            }
            
            if ($has_status) {
                $query = "SELECT user_id, username, email, role FROM users WHERE role = :role AND status = 'active'";
            } else {
                $query = "SELECT user_id, username, email, role FROM users WHERE role = :role";
            }
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":role", $role);
            $stmt->execute();
            
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Debug: Log hasil query
            error_log("getUsersByRole($role) found " . count($users) . " users");
            foreach ($users as $user) {
                error_log("User: " . $user['user_id'] . " - " . $user['username'] . " - " . $user['role']);
            }
            
            return $users;
        } catch (Exception $e) {
            error_log("Error getting users by role: " . $e->getMessage());
            return [];
        }
    }

    public function getAllUsers() {
        try {
            // Cek apakah kolom role dan status ada
            $check_query = "SHOW COLUMNS FROM users LIKE 'role'";
            $check_stmt = $this->conn->prepare($check_query);
            $check_stmt->execute();
            $has_role = $check_stmt->rowCount() > 0;
            
            $check_query2 = "SHOW COLUMNS FROM users LIKE 'status'";
            $check_stmt2 = $this->conn->prepare($check_query2);
            $check_stmt2->execute();
            $has_status = $check_stmt2->rowCount() > 0;
            
            $select_fields = "user_id, username, email";
            $where_clause = "";
            $order_clause = "ORDER BY username";
            
            if ($has_role) {
                $select_fields .= ", role";
                $order_clause = "ORDER BY role, username";
            }
            
            if ($has_status) {
                $where_clause = "WHERE status = 'active'";
            }
            
            $query = "SELECT {$select_fields} FROM users {$where_clause} {$order_clause}";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Jika tidak ada kolom role, set default
            if (!$has_role) {
                foreach ($users as &$user) {
                    $user['role'] = 'mahasiswa';
                }
            }
            
            return $users;
        } catch (Exception $e) {
            error_log("Error getting all users: " . $e->getMessage());
            return [];
        }
    }

    public function getNotificationHistory($limit = 50) {
        try {
            // Pastikan kolom yang diperlukan ada
            if (!$this->hasColumn('user_id')) {
                return [];
            }

            $select_fields = "title, message, type, created_at";
            $group_fields = "title, message, type, DATE(created_at)";
            
            if ($this->hasColumn('is_read')) {
                $select_fields .= ", COUNT(*) as total_recipients, SUM(CASE WHEN is_read = 1 THEN 1 ELSE 0 END) as read_count";
            } else {
                $select_fields .= ", COUNT(*) as total_recipients, 0 as read_count";
            }
            
            $query = "SELECT {$select_fields}
                     FROM " . $this->table_name . " 
                     GROUP BY {$group_fields}
                     ORDER BY created_at DESC
                     LIMIT :limit";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting notification history: " . $e->getMessage());
            return [];
        }
    }

    public function markAsRead($notification_id, $user_id) {
        try {
            if (!$this->hasColumn('is_read') || !$this->hasColumn('user_id')) {
                return false;
            }

            $set_clause = "is_read = 1";
            if ($this->hasColumn('read_at')) {
                $set_clause .= ", read_at = NOW()";
            }
            
            $query = "UPDATE " . $this->table_name . " 
                     SET {$set_clause}
                     WHERE notification_id = :notification_id AND user_id = :user_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":notification_id", $notification_id);
            $stmt->bindParam(":user_id", $user_id);
            
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error marking as read: " . $e->getMessage());
            return false;
        }
    }

    public function getUserNotifications($user_id, $limit = 50) {
        try {
            if (!$this->hasColumn('user_id')) {
                return [];
            }

            $query = "SELECT * FROM " . $this->table_name . "
                     WHERE user_id = :user_id
                     ORDER BY created_at DESC
                     LIMIT :limit";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
            $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting user notifications: " . $e->getMessage());
            return [];
        }
    }

    public function getUnreadCount($user_id) {
        try {
            if (!$this->hasColumn('user_id') || !$this->hasColumn('is_read')) {
                return 0;
            }

            $query = "SELECT COUNT(*) as unread_count 
                     FROM " . $this->table_name . " 
                     WHERE user_id = :user_id AND is_read = 0";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $user_id);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['unread_count'];
        } catch (Exception $e) {
            error_log("Error getting unread count: " . $e->getMessage());
            return 0;
        }
    }

    public function markAllAsRead($user_id) {
        try {
            if (!$this->hasColumn('is_read') || !$this->hasColumn('user_id')) {
                return false;
            }

            $set_clause = "is_read = 1";
            if ($this->hasColumn('read_at')) {
                $set_clause .= ", read_at = NOW()";
            }
            
            $query = "UPDATE " . $this->table_name . " 
                     SET {$set_clause}
                     WHERE user_id = :user_id AND is_read = 0";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $user_id);
            
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error marking all as read: " . $e->getMessage());
            return false;
        }
    }

    // Method untuk debug - cek struktur tabel
    public function getTableStructure() {
        return $this->table_structure;
    }
}
?>