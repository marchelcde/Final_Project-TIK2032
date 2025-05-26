<?php
// proses_register.php
// Location: FINAL_PROJECT-TIK2032/proses_register.php

// Ensure session is started if this file is accessed directly (e.g., if form action is not relative)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include the database connection file
require_once 'config/database.php'; // Path from root to config/

// Check if the form was submitted using POST method
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Initialize an array to store errors
    $errors = [];

    // Sanitize and validate input data
    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate Name
    if (empty($name)) {
        $errors[] = "Nama tidak boleh kosong.";
    } else if (strlen($name) < 3) {
        $errors[] = "Nama minimal 3 karakter.";
    }

    // Validate Username
    if (empty($username)) {
        $errors[] = "Username tidak boleh kosong.";
    } else if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = "Username hanya boleh berisi huruf, angka, dan underscore.";
    } else {
        // Check if username already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = "Username ini sudah terdaftar. Silakan gunakan username lain.";
        }
        $stmt->close();
    }

    // Validate Email
    if (empty($email)) {
        $errors[] = "Email tidak boleh kosong.";
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format email tidak valid.";
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = "Email ini sudah terdaftar. Silakan gunakan email lain.";
        }
        $stmt->close();
    }

    // Validate Password
    if (empty($password)) {
        $errors[] = "Password tidak boleh kosong.";
    } else if (strlen($password) < 8) {
        $errors[] = "Password minimal 8 karakter.";
    } else if (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password) || !preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = "Password harus berisi kombinasi huruf kapital, huruf kecil, angka, dan karakter khusus (@$!%*#?&).";
    }

    // Validate Confirm Password
    if (empty($confirm_password)) {
        $errors[] = "Konfirmasi password tidak boleh kosong.";
    } else if ($password !== $confirm_password) {
        $errors[] = "Konfirmasi password tidak cocok dengan password.";
    }

    // If there are no errors, proceed with registration
    if (empty($errors)) {
        // Hash the password for security
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Prepare an INSERT statement
        $stmt = $conn->prepare("INSERT INTO users (name, username, email, password) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $username, $email, $hashed_password);

        // Execute the statement
        if ($stmt->execute()) {
            // Registration successful, redirect to login.php (now at root)
            $_SESSION['success_message'] = "Pendaftaran berhasil! Silakan masuk.";
            header("location: login.php");
            exit();
        } else {
            // Error during insertion
            $errors[] = "Terjadi kesalahan saat mendaftar. Silakan coba lagi. Error: " . $stmt->error;
        }

        // Close the statement
        $stmt->close();
    }

    // If there are errors, store them in session and redirect back to register.php (now at root)
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        $_SESSION['form_data'] = $_POST; // Preserve form data
        header("location: register.php");
        exit();
    }
} else {
    // If accessed directly without POST request, redirect to register.php (now at root)
    header("location: register.php");
    exit();
}

// Close the database connection
$conn->close();
?>
