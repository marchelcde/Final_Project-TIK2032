<?php
// Admin-specific Operations Handler
require_once 'config.php';

header('Content-Type: application/json');

// Check if user is admin
function checkAdminAccess() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied. Admin privileges required.']);
        exit;
    }
}

// Handle different admin operations
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'get_dashboard_stats':
        getDashboardStats();
        break;
    
    case 'get_all_reports':
        getAllReports();
        break;
    
    case 'update_report_status':
        updateReportStatus();
        break;
    
    case 'delete_report':
        deleteReport();
        break;
    
    case 'get_statistics':
        getStatistics();
        break;
    
    case 'get_recent_reports':
        getRecentReports();
        break;
    
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
}

function getDashboardStats() {
    checkAdminAccess();
    
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        // Get total reports count
        $stmt = $conn->query("SELECT COUNT(*) as total FROM reports");
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get pending reports count
        $stmt = $conn->query("SELECT COUNT(*) as pending FROM reports WHERE status = 'pending'");
        $pending = $stmt->fetch(PDO::FETCH_ASSOC)['pending'];
        
        // Get completed reports count
        $stmt = $conn->query("SELECT COUNT(*) as completed FROM reports WHERE status = 'completed'");
        $completed = $stmt->fetch(PDO::FETCH_ASSOC)['completed'];
        
        // Get rejected reports count
        $stmt = $conn->query("SELECT COUNT(*) as rejected FROM reports WHERE status = 'rejected'");
        $rejected = $stmt->fetch(PDO::FETCH_ASSOC)['rejected'];
        
        echo json_encode([
            'total' => $total,
            'pending' => $pending,
            'completed' => $completed,
            'rejected' => $rejected
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch dashboard stats: ' . $e->getMessage()]);
    }
}

function getAllReports() {
    checkAdminAccess();
    
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        $status = $_GET['status'] ?? '';
        $category = $_GET['category'] ?? '';
        
        $query = "SELECT * FROM reports WHERE 1=1";
        $params = [];
        
        if (!empty($status)) {
            $query .= " AND status = :status";
            $params[':status'] = $status;
        }
        
        if (!empty($category)) {
            $query .= " AND kategori = :category";
            $params[':category'] = $category;
        }
        
        $query .= " ORDER BY tanggal DESC";
        
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['reports' => $reports]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch reports: ' . $e->getMessage()]);
    }
}

function updateReportStatus() {
    checkAdminAccess();
    
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        $reportId = $input['reportId'] ?? '';
        $newStatus = $input['status'] ?? '';
        
        if (empty($reportId) || empty($newStatus)) {
            http_response_code(400);
            echo json_encode(['error' => 'Report ID and status are required']);
            return;
        }
        
        $validStatuses = ['pending', 'in_progress', 'completed', 'rejected'];
        if (!in_array($newStatus, $validStatuses)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid status']);
            return;
        }
        
        $database = new Database();
        $conn = $database->getConnection();
        
        $stmt = $conn->prepare("UPDATE reports SET status = :status WHERE id = :id");
        $stmt->execute([
            ':status' => $newStatus,
            ':id' => $reportId
        ]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Report status updated successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Report not found']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update report status: ' . $e->getMessage()]);
    }
}

function deleteReport() {
    checkAdminAccess();
    
    try {
        $reportId = $_POST['reportId'] ?? '';
        
        if (empty($reportId)) {
            http_response_code(400);
            echo json_encode(['error' => 'Report ID is required']);
            return;
        }
        
        $database = new Database();
        $conn = $database->getConnection();
        
        // Delete related comments first
        $stmt = $conn->prepare("DELETE FROM comments WHERE report_id = :id");
        $stmt->execute([':id' => $reportId]);
        
        // Delete the report
        $stmt = $conn->prepare("DELETE FROM reports WHERE id = :id");
        $stmt->execute([':id' => $reportId]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Report deleted successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Report not found']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete report: ' . $e->getMessage()]);
    }
}

function getStatistics() {
    checkAdminAccess();
    
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        // Get reports by category
        $stmt = $conn->query("SELECT kategori, COUNT(*) as count FROM reports GROUP BY kategori");
        $categoryStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get reports by status
        $stmt = $conn->query("SELECT status, COUNT(*) as count FROM reports GROUP BY status");
        $statusStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get monthly reports for the current year
        $stmt = $conn->query("SELECT MONTH(tanggal) as month, COUNT(*) as count FROM reports WHERE YEAR(tanggal) = YEAR(CURDATE()) GROUP BY MONTH(tanggal) ORDER BY month");
        $monthlyStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'categoryStats' => $categoryStats,
            'statusStats' => $statusStats,
            'monthlyStats' => $monthlyStats
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch statistics: ' . $e->getMessage()]);
    }
}

function getRecentReports() {
    checkAdminAccess();
    
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        $limit = $_GET['limit'] ?? 5;
        
        $stmt = $conn->prepare("SELECT * FROM reports ORDER BY tanggal DESC LIMIT :limit");
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['reports' => $reports]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch recent reports: ' . $e->getMessage()]);
    }
}
?>
