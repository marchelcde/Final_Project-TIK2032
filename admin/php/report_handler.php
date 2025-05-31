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
        // Receive data either from JSON body (for AJAX with application/json) or $_POST (for standard form/multipart)
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $input = $_POST; // Fallback for x-www-form-urlencoded or multipart/form-data
        }
        
        // Validate required fields
        $required = ['nama', 'email', 'telepon', 'kategori', 'judul', 'deskripsi', 'lokasi'];
        foreach ($required as $field) {
            if (empty($input[$field])) { // Use $input instead of $data
                jsonResponse(['error' => "Field '$field' is required"], 400); // Improved error message
            }
        }
        
        // Handle file upload if present
        $fotoBuktiData = null;
        if (isset($_FILES['foto_bukti']) && $_FILES['foto_bukti']['error'] === UPLOAD_ERR_OK) {
            // Read the binary content of the uploaded file
            $fotoBuktiData = file_get_contents($_FILES['foto_bukti']['tmp_name']);
            if ($fotoBuktiData === false) {
                jsonResponse(['error' => 'Failed to read uploaded photo content.'], 500);
                return;
            }
        }

        // Generate report ID (if your 'id' column is not AUTO_INCREMENT)
        $reportId = generateId('RPT'); 
        
        // Prepare SQL - Added foto_bukti column to INSERT statement
        $query = "INSERT INTO reports (id, nama, email, telepon, kategori, judul, deskripsi, lokasi, foto_bukti, status, created_at) 
                  VALUES (:id, :nama, :email, :telepon, :kategori, :judul, :deskripsi, :lokasi, :foto_bukti, 'pending', NOW())";
        
        $stmt = $db->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':id', $reportId);
        $stmt->bindParam(':nama', sanitize($input['nama']));
        $stmt->bindParam(':email', sanitize($input['email']));
        $stmt->bindParam(':telepon', sanitize($input['telepon']));
        $stmt->bindParam(':kategori', sanitize($input['kategori']));
        $stmt->bindParam(':judul', sanitize($input['judul']));
        $stmt->bindParam(':deskripsi', sanitize($input['deskripsi']));
        $stmt->bindParam(':lokasi', sanitize($input['lokasi']));
        $stmt->bindParam(':foto_bukti', $fotoBuktiData, PDO::PARAM_LOB); // Bind BLOB data
        
        if ($stmt->execute()) {
            jsonResponse([
                'success' => true,
                'message' => 'Laporan berhasil dibuat',
                'report_id' => $reportId
            ]);
        } else {
            jsonResponse(['error' => 'Failed to save report'], 500);
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
        
        // Include foto_bukti in the select list to retrieve it
        $query = "SELECT id, nama, email, telepon, kategori, judul, deskripsi, lokasi, foto_bukti, status, created_at FROM reports WHERE 1=1";
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
            // If you want to display the image, you'll need to base64 encode it on the PHP side
            // or create a separate endpoint to serve the image.
            if (!empty($report['foto_bukti'])) {
                $report['foto_bukti_base64'] = base64_encode($report['foto_bukti']);
            }
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
        
        // Include foto_bukti in the select list to retrieve it
        $query = "SELECT id, nama, email, telepon, kategori, judul, deskripsi, lokasi, foto_bukti, status, created_at FROM reports WHERE id = :id";
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
        // If you want to display the image, you'll need to base64 encode it on the PHP side
        // or create a separate endpoint to serve the image.
        if (!empty($report['foto_bukti'])) {
            $report['foto_bukti_base64'] = base64_encode($report['foto_bukti']);
        }
        
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