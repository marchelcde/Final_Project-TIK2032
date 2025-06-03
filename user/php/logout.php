<?php

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

function destroyUserSession() {
    try {
        $userId = $_SESSION['user_id'] ?? 'unknown';
        $userRole = $_SESSION['user_role'] ?? 'unknown';
        
        $sessionVars = [
            'user_logged_in',
            'user_id', 
            'user_username',
            'user_email',
            'user_fullname',
            'user_role',
            'login_time',
            'last_activity'
        ];
        
        foreach ($sessionVars as $var) {
            if (isset($_SESSION[$var])) {
                unset($_SESSION[$var]);
            }
        }
        
        $_SESSION = array();
        
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
        
        session_destroy();
        
        session_start();
        session_regenerate_id(true);
        
        $_SESSION['logout_message'] = "Anda telah berhasil logout.";
        $_SESSION['logout_time'] = date('Y-m-d H:i:s');
        
        return [
            'success' => true,
            'message' => 'Logout berhasil',
            'user_id' => $userId,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
    } catch (Exception $e) {
        error_log("Logout error: " . $e->getMessage());
        return [
            'success' => false,
            'error' => 'Terjadi kesalahan saat logout: ' . $e->getMessage()
        ];
    }
}

function handleLogoutRequest() {
    $method = $_SERVER['REQUEST_METHOD'];
    if ($method === 'POST' || $method === 'GET') {
        if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
            echo json_encode([
                'success' => true,
                'message' => 'Pengguna sudah logout',
                'already_logged_out' => true
            ]);
            return;
        }
        
        $result = destroyUserSession();
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            echo json_encode($result);
            return;
        }
        if ($result['success']) {
            header("Location: ../../index.php?logout=success");
            exit();
        } else {
            header("Location: ../../index.php?logout=error");
            exit();
        }
        
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
}
function checkReferrer() {
    $allowedDomains = [
        'localhost',
        '127.0.0.1',
        $_SERVER['SERVER_NAME'] ?? 'localhost'
    ];
    
    if (isset($_SERVER['HTTP_REFERER'])) {
        $refererHost = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
        if (!in_array($refererHost, $allowedDomains)) {
            error_log("Suspicious logout attempt from: " . $_SERVER['HTTP_REFERER']);
        }
    }
}
error_log("Logout attempt from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));

checkReferrer();

handleLogoutRequest();
?>