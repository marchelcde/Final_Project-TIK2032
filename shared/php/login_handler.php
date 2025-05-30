<?php
// Login Handler for Database Users
require_once 'config.php';

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
        // Get input data
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            // Try to get from POST data
            $input = $_POST;
        }
        
        // Validate required fields
        if (empty($input['username']) || empty($input['password'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Username and password are required']);
            return;
        }
        
        $username = sanitize($input['username']);
        $password = $input['password'];
        
        // Check default admin credentials first
        if ($username === 'admin' && $password === 'admin123') {
            // Set session for admin
            $_SESSION['user_logged_in'] = true;
            $_SESSION['user_role'] = 'admin';
            $_SESSION['user_id'] = 'admin';
            $_SESSION['user_email'] = 'admin@gmail.com';
            $_SESSION['user_fullname'] = 'Administrator';
            
            echo json_encode([
                'success' => true,
                'message' => 'Login successful',
                'user' => [
                    'username' => 'admin',
                    'fullName' => 'Administrator',
                    'role' => 'admin',
                    'email' => 'admin@gmail.com'
                ]
            ]);
            return;
        }
        
        // Check default user credentials
        if ($username === 'user' && $password === 'user123') {
            // Set session for default user
            $_SESSION['user_logged_in'] = true;
            $_SESSION['user_role'] = 'user';
            $_SESSION['user_id'] = 'user';
            $_SESSION['user_email'] = 'user@gmail.com';
            $_SESSION['user_fullname'] = 'Demo User';
            
            echo json_encode([
                'success' => true,
                'message' => 'Login successful',
                'user' => [
                    'username' => 'user',
                    'fullName' => 'Demo User',
                    'role' => 'user',
                    'email' => 'user@gmail.com'
                ]
            ]);
            return;
        }
        
        // Check database for registered users
        $database = new Database();
        $conn = $database->getConnection();
        
        // Look for user by username or email
        $stmt = $conn->prepare("SELECT * FROM users WHERE (username = :username OR email = :username) AND status = 'active'");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            // Set session for registered user
            $_SESSION['user_logged_in'] = true;
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_id'] = $user['username'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_fullname'] = $user['fullName'];
            
            echo json_encode([
                'success' => true,
                'message' => 'Login successful',
                'user' => [
                    'username' => $user['username'],
                    'fullName' => $user['fullName'],
                    'role' => $user['role'],
                    'email' => $user['email']
                ]
            ]);
        } else {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid username or password']);
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Login failed: ' . $e->getMessage()]);
    }
}
?>
