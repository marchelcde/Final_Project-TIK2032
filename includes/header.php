<?php
// includes/header.php
// Location: FINAL_PROJECT-TIK2032/includes/header.php

// IMPORTANT: session_start() MUST be the very first thing in any file that uses sessions,
// before any output (even whitespace or HTML tags).
// This conditional check ensures it's called only once per request.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Determine if a user is logged in and their role (only interested if it's 'user' or 'guest' here)
$isLoggedIn = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
$userRole = $_SESSION['role'] ?? 'guest'; // Default role to 'guest'
$userName = '';

if ($isLoggedIn && $userRole === 'user') { // Only get user name if logged in as a regular user
    $userName = $_SESSION['name'] ?? $_SESSION['username'] ?? 'Pengguna';
}
// Admin specific details are handled in admin_header.php
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SLM - Sistem Laporan Masyarakat</title>
    <link rel="stylesheet" href="css/style.css"> 
</head>
<body>
    <header>
        <div class="container">
            <h1><a href="index.php">Sistem Laporan Masyarakat (SLM)</a></h1>
            <nav>
                <ul>                    
                    <?php if ($userRole === 'user'): ?>
                        <li><a href="laporan.php" class="require-login">Laporan</a></li>
                        <li><a href="about.php">Tentang SLM</a></li>
                        <li>Selamat Datang, **<?php echo htmlspecialchars($userName); ?>**!</li>
                        <li><a href="logout.php">Keluar</a></li> <?php else: // Guest or Admin viewing a user page ?>
                        <li><a href="about.php">Tentang SLM</a></li>
                        <li><a href="login.php" id="open-login-btn">Masuk</a></li> <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    <main class="container">
    </main>
</body>
</html>
