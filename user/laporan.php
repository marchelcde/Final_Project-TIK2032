<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$isLoggedIn = isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true;
$userRole = $_SESSION['user_role'] ?? null; 
$userId = $_SESSION['user_id'] ?? 'N/A';
$userName = $_SESSION['user_fullname'] ?? 'N/A';
$userUsername = $_SESSION['user_username'] ?? 'N/A';
$userEmail = $_SESSION['user_email'] ?? 'N/A';


?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Aduan Masyarakat</title>
    <link rel="stylesheet" href="css/laporan-style.css"> <link
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
      rel="stylesheet"
    />
</head>
<body data-logged-in="<?php echo $isLoggedIn ? 'true' : 'false'; ?>">
    <header>
        <nav class="navbar">
            <div class="nav-container">
                <div class="nav-logo">
                    <h2>SLM</h2>
                </div>
                <ul class="nav-menu">
                    <li class="nav-item"><a href="user-dashboard.html" class="nav-link">Back To Dashboard</a></li>
                    <li class="nav-item"><a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
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
        <?php if ($isLoggedIn && $userRole === 'user'): ?>
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
        <?php else: ?>
        <section class="not-logged-in-message">
            <h3>Anda Belum Login</h3>
            <p>Silakan <a href="../login.html">Masuk</a> atau <a href="register.html">Daftar</a> untuk membuat laporan dan melihat informasi akun Anda.</p>
        </section>
        <?php endif; ?>

    <footer>
        <div class="container">
            <p>&copy; 2025 Sistem Laporan Aduan Masyarakat. All rights reserved.</p>
        </div>
    </footer>

    <script src="../shared/js/script.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (document.getElementById('totalReportsCount') &&
                document.getElementById('inProgressReportsCount') &&
                document.getElementById('completedReportsCount')) {
                
                fetch('../shared/php/report_handler.php?action=stats')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.data) {
                            const totalReports = data.data.total;
                            let inProgress = 0;
                            let completed = 0;

                            data.data.by_status.forEach(statusStat => {
                                if (statusStat.status === 'in_progress') {
                                    inProgress = statusStat.count;
                                } else if (statusStat.status === 'completed') {
                                    completed = statusStat.count;
                                }
                            });

                            document.getElementById('totalReportsCount').textContent = totalReports;
                            document.getElementById('inProgressReportsCount').textContent = inProgress;
                            document.getElementById('completedReportsCount').textContent = completed;
                        } else {
                            console.error('Failed to fetch report stats:', data.error || 'Unknown error');
                            document.getElementById('totalReportsCount').textContent = 'N/A';
                            document.getElementById('inProgressReportsCount').textContent = 'N/A';
                            document.getElementById('completedReportsCount').textContent = 'N/A';
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching report stats:', error);
                        document.getElementById('totalReportsCount').textContent = 'Error';
                        document.getElementById('inProgressReportsCount').textContent = 'Error';
                        document.getElementById('completedReportsCount').textContent = 'Error';
                    });
            }
        });
    </script>
</body>
</html>