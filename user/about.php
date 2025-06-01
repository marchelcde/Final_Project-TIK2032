<?php
 include '../includes/header.php'; 
 include '../includes/footer.php';
 ?>


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SLM - Sistem Laporan Masyarakat</title>
    <link rel="stylesheet" href="shared/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body data-logged-in="<?php echo $isLoggedIn ? 'true' : 'false'; ?>">
    <header>
        <nav class="navbar">
            <div class="nav-container">
                <div class="nav-logo">
                    <h2>SLM</h2>
                </div>
                <ul class="nav-menu">
                    <li class="nav-item"><a href="user-dashboard.html" class="nav-link">Home</a></li>
                    <li class="nav-item"><a href="about.php" class="nav-link">About</a></li>
                    <?php if (!$isLoggedIn): ?>
                        <li class="nav-item"><a href="login.html" class="nav-link">Login</a></li>
                        <li class="nav-item"><a href="register.html" class="nav-link">Register</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a href="<?php echo ($userRole === 'admin') ? 'admin/admin-dashboard.html' : 'user/user-dashboard.html'; ?>" class="nav-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                        <li class="nav-item"><a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    <?php endif; ?>
                </ul>
                <div class="hamburger">
                    <span class="bar"></span>
                    <span class="bar"></span>
                    <span class="bar"></span>
                </div>
            </div>
        </nav>
    </header>
    <main class="container">
        <h2>Tentang Sistem Laporan Masyarakat (SLM)</h2>
        <p>Sistem Informasi Laporan Masyarakat (SLM) adalah sebuah aplikasi web yang dibangun sebagai bagian dari Proyek Akhir Mata Kuliah Pemrograman Web.</p>
        <p>Tujuan utama kami adalah menciptakan platform digital yang sederhana namun efektif untuk menjembatani komunikasi antara masyarakat dan pihak berwenang dalam menangani isu-isu di lingkungan sekitar.</p>
        
        <h3>Tim Pengembang</h3>
        <p>Proyek ini dikerjakan oleh kelompok mahasiswa yang terdiri dari:</p>
        <ul>
            <li>Marchel Manullang - 230211060047 - FE/BE</li>
            <li>Keefa Lasut - 230211060068 - BE</li>
            <li>Valentino Taroreh - 23021106009 - FE</li>
        </ul>

        <h3>Teknologi yang Digunakan</h3>
        <p>Aplikasi ini dibangun murni menggunakan:</p>
        <ul>
            <li>HTML5</li>
            <li>CSS3</li>
            <li>JavaScript</li>
            <li>PHP</li>
            <li>MySQL</li>
        </ul>
        <p>Kami tidak menggunakan library atau framework eksternal sesuai dengan ketentuan proyek.</p>
    </main>

    <footer>
        </footer>
</body>
</html>