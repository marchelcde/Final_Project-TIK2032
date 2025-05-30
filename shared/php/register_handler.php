<?php
// Registration Handler
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Handle user registration
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    registerUser();
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}

function registerUser() {
    try {
        // Get input data
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            // Try to get from POST data
            $input = $_POST;
        }
        
        // Validate required fields
        $required = ['fullName', 'email', 'phone', 'address', 'nik', 'username', 'password'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                http_response_code(400);
                echo json_encode(['error' => "Field '$field' is required"]);
                return;
            }
        }
        
        // Validate email format
        if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid email format']);
            return;
        }
        
        // Validate NIK (16 digits)
        if (!preg_match('/^\d{16}$/', $input['nik'])) {
            http_response_code(400);
            echo json_encode(['error' => 'NIK must be 16 digits']);
            return;
        }
        
        // Validate password length
        if (strlen($input['password']) < 6) {
            http_response_code(400);
            echo json_encode(['error' => 'Password must be at least 6 characters']);
            return;
        }
        
        $database = new Database();
        $conn = $database->getConnection();
        
        // Check if username already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = :username");
        $stmt->execute([':username' => $input['username']]);
        if ($stmt->fetch()) {
            http_response_code(409);
            echo json_encode(['error' => 'Username already exists']);
            return;
        }
        
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->execute([':email' => $input['email']]);
        if ($stmt->fetch()) {
            http_response_code(409);
            echo json_encode(['error' => 'Email already registered']);
            return;
        }
        
        // Check if NIK already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE nik = :nik");
        $stmt->execute([':nik' => $input['nik']]);
        if ($stmt->fetch()) {
            http_response_code(409);
            echo json_encode(['error' => 'NIK already registered']);
            return;
        }
        
        // Hash password for security
        $hashedPassword = password_hash($input['password'], PASSWORD_DEFAULT);
        
        // Generate user ID
        $userId = 'USR' . time() . rand(100, 999);
        
        // Insert new user
        $stmt = $conn->prepare("
            INSERT INTO users (id, username, password, fullName, email, phone, address, nik, registeredDate, role, status) 
            VALUES (:id, :username, :password, :fullName, :email, :phone, :address, :nik, :registeredDate, :role, :status)
        ");
        
        $result = $stmt->execute([
            ':id' => $userId,
            ':username' => $input['username'],
            ':password' => $hashedPassword,
            ':fullName' => $input['fullName'],
            ':email' => $input['email'],
            ':phone' => $input['phone'],
            ':address' => $input['address'],
            ':nik' => $input['nik'],
            ':registeredDate' => date('Y-m-d H:i:s'),
            ':role' => 'user',
            ':status' => 'active'
        ]);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Registration successful',
                'userId' => $userId,
                'username' => $input['username']
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to register user']);
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Registration failed: ' . $e->getMessage()]);
    }
}
?>
