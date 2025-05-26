<?php
// includes/admin_header.php
// Location: FINAL_PROJECT-TIK2032/includes/admin_header.php

// IMPORTANT: session_start() MUST be the very first thing in any file that uses sessions,
// before any output (even whitespace or HTML tags).
// This conditional check ensures it's called only once per request.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if logged in AND role is 'admin'
$isAdminLoggedIn = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true && ($_SESSION['role'] ?? '') === 'admin';
$adminFullName = $_SESSION['admin_full_name'] ?? ''; // Get the admin's full name, fallback to username

// If not logged in as admin, redirect to the unified login.php
if (!$isAdminLoggedIn) {
    header("Location: ../login.php"); // Path from includes/ to root login.php
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../css/style.css"> 
    </head>
<body>
    <header>
        <div class="container">
            <h1><a href="../admin/dashboard.php">Admin Panel SLM</a></h1>
            <nav>
                <ul>                    
                    <li><a href="../admin/dashboard.php">Dashboard</a></li>
                    <li><a href="../admin/kategori_kelola.php">Kelola Kategori</a></li>
                    <li><a href="../admin/laporan_detail.php">Lihat Laporan</a></li>
                    <li>Selamat Datang, **<?php echo htmlspecialchars($adminFullName); ?>**!</li>
                    <li><a href="../logout.php">Keluar</a></li> </ul>
            </nav>
        </div>
    </header>
    <main class="container">
        <p style="color: blue; font-weight: bold;">DEBUG: Session admin_full_name: <?php echo htmlspecialchars($_SESSION['admin_full_name'] ?? 'NOT SET'); ?></p>
    </main>
</body>
</html>
