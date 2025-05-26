<?php
// login.php
// Location: FINAL_PROJECT-TIK2032/login.php

// Include header.php which handles session_start()
include 'includes/header.php'; // Path from root to includes/

$errors = [];
$success_message = '';

// Retrieve messages and form data from session
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// Check if the user is already logged in, redirect based on role
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        header("location: admin/dashboard.php");
    } else {
        header("location: index.php"); // Default to main index for regular users
    }
    exit;
}

if (isset($_SESSION['errors'])) {
    $errors = $_SESSION['errors'];
    unset($_SESSION['errors']);
}

$form_data = [];
if (isset($_SESSION['form_data'])) {
    $form_data = $_SESSION['form_data'];
    unset($_SESSION['form_data']);
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
</head>
<body>
    <div class="login-container">
        <h2>Masuk</h2>
        
        <?php if (!empty($success_message)): ?>
            <p style="color: green;"><?php echo $success_message; ?></p>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <ul style="color: red;">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <form action="proses_login.php" method="POST">
            <div class="form-group">
                <label for="username_email">Username atau Email:</label><br>
                <input type="text" id="username_email" name="username_email" required value="<?php echo htmlspecialchars($form_data['username_email'] ?? ''); ?>"><br>
            </div>
            <div class="form-group">
                <label for="password">Password:</label><br>
                <input type="password" id="password" name="password" required><br>
            </div>
            <button type="submit">Login</button>
            <p>Belum punya akun? <a href="register.php">Daftar Sekarang</a></p>
        </form>
        <p><a href="index.php"> Kembali ke Halaman Utama</a></p>
    </div>
</body>
</html>
