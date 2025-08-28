<?php
require_once '../koneksi.php';

$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$software_id = isset($_GET['software_id']) ? intval($_GET['software_id']) : 0;
$brief = isset($_GET['brief']) ? true : false;

if ($software_id > 0) {
    try {
        $query = "SELECT ls.lab_software_id, ls.installation_date, l.lab_name, u.full_name as installer_name
                  FROM lab_software ls
                  JOIN laboratory l ON ls.lab_id = l.lab_id
                  LEFT JOIN users u ON ls.installed_by = u.user_id
                  WHERE ls.software_id = :software_id
                  ORDER BY ls.installation_date DESC";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':software_id', $software_id);
        $stmt->execute();
        
        $installations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['installations' => $installations]);
        
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['error' => 'Invalid software ID']);
}
?>