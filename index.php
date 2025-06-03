<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$isLoggedIn = isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true;

?>

<!DOCTYPE html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>LSM</title>
    <link rel="stylesheet" href="shared/css/style.css" />
    <link
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
      rel="stylesheet"
    />
  </head>
  <body data-logged-in="<?php echo $isLoggedIn ? 'true' : 'false'; ?>">
    <nav class="navbar">
      <div class="nav-container">
        <div class="nav-logo">
          <h2>SLM</h2>
        </div>
        <ul class="nav-menu">
          <li class="nav-item">
            <a href="index.php" class="nav-link active">Home</a>
          </li>
          <li class="nav-item">
            <a href="about.html" class="nav-link">About</a>
          </li>
          <?php if ($isLoggedIn): ?>
          <li class="nav-item">
            <a href="user/dashboard.php" class="nav-link">Dashboard</a>
          </li>
          <li class="nav-item">
            <a href="logout.php" class="nav-link">Logout</a>
          </li>
          <?php else: ?>
          <li class="nav-item">
            <a href="login.html" class="nav-link">Login</a>
          </li>
          <li class="nav-item">
            <a href="register.html" class="nav-link">Register</a>
          </li>
          <?php endif; ?>
        </ul>
        <div class="hamburger">
          <span class="bar"></span>
          <span class="bar"></span>
          <span class="bar"></span>
        </div>
      </div>
    </nav>

    <main>
      <section class="hero">
        <div class="container">
          <h1>Sistem Laporan Aduan Masyarakat</h1>
          <p>
            Laporkan keluhan dan saran Anda untuk pembangunan yang lebih baik
          </p>
          <?php if ($isLoggedIn): ?>
            <a href="user/laporan.php" class="btn btn-primary">Buat Laporan</a>
          <?php else: ?>
            <a href="login.html" class="btn btn-primary">Login untuk Membuat Laporan</a>
          <?php endif; ?>
        </div>
      </section>

      <section class="features">
        <div class="container">
          <h2>Mengapa Menggunakan Sistem Ini?</h2>
          <div class="features-grid">
            <div class="feature-card">
              <i class="fas fa-fast-forward"></i>
              <h3>Cepat & Mudah</h3>
              <p>Proses pelaporan yang simpel dan tidak ribet</p>
            </div>
            <div class="feature-card">
              <i class="fas fa-shield-alt"></i>
              <h3>Aman & Terpercaya</h3>
              <p>Data Anda akan dijaga kerahasiaannya</p>
            </div>
            <div class="feature-card">
              <i class="fas fa-clock"></i>
              <h3>Respon Cepat</h3>
              <p>Tim kami akan merespon laporan Anda dengan segera</p>
            </div>
          </div>
        </div>
      </section>
    </main>

    <footer>
      <div class="footer-bottom">
        <p>&copy; 2025 Sistem Laporan Aduan Masyarakat. All rights reserved.</p>
      </div>
    </footer>

    <script src="shared/js/script.js"></script>
  </body>
</html>
