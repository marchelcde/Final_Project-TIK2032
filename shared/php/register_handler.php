<?php
// Registration Handler
// Location: FINAL_PROJECT-TIK2032/shared/php/register_handler.php

require_once 'config.php'; // Includes session_start() and Database class, etc.

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
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            $input = $_POST; // Fallback for x-www-form-urlencoded
        }
        
        // Validate required fields
        $required = ['fullName', 'email', 'phone', 'address', 'nik', 'username', 'password'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                jsonResponse(['error' => "Field '$field' is required"], 400);
            }
        }
        
        // Validate email format
        if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            jsonResponse(['error' => 'Invalid email format'], 400);
            return;
        }
        
        // Validate NIK (16 digits)
        if (!preg_match('/^\d{16}$/', $input['nik'])) {
            jsonResponse(['error' => 'NIK must be 16 digits'], 400);
            return;
        }
        
        // Validate password length
        if (strlen($input['password']) < 6) {
            jsonResponse(['error' => 'Password must be at least 6 characters'], 400);
            return;
        }
        
        $database = new Database();
        $conn = $database->getConnection();
        
        // Check if username already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = :username");
        $stmt->execute([':username' => $input['username']]);
        if ($stmt->fetch()) {
            jsonResponse(['error' => 'Username already exists'], 409);
            return;
        }
        
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->execute([':email' => $input['email']]);
        if ($stmt->fetch()) {
            jsonResponse(['error' => 'Email already registered'], 409);
            return;
        }
        
        // Check if NIK already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE nik = :nik");
        $stmt->execute([':nik' => $input['nik']]);
        if ($stmt->fetch()) {
            jsonResponse(['error' => 'NIK already registered'], 409);
            return;
        }
        
        // Hash password for security
        $hashedPassword = password_hash($input['password'], PASSWORD_DEFAULT);
        
        // Generate user ID using the database function (if applicable, or a PHP generateId)
        // Note: Your database.sql has a GenerateReportId function, but not a GenerateUserId function.
        // Let's use a PHP based ID generation for consistency with existing JS.
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
            ':registeredDate' => date('Y-m-d H:i:s'), // Current timestamp
            ':role' => 'user', // Default role for new registrations
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
