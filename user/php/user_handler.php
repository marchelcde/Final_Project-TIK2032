<?php
require_once 'config.php';

header('Content-Type: application/json');

function checkUserAccess() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user_logged_in'])) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied. Please login first.']);
        exit;
    }
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'get_user_reports':
        getUserReports();
        break;
    
    case 'get_user_stats':
        getUserStats();
        break;
    
    case 'delete_user_report':
        deleteUserReport();
        break;
    
    case 'get_report_detail':
        getReportDetail();
        break;
    
    case 'submit_report':
        submitReport();
        break;
    
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
}

function getUserReports() {
    checkUserAccess();
    
    try {
        $userEmail = $_SESSION['user_email'] ?? '';
        
        if (empty($userEmail)) {
            http_response_code(400);
            echo json_encode(['error' => 'User email not found in session']);
            return;
        }
        
        $database = new Database();
        $conn = $database->getConnection();
        
        $status = $_GET['status'] ?? '';
        $search = $_GET['search'] ?? '';
        
        $query = "SELECT * FROM reports WHERE email = :email";
        $params = [':email' => $userEmail];
        
        if (!empty($status)) {
            $query .= " AND status = :status";
            $params[':status'] = $status;
        }
        
        if (!empty($search)) {
            $query .= " AND (judul LIKE :search OR deskripsi LIKE :search OR lokasi LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }
        
        $query .= " ORDER BY tanggal DESC";
        
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['reports' => $reports]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch user reports: ' . $e->getMessage()]);
    }
}

function getUserStats() {
    checkUserAccess();
    
    try {
        $userEmail = $_SESSION['user_email'] ?? '';
        
        if (empty($userEmail)) {
            http_response_code(400);
            echo json_encode(['error' => 'User email not found in session']);
            return;
        }
        
        $database = new Database();
        $conn = $database->getConnection();

        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM reports WHERE email = :email");
        $stmt->execute([':email' => $userEmail]);
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        $stmt = $conn->prepare("SELECT COUNT(*) as pending FROM reports WHERE email = :email AND status = 'pending'");
        $stmt->execute([':email' => $userEmail]);
        $pending = $stmt->fetch(PDO::FETCH_ASSOC)['pending'];

        $stmt = $conn->prepare("SELECT COUNT(*) as in_progress FROM reports WHERE email = :email AND status = 'in_progress'");
        $stmt->execute([':email' => $userEmail]);
        $inProgress = $stmt->fetch(PDO::FETCH_ASSOC)['in_progress'];

        $stmt = $conn->prepare("SELECT COUNT(*) as completed FROM reports WHERE email = :email AND status = 'completed'");
        $stmt->execute([':email' => $userEmail]);
        $completed = $stmt->fetch(PDO::FETCH_ASSOC)['completed'];
        
        echo json_encode([
            'total' => $total,
            'pending' => $pending,
            'in_progress' => $inProgress,
            'completed' => $completed
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch user stats: ' . $e->getMessage()]);
    }
}

function deleteUserReport() {
    checkUserAccess();
    
    try {
        $reportId = $_POST['reportId'] ?? '';
        $userEmail = $_SESSION['user_email'] ?? '';
        
        if (empty($reportId)) {
            http_response_code(400);
            echo json_encode(['error' => 'Report ID is required']);
            return;
        }
        
        $database = new Database();
        $conn = $database->getConnection();

        $stmt = $conn->prepare("SELECT status FROM reports WHERE id = :id AND email = :email");
        $stmt->execute([':id' => $reportId, ':email' => $userEmail]);
        $report = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$report) {
            http_response_code(404);
            echo json_encode(['error' => 'Report not found or access denied']);
            return;
        }
        
        if ($report['status'] !== 'pending') {
            http_response_code(400);
            echo json_encode(['error' => 'Only pending reports can be deleted']);
            return;
        }

        $stmt = $conn->prepare("DELETE FROM comments WHERE report_id = :id");
        $stmt->execute([':id' => $reportId]);

        $stmt = $conn->prepare("DELETE FROM reports WHERE id = :id AND email = :email");
        $stmt->execute([':id' => $reportId, ':email' => $userEmail]);
        
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

function getReportDetail() {
    checkUserAccess();
    
    try {
        $reportId = $_GET['reportId'] ?? '';
        $userEmail = $_SESSION['user_email'] ?? '';
        
        if (empty($reportId)) {
            http_response_code(400);
            echo json_encode(['error' => 'Report ID is required']);
            return;
        }
        
        $database = new Database();
        $conn = $database->getConnection();

        $stmt = $conn->prepare("SELECT * FROM reports WHERE id = :id AND email = :email");
        $stmt->execute([':id' => $reportId, ':email' => $userEmail]);
        $report = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$report) {
            http_response_code(404);
            echo json_encode(['error' => 'Report not found or access denied']);
            return;
        }

        $stmt = $conn->prepare("SELECT * FROM comments WHERE report_id = :id ORDER BY created_at ASC");
        $stmt->execute([':id' => $reportId]);
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'report' => $report,
            'comments' => $comments
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch report details: ' . $e->getMessage()]);
    }
}

function submitReport() {
    checkUserAccess();
    
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $required_fields = ['nama', 'email', 'telepon', 'judul', 'kategori', 'lokasi', 'deskripsi'];
        foreach ($required_fields as $field) {
            if (empty($input[$field])) {
                http_response_code(400);
                echo json_encode(['error' => "Field '$field' is required"]);
                return;
            }
        }
        
        $database = new Database();
        $conn = $database->getConnection();
        
        $reportId = generateId('RPT');
        
        $stmt = $conn->prepare("
            INSERT INTO reports (id, nama, email, telepon, judul, kategori, lokasi, deskripsi, status, tanggal) 
            VALUES (:id, :nama, :email, :telepon, :judul, :kategori, :lokasi, :deskripsi, 'pending', NOW())
        ");
        
        $stmt->execute([
            ':id' => $reportId,
            ':nama' => sanitize($input['nama']),
            ':email' => sanitize($input['email']),
            ':telepon' => sanitize($input['telepon']),
            ':judul' => sanitize($input['judul']),
            ':kategori' => sanitize($input['kategori']),
            ':lokasi' => sanitize($input['lokasi']),
            ':deskripsi' => sanitize($input['deskripsi'])
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Report submitted successfully',
            'reportId' => $reportId
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to submit report: ' . $e->getMessage()]);
    }
}
?>
