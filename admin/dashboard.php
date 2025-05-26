<?php
// admin/dashboard.php
// Location: FINAL_PROJECT-TIK2032/admin/dashboard.php

// Include the new admin_header.php
// Path from admin/ to includes/admin_header.php
include '../includes/admin_header.php'; 

// The login check is now handled within admin_header.php,
// so you don't strictly need it here again, but it doesn't hurt for redundancy.
// if (!isset($_SESSION['loggedin']) || !isset($_SESSION['loggedin']) || ($_SESSION['role'] ?? '') !== 'admin') {
//     header("Location: ../login.php");
//     exit();
// }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    </head>
<body>
    <div class="admin-dashboard-content">
        <h2>Dashboard Administrator</h2>
        <p>Selamat datang di panel kontrol administrator. Di sini Anda dapat mengelola kategori, melihat laporan, dan melakukan pengaturan sistem.</p>
        
        <h3>Tugas Administrator:</h3>
        <div>
            <div>
            <h4>Pengaturan Sistem</h4>
            <p>Melakukan konfigurasi dan pemeliharaan pengaturan umum aplikasi untuk memastikan kelancaran operasional.</p>
        </div>
            <h4>Kelola Kategori Laporan</h4>
            <p>Bertanggung jawab untuk menambah, mengedit, atau menghapus kategori laporan yang tersedia bagi pengguna.</p>
        </div>
        <div>
            <h4>Lihat Semua Laporan</h4>
            <p>Mengakses dan mengelola semua laporan yang masuk dari masyarakat, termasuk melihat detail dan status laporan.</p>
        </div>
        
    </div>
    <?php
    // Include the footer.php
    include '../includes/footer.php'; // Path from admin/ to includes/footer.php
    ?>
</body>
</html>
