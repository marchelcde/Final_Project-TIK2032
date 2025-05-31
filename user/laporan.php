<?php
// laporan.php
// Location: FINAL_PROJECT-TIK2032/laporan.php (assuming this file is now in the root directory)

// Ensure session is started if not already. This is crucial for accessing $_SESSION variables.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set variables based on session data for easier use in HTML
$isLoggedIn = isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true;
$userRole = $_SESSION['user_role'] ?? null; // 'user' or 'admin'
$userId = $_SESSION['user_id'] ?? 'N/A';
$userName = $_SESSION['user_fullname'] ?? 'N/A';
$userUsername = $_SESSION['user_username'] ?? 'N/A';
$userEmail = $_SESSION['user_email'] ?? 'N/A';

// Include header.php and footer.php. Adjust paths based on their actual location relative to laporan.php.
// Assuming 'includes' directory is a direct sibling to 'laporan.php'
include 'includes/header.php'; // Path from root to includes/

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
                    <li class="nav-item"><a href="index.html" class="nav-link">Home</a></li>
                    <li class="nav-item"><a href="about.html" class="nav-link">About</a></li>
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
        <?php if ($isLoggedIn && $userRole === 'user'): // Display user info and report form if logged in as a regular user ?>
        <section class="user-info-section">
            <h3>Informasi Pengguna Saat Ini</h3>
            <p><strong>Nama:</strong> <?php echo htmlspecialchars($userName); ?></p>
            <p><strong>Username:</strong> <?php echo htmlspecialchars($userUsername); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($userEmail); ?></p>
        </section>

        <section id="reportSubmission" class="form-section" style="margin-top: 3rem;">
            <h2>Buat Laporan Baru</h2>
            <p>Isi formulir di bawah ini untuk mengirimkan laporan Anda.</p>
            <form id="userReportForm" class="report-form" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="judul">Judul Aduan</label>
                    <input type="text" id="judul" name="judul" required />
                </div>

                <div class="form-group">
                    <label for="kategori">Kategori Aduan</label>
                    <select id="kategori" name="kategori" required>
                        <option value="">Pilih Kategori</option>
                        <option value="infrastruktur">Infrastruktur</option>
                        <option value="lingkungan">Lingkungan</option>
                        <option value="sosial">Sosial</option>
                        <option value="ekonomi">Ekonomi</option>
                        <option value="lainnya">Lainnya</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="lokasi">Lokasi Kejadian</label>
                    <input type="text" id="lokasi" name="lokasi" required />
                </div>

                <div class="form-group">
                    <label for="deskripsi">Deskripsi Aduan</label>
                    <textarea
                        id="deskripsi"
                        name="deskripsi"
                        rows="5"
                        required
                    ></textarea>
                </div>

                <div class="form-group">
                    <label for="foto_bukti">Upload Foto Bukti (Opsional)</label>
                    <input type="file" id="foto_bukti" name="foto_bukti" accept="image/*" />
                </div>

                <button type="submit" class="btn btn-primary">Kirim Laporan</button>
            </form>
        </section>

        <?php elseif ($isLoggedIn && $userRole === 'admin'): // Display admin info if logged in as admin ?>
        <section class="admin-info-section">
            <h3>Informasi Admin Saat Ini</h3>
            <p><strong>ID Admin:</strong> <?php echo htmlspecialchars($userId); ?></p>
            <p><strong>Username Admin:</strong> <?php echo htmlspecialchars($userUsername); ?></p>
            <p>Anda login sebagai Administrator. <a href="admin/admin-dashboard.html" class="button">Pergi ke Dashboard Admin</a></p>
            <p><a href="logout.php" class="button">Logout Admin</a></p>
        </section>
        <?php else: // Display a message if not logged in ?>
        <section class="not-logged-in-message">
            <h3>Anda Belum Login</h3>
            <p>Silakan <a href="login.html">Masuk</a> atau <a href="register.html">Daftar</a> untuk membuat laporan dan melihat informasi akun Anda.</p>
        </section>
        <?php endif; ?>

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

    <footer>
        <div class="container">
            <p>&copy; 2025 Sistem Laporan Aduan Masyarakat. All rights reserved.</p>
        </div>
    </footer>

    <script src="../shared/js/script.js"></script>

<?php
// Assuming 'includes' directory is a direct sibling to 'laporan.php'
include 'includes/footer.php';
?>
</body>
</html>
