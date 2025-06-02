<?php
error_reporting(E_ALL); 
ini_set('display_errors', 1); 


require_once 'config.php';

header('Content-Type: application/json');

function checkAdminAccess() {
    if (!isAdmin()) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied. Admin privileges required.']);
        exit;
    }
}

function getParsedInput() {
    $input = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE && empty($input)) {
        $input = $_POST;
    }
    return $input;
}

$action = $_GET['action'] ?? '';
if (empty($action)) { 
    $postInput = getParsedInput(); 
    $action = $postInput['action'] ?? ''; 
}


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
    
    case 'update_report_status_and_feedback':
        updateReportStatusAndFeedback();
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
        
    case 'add_new_user':
        addNewUser();
        break;
    
    case 'change_password':
        changeAdminPassword();
        break;
      case 'get_all_users':
        getAllUsers();
        break;
      case 'delete_user':
        deleteUser();
        break;
    
    case 'get_user_detail':
        getUserDetail();
        break;
      case 'update_user':
        updateUser();
        break;
    
    case 'update_admin_profile':
        updateAdminProfile();
        break;

    default:
        http_response_code(400);
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
        
        // Get in_progress reports count
        $stmt = $conn->query("SELECT COUNT(*) as in_progress FROM reports WHERE status = 'in_progress'");
        $in_progress = $stmt->fetch(PDO::FETCH_ASSOC)['in_progress'];
        
        // Get completed reports count
        $stmt = $conn->query("SELECT COUNT(*) as completed FROM reports WHERE status = 'completed'");
        $completed = $stmt->fetch(PDO::FETCH_ASSOC)['completed'];
        
        // Get rejected reports count
        $stmt = $conn->query("SELECT COUNT(*) as rejected FROM reports WHERE status = 'rejected'");
        $rejected = $stmt->fetch(PDO::FETCH_ASSOC)['rejected'];
        
        echo json_encode([
            'total' => $total,
            'pending' => $pending,
            'in_progress' => $in_progress,
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
        
        // Start transaction to handle potential trigger issues
        $conn->beginTransaction();
        
        try {
            // Ensure SYSTEM user exists before updating
            $systemUserCheck = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE id IN ('SYSTEM', 'ADM001')");
            $systemUserCheck->execute();
            $userCount = $systemUserCheck->fetch(PDO::FETCH_ASSOC)['count'];
            
            if ($userCount == 0) {
                // Create SYSTEM user if neither SYSTEM nor ADM001 exists
                $insertSystemUser = $conn->prepare("INSERT IGNORE INTO users (id, fullName, username, email, password, phone, address, nik, registeredDate, created_at, role, status) VALUES ('SYSTEM', 'System User', 'system', 'system@elapor.com', 'no_password_needed', '', 'System Generated', '', NOW(), NOW(), 'admin', 'active')");
                $insertSystemUser->execute();
            }
            
            // Update the report status
            $stmt = $conn->prepare("UPDATE reports SET status = :status, updated_at = NOW() WHERE id = :id");
            $stmt->execute([
                ':status' => $newStatus,
                ':id' => $reportId
            ]);
            
            if ($stmt->rowCount() > 0) {
                $conn->commit();
                echo json_encode(['success' => true, 'message' => 'Status laporan berhasil diperbarui']);
            } else {
                $conn->rollback();
                http_response_code(404);
                echo json_encode(['error' => 'Report not found']);
            }
            
        } catch (Exception $innerException) {
            $conn->rollback();
            
            // If it's a foreign key constraint error, try to fix it
            if (strpos($innerException->getMessage(), 'foreign key constraint') !== false) {
                // Try to create the missing user and retry
                try {
                    $conn->beginTransaction();
                    
                    $insertSystemUser = $conn->prepare("INSERT IGNORE INTO users (id, fullName, username, email, password, phone, address, nik, registeredDate, created_at, role, status) VALUES ('SYSTEM', 'System User', 'system', 'system@elapor.com', 'no_password_needed', '', 'System Generated', '', NOW(), NOW(), 'admin', 'active')");
                    $insertSystemUser->execute();
                    
                    $stmt = $conn->prepare("UPDATE reports SET status = :status, updated_at = NOW() WHERE id = :id");
                    $stmt->execute([
                        ':status' => $newStatus,
                        ':id' => $reportId
                    ]);
                    
                    if ($stmt->rowCount() > 0) {
                        $conn->commit();
                        echo json_encode(['success' => true, 'message' => 'Status laporan berhasil diperbarui (setelah perbaikan sistem)']);
                    } else {
                        $conn->rollback();
                        http_response_code(404);
                        echo json_encode(['error' => 'Report not found']);
                    }
                    
                } catch (Exception $retryException) {
                    $conn->rollback();
                    throw $retryException;
                }
            } else {
                throw $innerException;
            }
        }
          } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update report status: ' . $e->getMessage()]);
    }
}

