<?php
/**
 * Admin Logout Handler
 * Handles admin logout requests with comprehensive session cleanup
 * Similar implementation to user logout for consistency
 */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

header('Content-Type: application/json');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type');

function destroyAdminSession() {
    try {
        $userId = $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? 'unknown';
        $userRole = $_SESSION['user_role'] ?? $_SESSION['admin_role'] ?? 'admin';
        $username = $_SESSION['user_username'] ?? $_SESSION['admin_username'] ?? 'admin';
        
        // List of session variables to clear (both user and admin variants)
        $sessionVars = [
            'user_logged_in',
            'user_id', 
            'user_username',
            'user_email',
            'user_fullname',
            'user_role',
            'admin_logged_in',
            'admin_id',
            'admin_username', 
            'admin_email',
            'admin_fullname',
            'admin_role',
            'login_time',
            'last_activity',
            'current_user',
            'current_admin'
        ];
        
        // Clear specific session variables
        foreach ($sessionVars as $var) {
            if (isset($_SESSION[$var])) {
                unset($_SESSION[$var]);
            }
        }
        
        // Clear entire session array
        $_SESSION = array();
        
        // Destroy session cookie if it exists
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(), 
                '', 
                time() - 42000,
                $params["path"], 
                $params["domain"],
                $params["secure"], 
                $params["httponly"]
            );
        }
        
        // Destroy the session
        session_destroy();
        
        // Start new session for logout message
        session_start();
        session_regenerate_id(true);
        
        // Set logout confirmation message
        $_SESSION['logout_message'] = "Admin telah berhasil logout.";
        $_SESSION['logout_time'] = date('Y-m-d H:i:s');
        
        // Log successful logout
        error_log("Admin logout successful - User: {$username}, Role: {$userRole}, Time: " . date('Y-m-d H:i:s'));
        
        return [
            'success' => true,
            'message' => 'Admin logout berhasil',
            'user_id' => $userId,
            'username' => $username,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
    } catch (Exception $e) {
        error_log("Admin logout error: " . $e->getMessage());
        return [
            'success' => false,
            'error' => 'Terjadi kesalahan saat admin logout: ' . $e->getMessage(),
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
}

function handleAdminLogoutRequest() {
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'POST' || $method === 'GET') {
        // Check if user is already logged out
        $isLoggedIn = $_SESSION['user_logged_in'] ?? $_SESSION['admin_logged_in'] ?? false;
        $userRole = $_SESSION['user_role'] ?? $_SESSION['admin_role'] ?? null;
        
        if (!$isLoggedIn || ($userRole !== 'admin' && $userRole !== 'administrator')) {
            echo json_encode([
                'success' => true,
                'message' => 'Admin sudah logout atau tidak memiliki akses admin',
                'already_logged_out' => true,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            return;
        }
        
        // Perform logout
        $result = destroyAdminSession();
        
        // Check if this is an AJAX request
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            echo json_encode($result);
            return;
        }
        
        // Handle non-AJAX requests with redirect
        if ($result['success']) {
            header("Location: ../index.php?logout=success&admin=true");
            exit();
        } else {
            header("Location: ../index.php?logout=error&admin=true");
            exit();
        }
        
    } else {
        // Method not allowed
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'error' => 'Method not allowed',
            'message' => 'Only POST and GET requests are allowed',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
}

function checkAdminReferrer() {
    $allowedDomains = [
        'localhost',
        '127.0.0.1',
        $_SERVER['SERVER_NAME'] ?? 'localhost'
    ];
    
    if (isset($_SERVER['HTTP_REFERER'])) {
        $refererHost = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
        if (!in_array($refererHost, $allowedDomains)) {
            error_log("Suspicious admin logout attempt from: " . $_SERVER['HTTP_REFERER']);
        }
    }
}

// Log logout attempt for security monitoring
error_log("Admin logout attempt from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . 
         " at " . date('Y-m-d H:i:s'));

// Check referrer for security
checkAdminReferrer();

// Handle the logout request
handleAdminLogoutRequest();
?>
