<?php
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($method) {
    case 'POST':
        if ($action === 'create') {
            createReport($db);
        } elseif ($action === 'update_status') {
            updateReportStatus($db);
        }
        break;
    
    case 'GET':
        if ($action === 'list') {
            getReports($db);
        } elseif ($action === 'detail') {
            getReportDetail($db);
        } elseif ($action === 'stats') {
            getReportStats($db);
        }
        break;
    
    default:
        jsonResponse(['error' => 'Method not allowed'], 405);
}

function createReport($db) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        $required = ['nama', 'email', 'telepon', 'kategori', 'judul', 'deskripsi', 'lokasi'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                jsonResponse(['error' => "Field $field is required"], 400);
            }
        }
        
        // Generate report ID
        $reportId = generateId('RPT');
        
        // Prepare SQL
        $query = "INSERT INTO reports (id, nama, email, telepon, kategori, judul, deskripsi, lokasi, status, created_at) 
                  VALUES (:id, :nama, :email, :telepon, :kategori, :judul, :deskripsi, :lokasi, 'pending', NOW())";
        
        $stmt = $db->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':id', $reportId);
        $stmt->bindParam(':nama', sanitize($data['nama']));
        $stmt->bindParam(':email', sanitize($data['email']));
        $stmt->bindParam(':telepon', sanitize($data['telepon']));
        $stmt->bindParam(':kategori', sanitize($data['kategori']));
        $stmt->bindParam(':judul', sanitize($data['judul']));
        $stmt->bindParam(':deskripsi', sanitize($data['deskripsi']));
        $stmt->bindParam(':lokasi', sanitize($data['lokasi']));
        
        if ($stmt->execute()) {
            jsonResponse([
                'success' => true,
                'message' => 'Laporan berhasil dibuat',
                'report_id' => $reportId
            ]);
        } else {
            jsonResponse(['error' => 'Gagal menyimpan laporan'], 500);
        }
        
    } catch (Exception $e) {
        jsonResponse(['error' => $e->getMessage()], 500);
    }
}

function getReports($db) {
    try {
        $status = $_GET['status'] ?? '';
        $category = $_GET['category'] ?? '';
        $limit = $_GET['limit'] ?? 50;
        
        $query = "SELECT * FROM reports WHERE 1=1";
        $params = [];
        
        if ($status) {
            $query .= " AND status = :status";
            $params[':status'] = $status;
        }
        
        if ($category) {
            $query .= " AND kategori = :category";
            $params[':category'] = $category;
        }
        
        $query .= " ORDER BY created_at DESC LIMIT :limit";
        
        $stmt = $db->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindParam($key, $value);
        }
        $stmt->bindParam(':limit', (int)$limit, PDO::PARAM_INT);
        
        $stmt->execute();
        $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format data
        foreach ($reports as &$report) {
            $report['status_text'] = getStatusText($report['status']);
            $report['category_text'] = getCategoryText($report['kategori']);
            $report['formatted_date'] = formatDate($report['created_at']);
        }
        
        jsonResponse([
            'success' => true,
            'data' => $reports
        ]);
        
    } catch (Exception $e) {
        jsonResponse(['error' => $e->getMessage()], 500);
    }
}

function getReportDetail($db) {
    try {
        $id = $_GET['id'] ?? '';
        
        if (empty($id)) {
            jsonResponse(['error' => 'Report ID is required'], 400);
        }
        
        $query = "SELECT * FROM reports WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $report = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$report) {
            jsonResponse(['error' => 'Report not found'], 404);
        }
        
        // Format data
        $report['status_text'] = getStatusText($report['status']);
        $report['category_text'] = getCategoryText($report['kategori']);
        $report['formatted_date'] = formatDate($report['created_at']);
        
        jsonResponse([
            'success' => true,
            'data' => $report
        ]);
        
    } catch (Exception $e) {
        jsonResponse(['error' => $e->getMessage()], 500);
    }
}

function updateReportStatus($db) {
    try {
        // requireAdmin(); // Uncomment when authentication is implemented
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['id']) || empty($data['status'])) {
            jsonResponse(['error' => 'ID and status are required'], 400);
        }
        
        $validStatuses = ['pending', 'in_progress', 'completed', 'rejected'];
        if (!in_array($data['status'], $validStatuses)) {
            jsonResponse(['error' => 'Invalid status'], 400);
        }
        
        $query = "UPDATE reports SET status = :status, updated_at = NOW() WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':status', $data['status']);
        $stmt->bindParam(':id', $data['id']);
        
        if ($stmt->execute()) {
            jsonResponse([
                'success' => true,
                'message' => 'Status laporan berhasil diperbarui'
            ]);
        } else {
            jsonResponse(['error' => 'Gagal memperbarui status'], 500);
        }
        
    } catch (Exception $e) {
        jsonResponse(['error' => $e->getMessage()], 500);
    }
}

function getReportStats($db) {
    try {
        // Total reports
        $stmt = $db->query("SELECT COUNT(*) as total FROM reports");
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Reports by status
        $stmt = $db->query("SELECT status, COUNT(*) as count FROM reports GROUP BY status");
        $statusStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Reports by category
        $stmt = $db->query("SELECT kategori, COUNT(*) as count FROM reports GROUP BY kategori");
        $categoryStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Reports by month (last 6 months)
        $stmt = $db->query("
            SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as count 
            FROM reports 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month
        ");
        $monthlyStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        jsonResponse([
            'success' => true,
            'data' => [
                'total' => $total,
                'by_status' => $statusStats,
                'by_category' => $categoryStats,
                'by_month' => $monthlyStats
            ]
        ]);
        
    } catch (Exception $e) {
        jsonResponse(['error' => $e->getMessage()], 500);
    }
}
?>
