<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar</title>
</head>
<body>
    <div class = "register-container"></div>
    <h2>Daftar </h2>

    <form action="proses_register.php" method="POST">
        <div class="form-group">
            <label for="name">Nama :</label>
            <input type="text" id="name" name="name" required>
        <div class="form-group">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>
        </div>

        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>

        <div class="form-group">
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
        </div>  
        <p> Minimal 8 karakter dan harus berisi kombinasi huruf kapital, huruf kecil, angka dan karakter khusus (@$!%*#?&).</p>
        <div class="form-group">
            <label for="confirm_password">Konfirmasi Password:</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
        </div>

        <button type="submit">Daftar</button>
        <p>Sudah punya akun? <a href="login.php">Masuk Sekarang</a></p>
        <button type="button" onclick="window.location.href='../index.php'">Kembali ke Halaman Utama</button>

</body>
</html>