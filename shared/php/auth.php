<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'config.php'; // Ensure this path is correct relative to auth.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST'); // Allow POST for login/logout/register/change_password
header('Access-Control-Allow-Headers: Content-Type');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($method) {
    case 'POST':
        if ($action === 'login') {
            handleLogin();
        } elseif ($action === 'logout') {
            handleLogout();
        } elseif ($action === 'register') {
            handleRegister();
        } elseif ($action === 'change_password') { // ADD THIS NEW CASE
            handleChangePassword();
        }
        break;
    
    case 'GET':
        if ($action === 'check') {
            checkSession();
        }
        break;
    
    default:
        jsonResponse(['error' => 'Method not allowed'], 405);
}



function handleLogin() {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['username']) || empty($data['password'])) {
            jsonResponse(['error' => 'Username and password are required'], 400);
        }
        
        $username = sanitize($data['username']);
        $password = $data['password'];
        
        // Simple authentication (in production, use proper password hashing)
        $users = [
            'admin' => ['password' => 'admin123', 'role' => 'admin'],
            'user' => ['password' => 'user123', 'role' => 'user']
        ];
        
        if (isset($users[$username]) && $users[$username]['password'] === $password) {
            // Set session
            $_SESSION['user_id'] = $username;
            $_SESSION['user_role'] = $users[$username]['role'];
            $_SESSION['login_time'] = time();
            
            jsonResponse([
                'success' => true,
                'message' => 'Login successful',
                'user' => [
                    'username' => $username,
                    'role' => $users[$username]['role']
                ]
            ]);
        } else {
            jsonResponse(['error' => 'Invalid username or password'], 401);
        }
        
    } catch (Exception $e) {
        jsonResponse(['error' => $e->getMessage()], 500);
    }
}

function handleLogout() {
    try {
        // Clear session
        session_unset();
        session_destroy();
        
        jsonResponse([
            'success' => true,
            'message' => 'Logout successful'
        ]);
        
    } catch (Exception $e) {
        jsonResponse(['error' => $e->getMessage()], 500);
    }
}

function handleRegister() {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        $required = ['username', 'password', 'email', 'nama'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                jsonResponse(['error' => "Field $field is required"], 400);
            }
        }
        
        // In a real application, you would:
        // 1. Check if username/email already exists
        // 2. Hash the password
        // 3. Store in database
        
        // For demo purposes, just return success
        jsonResponse([
            'success' => true,
            'message' => 'Registration successful'
        ]);
        
    } catch (Exception $e) {
        jsonResponse(['error' => $e->getMessage()], 500);
    }
}

function checkSession() {
    try {
        if (isLoggedIn()) {
            jsonResponse([
                'success' => true,
                'logged_in' => true,
                'user' => [
                    'username' => $_SESSION['user_id'],
                    'role' => $_SESSION['user_role']
                ]
            ]);
        } else {
            jsonResponse([
                'success' => true,
                'logged_in' => false
            ]);
        }
        
    } catch (Exception $e) {
        jsonResponse(['error' => $e->getMessage()], 500);
    }
}

// ADD THE FOLLOWING FUNCTION:
function handleChangePassword() {
    // Check if user is logged in using the helper from config.php
    if (!isLoggedIn()) {
        jsonResponse(['error' => 'Akses ditolak. Silakan login terlebih dahulu.'], 403);
        return;
    }

    // Get input data (from JSON body or POST)
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST; // Fallback for x-www-form-urlencoded
    }

    // Get user ID from session
    $userId = $_SESSION['user_id'] ?? '';
    if (empty($userId)) {
        jsonResponse(['error' => 'ID Pengguna tidak ditemukan di sesi.'], 400);
        return;
    }

    // Get old and new passwords from input
    $oldPassword = $input['old_password'] ?? '';
    $newPassword = $input['new_password'] ?? '';

    // Validate input
    if (empty($oldPassword) || empty($newPassword)) {
        jsonResponse(['error' => 'Kata sandi lama dan kata sandi baru diperlukan.'], 400);
        return;
    }

    if (strlen($newPassword) < 6) { // Consistent with register_handler.php for minimum password length
        jsonResponse(['error' => 'Kata sandi baru minimal 6 karakter.'], 400);
        return;
    }
    // You can add more password complexity rules here if desired (e.g., special characters, uppercase, numbers)

    $database = new Database();
    $conn = $database->getConnection();

    try {
        // 1. Fetch the current hashed password for the logged-in user from the 'users' table.
        // Both regular users and admins are stored in the 'users' table with a 'role' column.
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = :id AND status = 'active'");
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            jsonResponse(['error' => 'Pengguna tidak ditemukan atau tidak aktif.'], 404);
            return;
        }

        // 2. Verify the old password against the stored hashed password.
        if (!password_verify($oldPassword, $user['password'])) {
            jsonResponse(['error' => 'Kata sandi lama salah.'], 401);
            return;
        }

        // 3. Hash the new password before storing it.
        $hashedNewPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        // 4. Update the password in the database.
        $stmt = $conn->prepare("UPDATE users SET password = :new_password, updated_at = NOW() WHERE id = :id");
        $stmt->execute([
            ':new_password' => $hashedNewPassword,
            ':id' => $userId
        ]);

        if ($stmt->rowCount() > 0) {
            jsonResponse(['success' => true, 'message' => 'Kata sandi berhasil diubah.']);
        } else {
            // This might happen if the new password is the same as the old one (after hashing)
            // or if there's a database issue.
            jsonResponse(['error' => 'Gagal mengubah kata sandi. Pastikan kata sandi baru berbeda dari yang lama.'], 500);
        }

    } catch (Exception $e) {
        // Catch any database or other exceptions
        jsonResponse(['error' => 'Terjadi kesalahan server saat mengubah kata sandi: ' . $e->getMessage()], 500);
    }
}
?>
