<?php
// User-specific Operations Handler
// Location: FINAL_PROJECT-TIK2032/user/php/user_handler.php

require_once 'config.php'; // Includes session_start() and Database class, etc.

header('Content-Type: application/json');

// Check if user is logged in (using the config's isLoggedIn function)
function checkUserAccess() {
    if (!isLoggedIn()) { // Use isLoggedIn from config.php
        http_response_code(403);
        echo json_encode(['error' => 'Akses ditolak. Silakan login terlebih dahulu.']); // Updated error message
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
    
    case 'submit_report': // This action is for creating a new report by a logged-in user
        submitReport();
        break;
    
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Aksi tidak valid']); // Updated error message
        break;
}

function getUserReports() {
    checkUserAccess(); // Ensure user is logged in

    try {
        // Use $_SESSION['user_id'] to filter reports
        $userId = $_SESSION['user_id'] ?? '';
        
        if (empty($userId)) {
            http_response_code(400);
            echo json_encode(['error' => 'ID Pengguna tidak ditemukan di sesi']); // Updated error message
            exit; // Exit here to prevent further execution without user ID
        }
        
        $database = new Database();
        $conn = $database->getConnection();
        
        $status = $_GET['status'] ?? '';
        $search = $_GET['search'] ?? '';
        
        // Select foto_bukti and feedback_admin as well for display
        $query = "SELECT id, judul, kategori, lokasi, deskripsi, status, created_at, feedback_admin, foto_bukti FROM reports WHERE user_id = :user_id";
        $params = [':user_id' => $userId];
        
        if (!empty($status)) {
            $query .= " AND status = :status";
            $params[':status'] = $status;
        }
        
        if (!empty($search)) {
            $query .= " AND (judul LIKE :search OR deskripsi LIKE :search OR lokasi LIKE :search)";
            $params[':search'] = '%' . $search . '%';
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
            // Encode foto_bukti to base64 if it exists for display in HTML
            if (!empty($report['foto_bukti'])) {
                $report['foto_bukti_base64'] = base64_encode($report['foto_bukti']);
            } else {
                $report['foto_bukti_base64'] = null;
            }
            // Remove the raw BLOB data from the JSON response to avoid issues
            unset($report['foto_bukti']);
        }
        $jsonData = json_encode(['reports' => $reports]);
    if (json_last_error() !== JSON_ERROR_NONE) {
    error_log('JSON Encode Error in getUserReports: ' . json_last_error_msg());
    }
        jsonResponse(['reports' => $reports]);
    } catch (Exception $e) {
        http_response_code(500);
        jsonResponse(['error' => 'Gagal mengambil laporan pengguna: ' . $e->getMessage()]); // Updated error message
    }
}

function getUserStats() {
    checkUserAccess();
    
    try {
        $userId = $_SESSION['user_id'] ?? '';
        
        if (empty($userId)) {
            http_response_code(400);
            jsonResponse(['error' => 'ID Pengguna tidak ditemukan di sesi']);
            exit;
        }
        
        $database = new Database();
        $conn = $database->getConnection();
        
        // Get total reports count for user
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM reports WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $userId]);
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get pending reports count for user
        $stmt = $conn->prepare("SELECT COUNT(*) as pending FROM reports WHERE user_id = :user_id AND status = 'pending'");
        $stmt->execute([':user_id' => $userId]);
        $pending = $stmt->fetch(PDO::FETCH_ASSOC)['pending'];
        
        // Get in progress reports count for user
        $stmt = $conn->prepare("SELECT COUNT(*) as in_progress FROM reports WHERE user_id = :user_id AND status = 'in_progress'");
        $stmt->execute([':user_id' => $userId]);
        $inProgress = $stmt->fetch(PDO::FETCH_ASSOC)['in_progress'];
        
        // Get completed reports count for user
        $stmt = $conn->prepare("SELECT COUNT(*) as completed FROM reports WHERE user_id = :user_id AND status = 'completed'");
        $stmt->execute([':user_id' => $userId]);
        $completed = $stmt->fetch(PDO::FETCH_ASSOC)['completed'];

        // ADD THIS QUERY FOR REJECTED REPORTS
        $stmt = $conn->prepare("SELECT COUNT(*) as rejected FROM reports WHERE user_id = :user_id AND status = 'rejected'");
        $stmt->execute([':user_id' => $userId]);
        $rejected = $stmt->fetch(PDO::FETCH_ASSOC)['rejected'];
        
        jsonResponse([
            'total' => $total,
            'pending' => $pending,
            'in_progress' => $inProgress,
            'completed' => $completed,
            'rejected' => $rejected // ADD THIS TO THE RESPONSE
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        jsonResponse(['error' => 'Gagal mengambil statistik pengguna: ' . $e->getMessage()]);
    }
}

function deleteUserReport() {
    checkUserAccess(); // Ensure user is logged in
    
    try {
        $reportId = $_POST['reportId'] ?? '';
        $userId = $_SESSION['user_id'] ?? ''; // Get user ID from session
        
        if (empty($reportId) || empty($userId)) {
            http_response_code(400);
            jsonResponse(['error' => 'ID Laporan dan ID Pengguna diperlukan']); // Updated error message
            return;
        }
        
        $database = new Database();
        $conn = $database->getConnection();
        
        // Check if report belongs to user and is still pending
        $stmt = $conn->prepare("SELECT status FROM reports WHERE id = :id AND user_id = :user_id"); // Filter by user_id
        $stmt->execute([':id' => $reportId, ':user_id' => $userId]);
        $report = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$report) {
            http_response_code(404);
            jsonResponse(['error' => 'Laporan tidak ditemukan atau akses ditolak']); // Updated error message
            return;
        }
        
        if ($report['status'] !== 'pending') {
            http_response_code(400);
            jsonResponse(['error' => 'Hanya laporan yang berstatus Menunggu yang dapat dihapus']); // Updated error message
            return;
        }
        
        // Delete related comments first (assuming 'report_comments' table)
        $stmt = $conn->prepare("DELETE FROM report_comments WHERE report_id = :id"); // Assuming report_comments table name
        $stmt->execute([':id' => $reportId]);
        
        // Delete the report
        $stmt = $conn->prepare("DELETE FROM reports WHERE id = :id AND user_id = :user_id"); // Filter by user_id
        $stmt->execute([':id' => $reportId, ':user_id' => $userId]);
        
        if ($stmt->rowCount() > 0) {
            jsonResponse(['success' => true, 'message' => 'Laporan berhasil dihapus']); // Updated message
        } else {
            http_response_code(404);
            jsonResponse(['error' => 'Laporan tidak ditemukan atau tidak dimiliki oleh pengguna']); // Updated error message
        }
    } catch (Exception $e) {
        http_response_code(500);
        jsonResponse(['error' => 'Gagal menghapus laporan: ' . $e->getMessage()]); // Updated error message
    }
}

