<?php
// admin/proses_login.php
session_start();
include '../config/database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $conn->real_escape_string($_POST['username']);
    $password_input = $_POST['password'];

    // Ambil data admin berdasarkan username
    $sql = "SELECT id_admin, username, password FROM pengguna_admin WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $admin = $result->fetch_assoc();
        
        // Verifikasi password (ASUMSI PASSWORD DI DB SUDAH DI-HASH!)
        // Ganti 'password_hash_dari_db' dengan $admin['password']
        // Jika password di DB belum di-hash, gunakan if ($password_input == $admin['password'])
        // SANGAT DISARANKAN MENGGUNAKAN HASH: password_verify($password_input, $admin['password'])
        
        // Contoh: Jika di DB belum di-hash (TIDAK AMAN)
        // if ($password_input === $admin['password']) {
            
        // Contoh: Jika di DB sudah di-hash (AMAN)
        if (password_verify($password_input, $admin['password'])) {
            // Password cocok, buat session
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $admin['id_admin'];
            $_SESSION['admin_username'] = $admin['username'];
            
            $stmt->close();
            $conn->close();
            header("Location: dashboard.php"); // Redirect ke dashboard
            exit();
        } else {
            // Password salah
            header("Location: login.php?error=1");
            exit();
        }
    } else {
        // Username tidak ditemukan
        header("Location: login.php?error=1");
        exit();
    }
    
    $stmt->close();
    $conn->close();

} else {
    header("Location: login.php");
    exit();
}
?>