<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ensure config.php is correctly included. Adjust path if necessary.
// Assuming this file is at shared/php/ and config.php is also at shared/php/
require_once 'config.php'; 

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST'); // Only allow POST for this operation
header('Access-Control-Allow-Headers: Content-Type');

// This script will only handle the 'change_password' action.
// The action should be passed in the JSON body for POST requests.
$input = json_decode(file_get_contents('php://input'), true);

// If JSON decoding failed or input was empty, try $_POST (e.g., for x-www-form-urlencoded)
if (!$input) {
    $input = $_POST;
}

// Check if the 'action' is explicitly 'change_password' or proceed if no action specified (as this file's sole purpose is password change)
$action = $input['action'] ?? '';

if ($action === 'change_password' || empty($action)) { // Allow if action is change_password or if no action (assuming dedicated script)
    handleChangePassword($input);
} else {
    jsonResponse(['error' => 'Aksi tidak valid untuk update password.'], 400);
}

function handleChangePassword($input) {
    // Check if user is logged in using the helper from config.php
    if (!isLoggedIn()) { // isLoggedIn() is from config.php
        jsonResponse(['error' => 'Akses ditolak. Silakan login terlebih dahulu.'], 403);
        return;
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

    $database = new Database(); // Database class from config.php
    $conn = null; // Initialize $conn to null

    try {
        $conn = $database->getConnection(); // Get database connection

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
    } finally {
        // Ensure connection is closed if it was opened
        if ($conn) {
            $conn = null;
        }
    }
}
?>