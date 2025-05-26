<?php
// register.php
// Location: FINAL_PROJECT-TIK2032/register.php

// Include header.php which handles session_start()
include 'includes/header.php'; // Path from root to includes/

$errors = [];
$success_message = '';
$form_data = [];

// Retrieve messages and form data from session
if (isset($_SESSION['errors'])) {
    $errors = $_SESSION['errors'];
    unset($_SESSION['errors']); // Clear errors after displaying
}

if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']); // Clear message after displaying
}

if (isset($_SESSION['form_data'])) {
    $form_data = $_SESSION['form_data'];
    unset($_SESSION['form_data']); // Clear form data after displaying
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar</title>
</head>
<body>
    <div class="register-container">
        <h2>Daftar</h2>

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

        <form action="proses_register.php" method="POST" onsubmit="return validateForm()">
            <div class="form-group">
                <label for="name">Nama :</label><br>
                <input type="text" id="name" name="name" required value="<?php echo htmlspecialchars($form_data['name'] ?? ''); ?>"><br>
            </div>
            <div class="form-group">
                <label for="username">Username:</label><br>
                <input type="text" id="username" name="username" required value="<?php echo htmlspecialchars($form_data['username'] ?? ''); ?>"><br>
            </div>

            <div class="form-group">
                <label for="email">Email:</label><br>
                <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>"><br>
            </div>

            <div class="form-group">
                <label for="password">Password:</label><br>
                <input type="password" id="password" name="password" required><br>
            </div>
            <p>Minimal 8 karakter dan harus berisi kombinasi huruf kapital, huruf kecil, angka dan karakter khusus (@$!%*#?&).</p>
            <div class="form-group">
                <label for="confirm_password">Konfirmasi Password:</label><br>
                <input type="password" id="confirm_password" name="confirm_password" required><br>
            </div>

            <button type="submit">Daftar</button>
            <p>Sudah punya akun? <a href="login.php">Masuk Sekarang</a></p>
            <button type="button" onclick="window.location.href='index.php'">Kembali ke Halaman Utama</button>
        </form>
    </div>

    <script>
        function validateForm() {
            const password = document.getElementById('password').value;
            const confirm_password = document.getElementById('confirm_password').value;
            const name = document.getElementById('name').value;
            const username = document.getElementById('username').value;
            const email = document.getElementById('email').value;

            let messages = [];

            if (name.trim() === '') {
                messages.push('Nama tidak boleh kosong.');
            }
            if (username.trim() === '') {
                messages.push('Username tidak boleh kosong.');
            }
            if (email.trim() === '') {
                messages.push('Email tidak boleh kosong.');
            }

            if (password.length < 8) {
                messages.push('Password minimal 8 karakter.');
            }
            if (!/[A-Z]/.test(password)) {
                messages.push('Password harus berisi setidaknya satu huruf kapital.');
            }
            if (!/[a-z]/.test(password)) {
                messages.push('Password harus berisi setidaknya satu huruf kecil.');
            }
            if (!/[0-9]/.test(password)) {
                messages.push('Password harus berisi setidaknya satu angka.');
            }
            if (!/[^A-Za-z0-9]/.test(password)) {
                messages.push('Password harus berisi setidaknya satu karakter khusus (@$!%*#?&).');
            }

            if (password !== confirm_password) {
                messages.push('Konfirmasi password tidak cocok dengan password.');
            }

            if (messages.length > 0) {
                alert(messages.join('\n'));
                return false;
            }
            return true;
        }
    </script>
</body>
</html>
