<?php
// shared/php/login_handler.php
// Location: FINAL_PROJECT-TIK2032/shared/php/login_handler.php

require_once 'config.php'; // Includes session_start() and Database class, etc.

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Handle user login
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    loginUser();
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}

function loginUser() {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            $input = $_POST; // Fallback for x-www-form-urlencoded
        }
        
        if (empty($input['username']) || empty($input['password'])) {
            jsonResponse(['error' => 'Username and password are required'], 400);
        }
        
        $username_input = sanitize($input['username']);
        $password_input = $input['password'];
        
        $database = new Database();
        $conn = $database->getConnection();
        
        // --- Attempt to login as a REGULAR USER first (users table) ---
        // Retrieve id, username, fullName, email, password, role from users table
        $stmt_user = $conn->prepare("SELECT id, username, password, fullName, email, role FROM users WHERE (username = :username OR email = :username) AND status = 'active'");
        $stmt_user->execute([':username' => $username_input]);
        $user = $stmt_user->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Verify password (users table uses hashed passwords)
            if (password_verify($password_input, $user['password'])) {
                session_regenerate_id();
                $_SESSION['user_logged_in'] = true;
                $_SESSION['user_role'] = $user['role']; // 'user'
                $_SESSION['user_id'] = $user['id']; // Store actual user ID from DB
                $_SESSION['user_username'] = $user['username'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_fullname'] = $user['fullName'];
                
                jsonResponse([
                    'success' => true,
                    'message' => 'Login successful',
                    'user' => [
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'fullName' => $user['fullName'],
                        'role' => $user['role'],
                        'email' => $user['email']
                    ]
                ]);
                return;
            }
        }
        
        // --- If not found/authenticated as regular user, attempt to login as ADMIN (users table with 'admin' role) ---
        // Note: Your DB structure has 'admin' as a role in the 'users' table, not a separate 'pengguna_admin'.
        // So, we'll check the 'users' table again, specifically for the 'admin' role.
        
        // If the above query found a user but password didn't match, or user not found, continue to check admin.
        // It's more efficient to check for the admin role directly in the 'users' table if admins are just users with a specific role.
        // If 'pengguna_admin' is a separate table, then this section should query 'pengguna_admin'.
        // Based on database.sql, 'admin' is a role in the 'users' table.
        
        // Re-query for admin role specifically if not authenticated as regular user
        $stmt_admin = $conn->prepare("SELECT id, username, password, fullName, email, role FROM users WHERE (username = :username OR email = :username) AND role = 'admin' AND status = 'active'");
        $stmt_admin->execute([':username' => $username_input]);
        $admin = $stmt_admin->fetch(PDO::FETCH_ASSOC);

        if ($admin) {
            // Admin password verification (using hashed password from users table for admin role)
            // If you want plain-text for admin, change password_verify to direct comparison (INSECURE)
            if (password_verify($password_input, $admin['password'])) { 
                session_regenerate_id();
                $_SESSION['user_logged_in'] = true;
                $_SESSION['user_role'] = 'admin'; // 'admin'
                $_SESSION['user_id'] = $admin['id']; // Store actual admin ID from DB
                $_SESSION['user_username'] = $admin['username'];
                $_SESSION['user_email'] = $admin['email'];
                $_SESSION['user_fullname'] = $admin['fullName'];
                
                jsonResponse([
                    'success' => true,
                    'message' => 'Login successful',
                    'user' => [
                        'id' => $admin['id'],
                        'username' => $admin['username'],
                        'fullName' => $admin['fullName'],
                        'role' => $admin['role'],
                        'email' => $admin['email']
                    ]
                ]);
                return;
            }
        }
        
        // If neither user nor admin authenticated
        jsonResponse(['error' => 'Invalid username or password'], 401);
        
    } catch (Exception $e) {
        jsonResponse(['error' => 'Login failed: ' . $e->getMessage()], 500);
    }
}
?>
