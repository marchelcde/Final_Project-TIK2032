<?php
// Admin-specific Operations Handler
// Location: FINAL_PROJECT-TIK2032/admin/php/admin_handler.php

require_once 'config.php'; // Includes session_start() and Database class, etc.

header('Content-Type: application/json');

// Check if user is admin (using the config's isAdmin function)
function checkAdminAccess() {
    if (!isAdmin()) { // Use isAdmin from config.php
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
    
    case 'update_report_status': // Admin can update status and add feedback
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
    
    case 'add_admin_feedback': // New action for admin to add feedback
        addAdminFeedback();
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
        
        // Also fetch feedback_admin and user_id for display/context if needed
        $query = "SELECT id, user_id, nama, email, telepon, kategori, judul, deskripsi, lokasi, status, created_at, feedback_admin FROM reports WHERE 1=1";
        $params = [];
        
        if (!empty($status)) {
            $query .= " AND status = :status";
            $params[':status'] = $status;
        }
        
        if (!empty($category)) {
            $query .= " AND kategori = :category";
            $params[':category'] = $category;
        }
        
        $query .= " ORDER BY created_at DESC";
        
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format data (using functions from config.php)
        foreach ($reports as &$report) {
            $report['status_text'] = getStatusText($report['status']);
            $report['category_text'] = getCategoryText($report['kategori']);
            $report['formatted_date'] = formatDate($report['created_at']);
        }
        
        echo json_encode(['reports' => $reports]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch reports: ' . $e->getMessage()]);
    }
}

function updateReportStatus() {
    checkAdminAccess(); // Ensure admin is logged in
    
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
        
        // Update status and updated_at timestamp
        $stmt = $conn->prepare("UPDATE reports SET status = :status, updated_at = NOW() WHERE id = :id");
        $stmt->execute([
            ':status' => $newStatus,
            ':id' => $reportId
        ]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Status laporan berhasil diperbarui']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Report not found']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update report status: ' . $e->getMessage()]);
    }
}

// New function for admin to add/update feedback
function addAdminFeedback() {
    checkAdminAccess(); // Ensure admin is logged in

    try {
        $input = json_decode(file_get_contents('php://input'), true);
        $reportId = $input['reportId'] ?? '';
        $feedback = $input['feedback'] ?? '';
        // Optional: $adminId = $_SESSION['user_id'] ?? null; // To track which admin gave feedback

        if (empty($reportId)) {
            http_response_code(400);
            jsonResponse(['error' => 'Report ID is required']);
            return;
        }
        
        $database = new Database();
        $conn = $database->getConnection();
        
        $stmt = $conn->prepare("UPDATE reports SET feedback_admin = :feedback, updated_at = NOW() WHERE id = :id");
        $stmt->execute([
            ':feedback' => sanitize($feedback),
            ':id' => $reportId
        ]);

        if ($stmt->rowCount() > 0) {
            jsonResponse(['success' => true, 'message' => 'Feedback admin berhasil ditambahkan']);
        } else {
            http_response_code(404);
            jsonResponse(['error' => 'Laporan tidak ditemukan']);
        }

    } catch (Exception $e) {
        http_response_code(500);
        jsonResponse(['error' => 'Gagal menambahkan feedback admin: ' . $e->getMessage()]);
    }
}

function deleteReport() {
    checkAdminAccess();
    
    try {
        $reportId = $_POST['reportId'] ?? ''; // Assuming POST for delete
        
        if (empty($reportId)) {
            http_response_code(400);
            echo json_encode(['error' => 'Report ID is required']);
            return;
        }
        
        $database = new Database();
        $conn = $database->getConnection();
        
        // Delete related comments first (assuming 'report_comments' table)
        $stmt = $conn->prepare("DELETE FROM report_comments WHERE report_id = :id");
        $stmt->execute([':id' => $reportId]);
        
        // Delete the report
        $stmt = $conn->prepare("DELETE FROM reports WHERE id = :id");
        $stmt->execute([':id' => $reportId]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Laporan berhasil dihapus']);
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
        $stmt = $conn->query("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count FROM reports WHERE YEAR(created_at) = YEAR(CURDATE()) GROUP BY MONTH(created_at) ORDER BY month"); // Changed from tanggal to created_at
        $monthlyStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        jsonResponse([
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
        
        $stmt = $conn->prepare("SELECT id, judul, kategori, status, created_at FROM reports ORDER BY created_at DESC LIMIT :limit"); // Changed from tanggal to created_at
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format data (using functions from config.php)
        foreach ($reports as &$report) {
            $report['status_text'] = getStatusText($report['status']);
            $report['category_text'] = getCategoryText($report['kategori']);
            $report['formatted_date'] = formatDate($report['created_at']);
        }
        
        echo json_encode(['reports' => $reports]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch recent reports: ' . $e->getMessage()]);
    }
}
?>
