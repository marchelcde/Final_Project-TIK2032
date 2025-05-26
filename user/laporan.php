<?php
// index.php
// Location: FINAL_PROJECT-TIK2032/index.php

// Include header.php which handles session_start() and sets $isLoggedIn, $userRole, $userName
include '../includes/header.php'; // Path from root to includes/

// $isLoggedIn, $userRole, and $userName are now available from header.php.

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SLM - Sistem Laporan Masyarakat</title>
    <link rel="stylesheet" href="css/style.css"> 
</head>
<body data-logged-in="<?php echo $isLoggedIn ? 'true' : 'false'; ?>">
    <main class="container">
        <?php if ($isLoggedIn && $userRole === 'user'): // Display user info only if logged in as a regular user ?>
        <section class="user-info-section">
            <h3>Informasi Pengguna Saat Ini</h3>
            <p><strong>ID Pengguna:</strong> <?php echo htmlspecialchars($_SESSION['id'] ?? 'N/A'); ?></p>
            <p><strong>Nama:</strong> <?php echo htmlspecialchars($_SESSION['name'] ?? 'N/A'); ?></p>
            <p><strong>Username:</strong> <?php echo htmlspecialchars($_SESSION['username'] ?? 'N/A'); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($_SESSION['email'] ?? 'N/A'); ?></p>
            <p><a href="logout.php">Logout</a></p>
        </section>
        <?php elseif ($isLoggedIn && $userRole === 'admin'): // Display admin info if logged in as admin ?>
        <section class="admin-info-section">
            <h3>Informasi Admin Saat Ini</h3>
            <p><strong>ID Admin:</strong> <?php echo htmlspecialchars($_SESSION['admin_id'] ?? 'N/A'); ?></p>
            <p><strong>Username Admin:</strong> <?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'N/A'); ?></p>
            <p>Anda login sebagai Administrator. <a href="admin/dashboard.php">Pergi ke Dashboard Admin</a></p>
            <p><a href="logout.php">Logout Admin</a></p>
        </section>
        <?php else: // Display a message if not logged in ?>
        <section class="not-logged-in-message">
            <h3>Anda Belum Login</h3>
            <p>Silakan <a href="login.php">Masuk</a> atau <a href="register.php">Daftar</a> untuk membuat laporan dan melihat informasi akun Anda.</p>
        </section>
        <?php endif; ?>

        <section class="hero">
            <h2>Selamat Datang di Sistem Laporan Masyarakat</h2>
            <p>Laporkan masalah atau berikan aspirasi Anda untuk komunitas yang lebih baik. Kami siap mendengarkan dan menindaklanjuti.</p>
            <a href="user/laporan.php" class="button require-login">Buat Laporan Sekarang!</a>
        </section>

        <section class="features">
            <h3>Mengapa Menggunakan SLM?</h3>
            <article>
                <h4>Mudah & Cepat</h4>
                <p>Sampaikan laporan Anda hanya dalam beberapa langkah sederhana.</p>
            </article>
            <article>
                <h4>Transparan</h4>
                <p>Lacak status laporan Anda secara online kapan saja.</p>
            </article>
            <article>
                <h4>Responsif</h4>
                <p>Laporan Anda akan diterima dan ditangani oleh pihak berwenang.</p>
            </article>
        </section>

        <section class="stats">
            <h3>Statistik Laporan</h3>
            <p>Total Laporan Masuk: <strong>150</strong></p>
            <p>Laporan Diproses: <strong>75</strong></p>
            <p>Laporan Selesai: <strong>60</strong></p>
        </section>
    </main>
    <div id="popup-overlay"></div> 
    <div id="popup-container">
    </div>
    <script src="js/script.js"></script> 

<?php
include 'includes/footer.php';
?>
</body>
</html>