function updateReportStatusAndFeedback() {
    checkAdminAccess();
    
    try {
        $input = getParsedInput();

        $reportId = $input['reportId'] ?? '';
        $newStatus = $input['status'] ?? '';
        $feedback = $input['feedback'] ?? '';
        
        if (empty($reportId) || empty($newStatus)) {
            http_response_code(400);
            echo json_encode(['error' => 'Report ID and status are required. Debug: reportId="' . $reportId . '", newStatus="' . $newStatus . '"']);
            return;
        }
        
        $validStatuses = ['pending', 'in_progress', 'completed', 'rejected'];
        if (!in_array($newStatus, $validStatuses)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid status: ' . $newStatus]);
            return;
        }
        
        $database = new Database();
        $conn = $database->getConnection();
        $conn->beginTransaction();
        
        try {
            // Update both status and feedback in a single query
            $stmt = $conn->prepare("UPDATE reports SET status = :status, feedback_admin = :feedback, updated_at = NOW() WHERE id = :id");
            $stmt->execute([
                ':status' => $newStatus,
                ':feedback' => sanitize($feedback),
                ':id' => $reportId
            ]);
            
            if ($stmt->rowCount() > 0) {
                $conn->commit();
                echo json_encode(['success' => true, 'message' => 'Status dan feedback laporan berhasil diperbarui']);
            } else {
                $conn->rollback();
                http_response_code(404);
                echo json_encode(['error' => 'Report not found']);
            }
            
        } catch (Exception $innerException) {
            $conn->rollback();
            throw $innerException;
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update report status and feedback: ' . $e->getMessage()]);
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
        
        // Get monthly reports for the current year - fixed GROUP BY
        $stmt = $conn->query("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count FROM reports WHERE YEAR(created_at) = YEAR(CURDATE()) GROUP BY DATE_FORMAT(created_at, '%Y-%m') ORDER BY month");
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
        
        // Include nama, email, lokasi fields to match getAllReports structure
        $stmt = $conn->prepare("SELECT id, user_id, nama, email, telepon, kategori, judul, deskripsi, lokasi, status, created_at, feedback_admin, foto_bukti FROM reports ORDER BY created_at DESC LIMIT :limit");
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format data (using functions from config.php) - match getAllReports formatting
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
        echo json_encode(['error' => 'Failed to fetch recent reports: ' . $e->getMessage()]);
    }
}

// Function to add a new user
function addNewUser() {
    checkAdminAccess();
    
    try {
        $input = getParsedInput();
        
        $fullName = $input['fullName'] ?? '';
        $username = $input['username'] ?? '';
        $email = $input['email'] ?? '';
        $phone = $input['phone'] ?? '';
        $password = $input['password'] ?? '';
        $role = $input['role'] ?? 'user';
        
        // Validation
        if (empty($fullName) || empty($username) || empty($email) || empty($password)) {
            jsonResponse(['error' => 'Nama lengkap, username, email, dan password harus diisi'], 400);
            return;
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonResponse(['error' => 'Format email tidak valid'], 400);
            return;
        }
        
        if (strlen($password) < 6) {
            jsonResponse(['error' => 'Password minimal 6 karakter'], 400);
            return;
        }
        
        $database = new Database();
        $conn = $database->getConnection();
        
        // Check if username or email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = :username OR email = :email");
        $stmt->execute([':username' => $username, ':email' => $email]);
        if ($stmt->rowCount() > 0) {
            jsonResponse(['error' => 'Username atau email sudah digunakan'], 400);
            return;
        }
        
        // Generate user ID
        $userId = 'USR' . time() . rand(100, 999);
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
          // Insert new user
        $stmt = $conn->prepare("INSERT INTO users (id, username, email, password, fullName, phone, role, created_at) VALUES (:id, :username, :email, :password, :fullName, :phone, :role, NOW())");
        $stmt->execute([
            ':id' => $userId,
            ':username' => $username,
            ':email' => $email,
            ':password' => $hashedPassword,
            ':fullName' => $fullName,
            ':phone' => $phone,
            ':role' => $role
        ]);
        
        jsonResponse(['success' => true, 'message' => 'Pengguna berhasil ditambahkan', 'userId' => $userId]);
        
    } catch (Exception $e) {
        jsonResponse(['error' => 'Gagal menambahkan pengguna: ' . $e->getMessage()], 500);
    }
}

// Function to change admin password
function changeAdminPassword() {
    checkAdminAccess();
    
    try {
        $input = getParsedInput();
        
        $currentPassword = $input['currentPassword'] ?? '';
        $newPassword = $input['newPassword'] ?? '';
        $confirmPassword = $input['confirmPassword'] ?? '';
        
        // Validation
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            jsonResponse(['error' => 'Semua field password harus diisi'], 400);
            return;
        }
        
        if ($newPassword !== $confirmPassword) {
            jsonResponse(['error' => 'Password baru dan konfirmasi password tidak cocok'], 400);
            return;
        }
        
        if (strlen($newPassword) < 6) {
            jsonResponse(['error' => 'Password baru minimal 6 karakter'], 400);
            return;
        }
        
        $database = new Database();
        $conn = $database->getConnection();
        
        // Get current admin user (assuming we have admin user info in session)
        $adminUsername = $_SESSION['username'] ?? 'admin'; // fallback to admin
        
        // Get current admin data
        $stmt = $conn->prepare("SELECT password FROM users WHERE username = :username AND role = 'admin'");
        $stmt->execute([':username' => $adminUsername]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$admin) {
            jsonResponse(['error' => 'Admin tidak ditemukan'], 404);
            return;
        }
        
        // Verify current password
        if (!password_verify($currentPassword, $admin['password'])) {
            jsonResponse(['error' => 'Password saat ini salah'], 400);
            return;
        }
        
        // Hash new password
        $hashedNewPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        // Update password
        $stmt = $conn->prepare("UPDATE users SET password = :password, updated_at = NOW() WHERE username = :username AND role = 'admin'");
        $stmt->execute([
            ':password' => $hashedNewPassword,
            ':username' => $adminUsername
        ]);
        
        jsonResponse(['success' => true, 'message' => 'Password berhasil diubah']);
        
    } catch (Exception $e) {
        jsonResponse(['error' => 'Gagal mengubah password: ' . $e->getMessage()], 500);
    }
}

