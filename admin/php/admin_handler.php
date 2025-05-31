<?php
error_reporting(E_ALL); // Ensure this is enabled for debugging
ini_set('display_errors', 1); // Ensure this is enabled for debugging

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

// Function to get parsed input data (Robust for POST requests)
function getParsedInput() {
    $input = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE && empty($input)) {
        // If JSON decoding failed or input was empty, try $_POST (e.g., for x-www-form-urlencoded)
        $input = $_POST;
    }
    return $input;
}

// Handle different admin operations
$action = $_GET['action'] ?? ''; // GET action first
if (empty($action)) { // If GET action is empty, check POST action
    $postInput = getParsedInput(); // Get parsed POST input
    $action = $postInput['action'] ?? ''; // Extract action from POST input
}


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
    
    case 'get_report_detail':
        getReportDetail();
        break;
    
    case 'add_admin_feedback':
        addAdminFeedback();
        break;

    default:
        http_response_code(400);
        // MODIFIED: Added debug info to default error message
        echo json_encode(['error' => 'Aksi tidak valid atau hilang. Action diterima: "' . ($action ?? 'Tidak ada') . '"']);
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
        
        $query = "SELECT id, user_id, nama, email, telepon, kategori, judul, deskripsi, lokasi, status, created_at, feedback_admin, foto_bukti FROM reports WHERE 1=1";
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

        foreach ($reports as &$report) {
            $report['status_text'] = getStatusText($report['status']);
            $report['category_text'] = getCategoryText($report['kategori']);
            $report['formatted_date'] = formatDate($report['created_at']);
            if (!empty($report['foto_bukti'])) {
                $report['foto_bukti_base64'] = base64_encode($report['foto_bukti']);
            } else {
                $report['foto_bukti_base64'] = null;
            }
            unset($report['foto_bukti']);
        }
        
        echo json_encode(['reports' => $reports]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch reports: ' . $e->getMessage()]);
    }
}

function getReportDetail() {
    checkAdminAccess();
    
    try {
        $id = $_GET['id'] ?? '';
        
        if (empty($id)) {
            jsonResponse(['error' => 'Report ID is required'], 400);
        }
        
        $database = new Database();
        $conn = $database->getConnection();

        $query = "SELECT id, user_id, nama, email, telepon, kategori, judul, deskripsi, lokasi, status, created_at, updated_at, feedback_admin, foto_bukti FROM reports WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $report = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$report) {
            jsonResponse(['error' => 'Report not found'], 404);
        }
        
        $report['status_text'] = getStatusText($report['status']);
        $report['category_text'] = getCategoryText($report['kategori']);
        $report['formatted_date'] = formatDate($report['created_at']);
        if (!empty($report['foto_bukti'])) {
            $report['foto_bukti_base64'] = base64_encode($report['foto_bukti']);
        } else {
            $report['foto_bukti_base64'] = null;
        }
        unset($report['foto_bukti']);
        
        jsonResponse([
            'success' => true,
            'data' => $report
        ]);
        
    } catch (Exception $e) {
        jsonResponse(['error' => $e->getMessage()], 500);
    }
}


function updateReportStatus() {
    checkAdminAccess();
    
    try {
        $input = getParsedInput(); // Use the new robust input function

        $reportId = $input['reportId'] ?? '';
        $newStatus = $input['status'] ?? '';
        
        if (empty($reportId) || empty($newStatus)) {
            http_response_code(400);
            echo json_encode(['error' => 'Report ID and status are required. Debug: reportId="' . $reportId . '", newStatus="' . $newStatus . '"']);
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

function addAdminFeedback() {
    checkAdminAccess();

    try {
        $input = getParsedInput(); // Use the new robust input function
        $reportId = $input['reportId'] ?? '';
        $feedback = $input['feedback'] ?? '';

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
        // MODIFIED: Use getParsedInput for POST data
        $input = getParsedInput();
        $reportId = $input['reportId'] ?? ''; 
        
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
        $stmt = $conn->query("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count FROM reports WHERE YEAR(created_at) = YEAR(CURDATE()) GROUP BY MONTH(created_at) ORDER BY month");
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
        
        $stmt = $conn->prepare("SELECT id, judul, kategori, status, created_at FROM reports ORDER BY created_at DESC LIMIT :limit");
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