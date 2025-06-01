<?php
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
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

        $users = [
            'admin' => ['password' => 'admin123', 'role' => 'admin'],
            'user' => ['password' => 'user123', 'role' => 'user']
        ];
        
        if (isset($users[$username]) && $users[$username]['password'] === $password) {

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

        $required = ['username', 'password', 'email', 'nama'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                jsonResponse(['error' => "Field $field is required"], 400);
            }
        }

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
?>