// Function to get all users
function getAllUsers() {
    checkAdminAccess();
    
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        $stmt = $conn->prepare("SELECT id, username, email, fullName, phone, role, created_at FROM users ORDER BY created_at DESC");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format dates
        foreach ($users as &$user) {
            $user['formatted_date'] = formatDate($user['created_at']);
        }
        
        jsonResponse(['users' => $users]);
        
    } catch (Exception $e) {
        jsonResponse(['error' => 'Gagal mengambil data pengguna: ' . $e->getMessage()], 500);
    }
}

function deleteUser() {
    checkAdminAccess();
    
    try {
        $input = getParsedInput();
        $userId = $input['user_id'] ?? null;
        
        if (!$userId) {
            jsonResponse(['error' => 'User ID tidak ditemukan'], 400);
            return;
        }
        
        $database = new Database();
        $conn = $database->getConnection();
        
        // Check if user exists
        $stmt = $conn->prepare("SELECT id, username FROM users WHERE id = :user_id");
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            jsonResponse(['error' => 'Pengguna tidak ditemukan'], 404);
            return;
        }
        
        // Don't allow deleting admin users
        if ($user['username'] === 'admin') {
            jsonResponse(['error' => 'Admin utama tidak dapat dihapus'], 403);
            return;
        }
        
        // Delete user
        $stmt = $conn->prepare("DELETE FROM users WHERE id = :user_id");
        $stmt->bindParam(':user_id', $userId);
        
        if ($stmt->execute()) {
            jsonResponse([
                'success' => true,
                'message' => 'Pengguna berhasil dihapus',
                'deleted_user_id' => $userId
            ]);
        } else {
            jsonResponse(['error' => 'Gagal menghapus pengguna'], 500);
        }
        
    } catch (Exception $e) {
        jsonResponse(['error' => 'Gagal menghapus pengguna: ' . $e->getMessage()], 500);
    }
}

