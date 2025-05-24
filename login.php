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
        <button id= "close-popup">&times; </button>
        
        <form action="proses_login.php" method="POST">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit">Login</button>
            <p>Belum punya akun? <a href="register.php">Daftar Sekarang</a></p>
        </form>
        <p><a href="../index.php"> Kembali ke Halaman Utama</a></p>
    </div>
</body>
</html>