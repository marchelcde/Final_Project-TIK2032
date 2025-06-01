<?php
// proses_login.php
// Location: FINAL_PROJECT-TIK2032/proses_login.php

// Ensure session is started if this file is accessed directly (e.g., if form action is not relative)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include the database connection file
require_once 'config/database.php'; // Path from root to config/

// Check if the form was submitted using POST method
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username_input = trim($_POST['username_email']);
    $password_input = $_POST['password'];

    // --- Attempt to login as a REGULAR USER first (STILL USES HASHED PASSWORD) ---
    $stmt_user = $conn->prepare("SELECT id, name, username, email, password FROM users WHERE username = ? OR email = ?");
    $stmt_user->bind_param("ss", $username_input, $username_input);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();

    if ($result_user->num_rows == 1) {
        $user = $result_user->fetch_assoc();
        // User password verification remains secure (hashed)
        if (password_verify($password_input, $user['password'])) {
            // User login successful
            session_regenerate_id();
            $_SESSION['loggedin'] = true;
            $_SESSION['role'] = 'user'; // Set role to 'user'
            $_SESSION['id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            
            $stmt_user->close();
            $conn->close();
            header("location: user/index.php"); // Redirect to main index (now at root)
            exit();
        }
    }
    $stmt_user->close(); // Close statement even if user not found or password incorrect

    // --- If not a regular user, attempt to login as an ADMIN (USES PLAIN TEXT PASSWORD) ---
    // IMPORTANT: Ensure 'full_name' is selected here
    $stmt_admin = $conn->prepare("SELECT id_admin, username, password, full_name FROM pengguna_admin WHERE username = ?");
    $stmt_admin->bind_param("s", $username_input);
    $stmt_admin->execute();
    $result_admin = $stmt_admin->get_result();

    if ($result_admin->num_rows == 1) {
        $admin = $result_admin->fetch_assoc();
        // ADMIN password verification is now INSECURE (plain text comparison)
        if ($password_input === $admin['password']) { // Changed from password_verify() to direct comparison
            // Admin login successful
            session_regenerate_id();
            $_SESSION['loggedin'] = true;
            $_SESSION['role'] = 'admin'; // Set role to 'admin'
            $_SESSION['admin_id'] = $admin['id_admin']; // Keep admin-specific ID
            $_SESSION['admin_username'] = $admin['username']; // Keep admin-specific username
            $_SESSION['admin_full_name'] = $admin['full_name']; // Store admin's full name from DB
            
            $stmt_admin->close();
            $conn->close();
            header("location: admin/dashboard.php"); // Redirect to admin dashboard (from root to admin/)
            exit();
        }
    }
    $stmt_admin->close(); // Close statement even if admin not found or password incorrect

    // --- If credentials didn't match either user or admin ---
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['errors'] = ["Username atau Email atau Password salah."];
    $_SESSION['form_data']['username_email'] = $username_input;
    
    $conn->close();
    header("location: login.php"); // Redirect back to login page (now at root)
    exit();

} else {
    // If accessed directly without POST request, redirect to login page
    header("location: login.php");
    exit();
}
?>