function getUserDetail() {
    checkAdminAccess();
    
    try {
        $userId = $_GET['user_id'] ?? null;
        
        if (!$userId) {
            jsonResponse(['error' => 'User ID tidak ditemukan'], 400);
            return;
        }
        
        $database = new Database();
        $conn = $database->getConnection();
        
        $stmt = $conn->prepare("SELECT id, username, email, fullName, phone, role, created_at FROM users WHERE id = :user_id");
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            jsonResponse(['error' => 'Pengguna tidak ditemukan'], 404);
            return;
        }
        
        jsonResponse(['user' => $user]);
        
    } catch (Exception $e) {
        jsonResponse(['error' => 'Gagal mengambil detail pengguna: ' . $e->getMessage()], 500);
    }
}

function updateUser() {
    checkAdminAccess();
    
    try {
        $input = getParsedInput();
        
        $userId = $input['user_id'] ?? null;
        $fullName = $input['fullName'] ?? '';
        $email = $input['email'] ?? '';
        $phone = $input['phone'] ?? '';
        $role = $input['role'] ?? 'user';
        
        if (!$userId) {
            jsonResponse(['error' => 'User ID tidak ditemukan'], 400);
            return;
        }
        
        // Validation
        if (empty($fullName) || empty($email)) {
            jsonResponse(['error' => 'Nama lengkap dan email harus diisi'], 400);
            return;
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonResponse(['error' => 'Format email tidak valid'], 400);
            return;
        }
        
        $database = new Database();
        $conn = $database->getConnection();
        
        // Check if user exists
        $stmt = $conn->prepare("SELECT id, username FROM users WHERE id = :user_id");
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            jsonResponse(['error' => 'Pengguna tidak ditemukan'], 404);
            return;
        }
        
        // Check if email is already used by another user
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email AND id != :user_id");
        $stmt->execute([':email' => $email, ':user_id' => $userId]);
        if ($stmt->rowCount() > 0) {
            jsonResponse(['error' => 'Email sudah digunakan oleh pengguna lain'], 400);
            return;
        }
        
        // Update user
        $stmt = $conn->prepare("UPDATE users SET fullName = :fullName, email = :email, phone = :phone, role = :role WHERE id = :user_id");
        $stmt->execute([
            ':fullName' => $fullName,
            ':email' => $email,
            ':phone' => $phone,
            ':role' => $role,
            ':user_id' => $userId
        ]);
        
        jsonResponse([
            'success' => true,
            'message' => 'Data pengguna berhasil diperbarui',
            'user_id' => $userId
        ]);
        
    } catch (Exception $e) {
        jsonResponse(['error' => 'Gagal memperbarui data pengguna: ' . $e->getMessage()], 500);
    }
}

function updateAdminProfile() {
    checkAdminAccess();
    
    try {
        $input = getParsedInput();
        
        $fullName = $input['fullName'] ?? '';
        $email = $input['email'] ?? '';
        
        // Validation
        if (empty($fullName) || empty($email)) {
            jsonResponse(['error' => 'Nama lengkap dan email harus diisi'], 400);
            return;
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonResponse(['error' => 'Format email tidak valid'], 400);
            return;
        }
        
        $database = new Database();
        $conn = $database->getConnection();
        
        // Get current admin user ID from session
        $adminId = $_SESSION['user_id'] ?? '';
        if (empty($adminId)) {
            jsonResponse(['error' => 'ID admin tidak ditemukan di sesi'], 400);
            return;
        }
        
        // Check if email is already used by another user (excluding current admin)
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email AND id != :admin_id");
        $stmt->execute([':email' => $email, ':admin_id' => $adminId]);
        if ($stmt->rowCount() > 0) {
            jsonResponse(['error' => 'Email sudah digunakan oleh pengguna lain'], 400);
            return;
        }
        
        // Update admin profile
        $stmt = $conn->prepare("UPDATE users SET fullName = :fullName, email = :email, updated_at = NOW() WHERE id = :admin_id AND role = 'admin'");
        $stmt->execute([
            ':fullName' => $fullName,
            ':email' => $email,
            ':admin_id' => $adminId
        ]);
        
        if ($stmt->rowCount() > 0) {
            jsonResponse([
                'success' => true,
                'message' => 'Profil admin berhasil diperbarui',
                'admin_id' => $adminId
            ]);
        } else {
            jsonResponse(['error' => 'Gagal memperbarui profil admin atau admin tidak ditemukan'], 404);
        }
        
    } catch (Exception $e) {
        jsonResponse(['error' => 'Gagal memperbarui profil admin: ' . $e->getMessage()], 500);
    }
}