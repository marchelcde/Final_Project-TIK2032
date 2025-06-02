<?php
/**
 * Main Logout Handler
 * Location: c:\laragon\www\Final_Project-TIK2032\logout.php
 * 
 * Handles logout for both admin and user roles with comprehensive session cleanup
 */

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Prevent caching
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

/**
 * Comprehensive session cleanup function
 */
function performLogout() {
    try {
        // Store user info for logging before destroying session
        $userId = $_SESSION['user_id'] ?? 'unknown';
        $userRole = $_SESSION['user_role'] ?? 'unknown';
        $loginTime = $_SESSION['login_time'] ?? 'unknown';
        
        // Log the logout activity
        error_log("User logout - ID: {$userId}, Role: {$userRole}, Login Time: {$loginTime}, Logout Time: " . date('Y-m-d H:i:s'));
        
        // Unset all specific session variables
        $sessionVars = [
            'user_id',
            'user_username', 
            'user_email',
            'user_fullname',
            'user_role',
            'user_logged_in',
            'login_time',
            'last_activity',
            'username',
            'name',
            'email',
            'role'
        ];
        
        foreach ($sessionVars as $var) {
            if (isset($_SESSION[$var])) {
                unset($_SESSION[$var]);
            }
        }
        
        // Clear session array completely
        $_SESSION = array();
        
        // Delete session cookie if it exists
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
        
        // Start a new session and regenerate ID for security
        session_start();
        session_regenerate_id(true);
        
        // Set a success message for the new session
        $_SESSION['logout_message'] = "Anda telah berhasil logout.";
        $_SESSION['logout_time'] = date('Y-m-d H:i:s');
        
        return true;
        
    } catch (Exception $e) {
        error_log("Logout error: " . $e->getMessage());
        return false;
    }
}

// Check if this is an AJAX request
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Check if user is already logged out
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Pengguna sudah logout',
            'already_logged_out' => true
        ]);
        exit();
    }
    // Redirect to home page if already logged out
    header("Location: index.php?status=already_logged_out");
    exit();
}

// Perform logout
$logoutSuccess = performLogout();

// Handle response based on request type
if ($isAjax) {
    header('Content-Type: application/json');
    if ($logoutSuccess) {
        echo json_encode([
            'success' => true,
            'message' => 'Logout berhasil',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Terjadi kesalahan saat logout'
        ]);
    }
    exit();
}

// For regular browser requests, redirect to home page
if ($logoutSuccess) {
    header("Location: index.php?logout=success");
} else {
    header("Location: index.php?logout=error");
}
exit();
?>