function getReportDetail() {
    checkUserAccess(); // Ensure user is logged in
    
    try {
        $reportId = $_GET['reportId'] ?? '';
        $userId = $_SESSION['user_id'] ?? ''; // Get user ID from session

        if (empty($reportId) || empty($userId)) {
            jsonResponse(['error' => 'ID Laporan dan ID Pengguna diperlukan'], 400); // Updated error message
            return;
        }
        
        $database = new Database();
        $conn = $database->getConnection();
        
        // Get report details - user can only view their own reports
        // Select feedback_admin and foto_bukti as well
        $stmt = $conn->prepare("SELECT id, judul, kategori, lokasi, deskripsi, status, created_at, feedback_admin, foto_bukti FROM reports WHERE id = :id AND user_id = :user_id"); // Filter by user_id
        $stmt->execute([':id' => $reportId, ':user_id' => $userId]);
        $report = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$report) {
            jsonResponse(['error' => 'Laporan tidak ditemukan atau akses ditolak'], 404); // Updated error message
            return;
        }
        
        // Get comments for the report (assuming report_comments table name)
        $stmt_comments = $conn->prepare("SELECT comment, created_at FROM report_comments WHERE report_id = :id ORDER BY created_at ASC");
        $stmt_comments->execute([':id' => $reportId]);
        $comments = $stmt_comments->fetchAll(PDO::FETCH_ASSOC);
        
        // Format data (using functions from config.php)
        $report['status_text'] = getStatusText($report['status']);
        $report['category_text'] = getCategoryText($report['kategori']);
        $report['formatted_date'] = formatDate($report['created_at']);
        // Encode foto_bukti to base64 if it exists for display in HTML
        if (!empty($report['foto_bukti'])) {
            $report['foto_bukti_base64'] = base64_encode($report['foto_bukti']);
        } else {
            $report['foto_bukti_base64'] = null;
        }
        // Remove the raw BLOB data from the JSON response to avoid issues
        unset($report['foto_bukti']);

        foreach ($comments as &$comment) {
            $comment['formatted_date'] = formatDate($comment['created_at']);
        }
        
        jsonResponse([
            'report' => $report,
            'comments' => $comments
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        jsonResponse(['error' => 'Gagal mengambil detail laporan: ' . $e->getMessage()]); // Updated error message
    }
}

// This function will be called when a user submits a report from the form
function submitReport() {
    checkUserAccess(); // Ensure user is logged in

    try {
        // Receive data either from JSON body (for AJAX) or $_POST (for standard form)
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $input = $_POST;
        }
        
        // Get user_id from session (crucial for linking report to user)
        $userId = $_SESSION['user_id'] ?? '';
        if (empty($userId)) {
            jsonResponse(['error' => 'ID Pengguna tidak ditemukan di sesi. Silakan login kembali.'], 401); // Updated error message
            return;
        }

        $database = new Database();
        $conn = $database->getConnection();
        
        // Validate required fields
        $required_fields = ['judul', 'kategori', 'lokasi', 'deskripsi'];
        foreach ($required_fields as $field) {
            if (empty($input[$field])) {
                jsonResponse(['error' => "Field '$field' is required"], 400); // Updated error message
                return;
            }
        }
        
        // Get user's name and email from session for the report (more reliable)
        $reporterName = $_SESSION['user_fullname'] ?? $_SESSION['user_username'] ?? '';
        $reporterEmail = $_SESSION['user_email'] ?? '';
        $reporterPhone = $_SESSION['user_phone'] ?? ''; // Assuming you store phone in session, or add it to users table

        // --- START: Added foto_bukti handling for BLOB ---
        $fotoBuktiData = null;
        if (isset($_FILES['foto_bukti']) && $_FILES['foto_bukti']['error'] === UPLOAD_ERR_OK) {
            // Read the binary content of the uploaded file
            $fotoBuktiData = file_get_contents($_FILES['foto_bukti']['tmp_name']);
            if ($fotoBuktiData === false) {
                jsonResponse(['error' => 'Gagal membaca konten foto bukti yang diunggah.'], 500);
                return;
            }
        }
        // --- END: Added foto_bukti handling ---

        // Generate report ID using the database function (if implemented) or PHP
        $reportId = generateId('RPT'); // Using generateId from config.php

        // CORRECTED INSERT statement to include foto_bukti
        $stmt = $conn->prepare("
            INSERT INTO reports (id, user_id, nama, email, telepon, judul, kategori, lokasi, deskripsi, foto_bukti)
            VALUES (:id, :user_id, :nama, :email, :telepon, :judul, :kategori, :lokasi, :deskripsi, :foto_bukti)
        ");
        
        $stmt->execute([
            ':id' => $reportId,
            ':user_id' => $userId, // Link report to user
            ':nama' => sanitize($reporterName),
            ':email' => sanitize($reporterEmail),
            ':telepon' => sanitize($reporterPhone),
            ':judul' => sanitize($input['judul']),
            ':kategori' => sanitize($input['kategori']),
            ':lokasi' => sanitize($input['lokasi']),
            ':deskripsi' => sanitize($input['deskripsi']),
            ':foto_bukti' => $fotoBuktiData // Bind the BLOB data
        ]);
        
        jsonResponse([
            'success' => true,
            'message' => 'Laporan berhasil dibuat', // Updated message
            'reportId' => $reportId
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        jsonResponse(['error' => 'Gagal mengirim laporan: ' . $e->getMessage()]); // Updated error message
    }
}
?>